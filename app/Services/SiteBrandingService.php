<?php

namespace App\Services;

use App\Models\HistoryLog;
use App\Models\SiteBrandingVersion;
use App\Models\User;
use App\Support\SiteBranding\HtmlSanitizer;
use App\Support\SiteBranding\ImageOptimizer;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SiteBrandingService
{
    private const ACTIVE_CACHE_KEY = 'site_branding.active.v1';

    private const ACTIVE_CACHE_SECONDS = 21600;

    private ?array $activeCache = null;

    private bool $checkingScheduledVersion = false;

    public function __construct(
        private readonly HtmlSanitizer $htmlSanitizer,
        private readonly ImageOptimizer $imageOptimizer,
    ) {
    }

    /** @return array<string, string> */
    public function activeRaw(): array
    {
        if ($this->activeCache !== null) {
            return $this->activeCache;
        }

        if (! Schema::hasTable('site_settings')) {
            return $this->activeCache = $this->defaults();
        }

        $this->activateDueScheduledVersion();

        try {
            $stored = Cache::remember(
                self::ACTIVE_CACHE_KEY,
                self::ACTIVE_CACHE_SECONDS,
                fn (): array => $this->readStoredSettings(),
            );
        } catch (Throwable $exception) {
            // Branding must remain available even when the configured cache
            // backend is temporarily unavailable or has not been migrated yet.
            report($exception);
            $stored = $this->readStoredSettings();
        }

        return $this->activeCache = $this->normalizeRawSettings($stored);
    }

    /** @return array<string, string> */
    public function active(): array
    {
        return $this->presentSettings($this->activeRaw());
    }

    /** @return array<string, string> */
    public function forViews(): array
    {
        $settings = $this->active();
        $settings['footer_copyright_rendered'] = $this->renderTemplate(
            $settings['footer_copyright'],
            $settings,
        );

        return $settings;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, UploadedFile|null> $files
     */
    public function saveVersion(
        array $data,
        array $files,
        User $actor,
        ?SiteBrandingVersion $version = null,
    ): SiteBrandingVersion {
        if (
            $version !== null
            && ! in_array($version->status, [
                SiteBrandingVersion::STATUS_DRAFT,
                SiteBrandingVersion::STATUS_SCHEDULED,
            ], true)
        ) {
            throw ValidationException::withMessages([
                'version' => 'Hanya draft atau versi terjadwal yang dapat diedit.',
            ]);
        }

        $baseSettings = $version
            ? $this->normalizeRawSettings($version->settings ?? [])
            : $this->activeRaw();

        $settings = array_replace(
            $baseSettings,
            $this->sanitizeTextSettings($data),
        );

        $newlyStoredPaths = [];
        $replacedPaths = [];

        try {
            foreach ([
                'app_logo' => ['key' => 'app_logo_path', 'folder' => 'app-logo', 'max_dimension' => 1600],
                'instansi_logo' => ['key' => 'instansi_logo_path', 'folder' => 'institution-logo', 'max_dimension' => 1600],
                'favicon' => ['key' => 'favicon_path', 'folder' => 'favicon', 'max_dimension' => 512],
            ] as $input => $definition) {
                $file = $files[$input] ?? null;

                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $oldPath = $settings[$definition['key']] ?? null;
                $newPath = $this->storeAsset(
                    $file,
                    $definition['folder'],
                    $definition['max_dimension'],
                    $input,
                );
                $settings[$definition['key']] = $newPath;
                $newlyStoredPaths[] = $newPath;

                if ($oldPath && $oldPath !== $newPath) {
                    $replacedPaths[] = $oldPath;
                }
            }

            $savedVersion = DB::transaction(function () use (
                $data,
                $settings,
                $actor,
                $version,
            ) {
                $attributes = [
                    'label' => trim((string) $data['label']),
                    'settings' => Arr::only(
                        $settings,
                        array_merge($this->textKeys(), $this->assetKeys()),
                    ),
                    'status' => SiteBrandingVersion::STATUS_DRAFT,
                    'effective_from' => $this->parseDate($data['effective_from'] ?? null),
                    'effective_until' => null,
                    'notes' => $data['notes'] ?? null,
                    'published_by' => null,
                    'published_at' => null,
                ];

                if ($version) {
                    $version->fill($attributes)->save();

                    return $version->fresh();
                }

                return SiteBrandingVersion::create([
                    ...$attributes,
                    'created_by' => $actor->id,
                ]);
            });
        } catch (Throwable $exception) {
            foreach ($newlyStoredPaths as $path) {
                $this->deleteManagedAsset($path);
            }

            throw $exception;
        }

        foreach ($replacedPaths as $path) {
            $this->deleteAssetWhenUnused($path, $savedVersion->id);
        }

        $changes = $this->settingChanges($baseSettings, $settings);
        $changedKeys = array_keys($changes);

        $this->recordAudit(
            $actor,
            $version ? 'BRANDING_DRAFT_UPDATED' : 'BRANDING_DRAFT_CREATED',
            [
                'version_id' => $savedVersion->id,
                'label' => $savedVersion->label,
                'changed_keys' => $changedKeys,
                'changes' => $changes,
            ],
        );

        if (($data['action'] ?? 'draft') === 'publish') {
            return $this->publish(
                $savedVersion,
                $actor,
                $this->parseDate($data['effective_from'] ?? null),
            );
        }

        return $savedVersion->fresh(['creator', 'publisher']);
    }

    public function publish(
        SiteBrandingVersion $version,
        User $actor,
        ?CarbonInterface $effectiveFrom = null,
    ): SiteBrandingVersion {
        if ($version->status === SiteBrandingVersion::STATUS_ARCHIVED) {
            throw ValidationException::withMessages([
                'version' => 'Versi arsip harus dipulihkan melalui aksi rollback agar riwayat publikasi tetap utuh.',
            ]);
        }

        if ($version->status === SiteBrandingVersion::STATUS_PUBLISHED) {
            return $version->fresh(['creator', 'publisher']);
        }

        if (! in_array($version->status, [
            SiteBrandingVersion::STATUS_DRAFT,
            SiteBrandingVersion::STATUS_SCHEDULED,
        ], true)) {
            throw ValidationException::withMessages([
                'version' => 'Status versi identitas tidak dapat dipublikasikan.',
            ]);
        }

        $effectiveFrom ??= $version->effective_from;

        if ($effectiveFrom && $effectiveFrom->isFuture()) {
            $version->forceFill([
                'status' => SiteBrandingVersion::STATUS_SCHEDULED,
                'effective_from' => $effectiveFrom,
                'published_by' => null,
                'published_at' => null,
            ])->save();

            $this->recordAudit($actor, 'BRANDING_SCHEDULED', [
                'version_id' => $version->id,
                'label' => $version->label,
                'effective_from' => $effectiveFrom->toIso8601String(),
            ]);

            return $version->fresh(['creator', 'publisher']);
        }

        return $this->activateVersion($version, $actor);
    }

    public function rollback(
        SiteBrandingVersion $target,
        User $actor,
    ): SiteBrandingVersion {
        if ($target->status !== SiteBrandingVersion::STATUS_ARCHIVED) {
            throw ValidationException::withMessages([
                'version' => 'Rollback hanya dapat dilakukan dari versi identitas yang sudah diarsipkan.',
            ]);
        }

        $rollback = SiteBrandingVersion::create([
            'label' => 'Rollback: '.$target->label.' ('.now()->format('d-m-Y H:i').')',
            'settings' => $this->normalizeRawSettings($target->settings ?? []),
            'status' => SiteBrandingVersion::STATUS_DRAFT,
            'effective_from' => now(),
            'notes' => 'Dibuat otomatis dari versi #'.$target->id.'.',
            'created_by' => $actor->id,
        ]);

        $this->recordAudit($actor, 'BRANDING_ROLLBACK_CREATED', [
            'source_version_id' => $target->id,
            'rollback_version_id' => $rollback->id,
        ]);

        return $this->activateVersion($rollback, $actor, true);
    }

    public function deleteVersion(
        SiteBrandingVersion $version,
        User $actor,
    ): void {
        if (! in_array($version->status, [
            SiteBrandingVersion::STATUS_DRAFT,
            SiteBrandingVersion::STATUS_SCHEDULED,
            SiteBrandingVersion::STATUS_ARCHIVED,
        ], true)) {
            throw ValidationException::withMessages([
                'version' => 'Versi aktif publikasi tidak boleh dihapus.',
            ]);
        }

        $paths = Arr::only(
            $this->normalizeRawSettings($version->settings ?? []),
            $this->assetKeys(),
        );
        $versionId = $version->id;
        $label = $version->label;
        $wasArchived = $version->status === SiteBrandingVersion::STATUS_ARCHIVED;

        $version->delete();

        foreach ($paths as $path) {
            $this->deleteAssetWhenUnused((string) $path);
        }

        $this->recordAudit($actor, $wasArchived ? 'BRANDING_ARCHIVE_DELETED' : 'BRANDING_DRAFT_DELETED', [
            'version_id' => $versionId,
            'label' => $label,
        ]);
    }

    /** @return array<string, mixed> */
    public function presentVersion(SiteBrandingVersion $version): array
    {
        $version->loadMissing(['creator:id,name', 'publisher:id,name']);

        return [
            'id' => $version->id,
            'label' => $version->label,
            'status' => $version->status,
            'settings' => $this->presentSettings(
                $this->normalizeRawSettings($version->settings ?? []),
            ),
            'effective_from' => $version->effective_from?->toIso8601String(),
            'effective_until' => $version->effective_until?->toIso8601String(),
            'published_at' => $version->published_at?->toIso8601String(),
            'notes' => $version->notes,
            'created_at' => $version->created_at?->toIso8601String(),
            'updated_at' => $version->updated_at?->toIso8601String(),
            'creator' => $version->creator
                ? ['id' => $version->creator->id, 'name' => $version->creator->name]
                : null,
            'publisher' => $version->publisher
                ? ['id' => $version->publisher->id, 'name' => $version->publisher->name]
                : null,
        ];
    }

    /** @return array<string, string> */
    public function presentSettings(array $rawSettings): array
    {
        $settings = $this->normalizeRawSettings($rawSettings);

        $presented = Arr::only($settings, $this->textKeys());
        $presented['app_logo_url'] = $this->assetUrl($settings['app_logo_path']);
        $presented['instansi_logo_url'] = $this->assetUrl($settings['instansi_logo_path']);
        $presented['favicon_url'] = $this->assetUrl($settings['favicon_path']);

        return $presented;
    }

    /** @param array<string, string> $settings */
    public function renderTemplate(string $template, array $settings): string
    {
        return strtr($template, [
            '{year}' => (string) now()->year,
            '{app_name}' => $settings['app_name'] ?? '',
            '{instansi_name}' => $settings['instansi_name'] ?? '',
            '{instansi_full_name}' => $settings['instansi_sub'] ?? '',
        ]);
    }

    public function safeFilePrefix(?string $name = null): string
    {
        $name ??= $this->activeRaw()['app_name'] ?? 'APLIKASI';
        $prefix = Str::upper(Str::slug($name, '_'));

        return $prefix !== '' ? $prefix : 'APLIKASI';
    }

    private function activateVersion(
        SiteBrandingVersion $version,
        ?User $actor,
        bool $isRollback = false,
    ): SiteBrandingVersion {
        [$activated, $didActivate, $previousVersionId] = DB::transaction(function () use ($version, $actor) {
            /** @var SiteBrandingVersion $lockedVersion */
            $lockedVersion = SiteBrandingVersion::query()
                ->lockForUpdate()
                ->findOrFail($version->id);

            // Another request may have activated this version while the caller
            // was waiting for the row lock. Do not publish or audit it twice.
            if ($lockedVersion->status === SiteBrandingVersion::STATUS_PUBLISHED) {
                return [$lockedVersion, false, null];
            }

            if (! in_array($lockedVersion->status, [
                SiteBrandingVersion::STATUS_DRAFT,
                SiteBrandingVersion::STATUS_SCHEDULED,
            ], true)) {
                throw ValidationException::withMessages([
                    'version' => 'Status versi identitas tidak dapat dipublikasikan.',
                ]);
            }

            // Scheduler calls have no actor. Re-check the due date while the
            // row is locked so a reschedule/cancel action cannot race with it.
            if ($actor === null && (
                $lockedVersion->status !== SiteBrandingVersion::STATUS_SCHEDULED
                || $lockedVersion->effective_from === null
                || $lockedVersion->effective_from->isFuture()
            )) {
                return [$lockedVersion, false, null];
            }

            $previousVersionId = SiteBrandingVersion::query()
                ->where('status', SiteBrandingVersion::STATUS_PUBLISHED)
                ->where('id', '!=', $lockedVersion->id)
                ->lockForUpdate()
                ->value('id');

            SiteBrandingVersion::query()
                ->where('status', SiteBrandingVersion::STATUS_PUBLISHED)
                ->where('id', '!=', $lockedVersion->id)
                ->update([
                    'status' => SiteBrandingVersion::STATUS_ARCHIVED,
                    'effective_until' => now(),
                ]);

            SiteBrandingVersion::query()
                ->where('status', SiteBrandingVersion::STATUS_SCHEDULED)
                ->where('effective_from', '<=', now())
                ->where('id', '!=', $lockedVersion->id)
                ->update(['status' => SiteBrandingVersion::STATUS_ARCHIVED]);

            $settings = $this->normalizeRawSettings($lockedVersion->settings ?? []);
            $this->syncActiveSettings($settings);

            $lockedVersion->forceFill([
                'status' => SiteBrandingVersion::STATUS_PUBLISHED,
                'effective_from' => $lockedVersion->effective_from ?? now(),
                'effective_until' => null,
                'published_by' => $actor?->id,
                'published_at' => now(),
            ])->save();

            return [$lockedVersion, true, $previousVersionId];
        });

        if (! $didActivate) {
            return $activated->fresh(['creator', 'publisher']);
        }

        $this->forgetActiveCache();

        $this->recordAudit(
            $actor,
            $isRollback ? 'BRANDING_ROLLBACK_PUBLISHED' : 'BRANDING_PUBLISHED',
            [
                'version_id' => $activated->id,
                'label' => $activated->label,
                'previous_version_id' => $previousVersionId,
            ],
        );

        return $activated->fresh(['creator', 'publisher']);
    }

    public function activateDueScheduledVersion(): ?SiteBrandingVersion
    {
        if (
            $this->checkingScheduledVersion
            || ! Schema::hasTable('site_branding_versions')
        ) {
            return null;
        }

        $this->checkingScheduledVersion = true;
        $activated = null;

        try {
            $due = SiteBrandingVersion::query()
                ->where('status', SiteBrandingVersion::STATUS_SCHEDULED)
                ->where('effective_from', '<=', now())
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->first();

            if ($due) {
                $activated = $this->activateVersion($due, null);
            }
        } finally {
            $this->checkingScheduledVersion = false;
        }

        return $activated;
    }

    /** @param array<string, mixed> $data
     *  @return array<string, string>
     */
    private function sanitizeTextSettings(array $data): array
    {
        $settings = [];

        foreach ($this->textKeys() as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = trim((string) ($data[$key] ?? ''));

            if (in_array($key, ['login_heading', 'login_description'], true)) {
                $value = $this->htmlSanitizer->sanitize($value);
            }

            $settings[$key] = $value;
        }

        return $settings;
    }

    /** @param array<string, mixed> $settings */
    private function syncActiveSettings(array $settings): void
    {
        $timestamp = now();
        $rows = collect(array_merge($this->textKeys(), $this->assetKeys()))
            ->map(fn (string $key): array => [
                'key' => $key,
                'value' => (string) ($settings[$key] ?? ''),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->all();

        DB::table('site_settings')->upsert(
            $rows,
            ['key'],
            ['value', 'updated_at'],
        );

        DB::table('site_settings')
            ->whereIn('key', ['app_logo_url', 'instansi_logo_url'])
            ->delete();
    }

    /** @return array<string, string> */
    private function readStoredSettings(): array
    {
        return DB::table('site_settings')
            ->whereIn('key', array_merge($this->textKeys(), $this->assetKeys()))
            ->pluck('value', 'key')
            ->map(fn ($value) => (string) ($value ?? ''))
            ->all();
    }

    private function forgetActiveCache(): void
    {
        $this->activeCache = null;

        try {
            Cache::forget(self::ACTIVE_CACHE_KEY);
        } catch (Throwable $exception) {
            // A cache outage must not roll back an otherwise valid publish.
            report($exception);
        }
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @return array<string, array{before: string|null, after: string|null}>
     */
    private function settingChanges(array $before, array $after): array
    {
        $changes = [];

        foreach (array_merge($this->textKeys(), $this->assetKeys()) as $key) {
            $oldValue = array_key_exists($key, $before) ? (string) $before[$key] : null;
            $newValue = array_key_exists($key, $after) ? (string) $after[$key] : null;

            if ($oldValue === $newValue) {
                continue;
            }

            $changes[$key] = [
                'before' => $oldValue,
                'after' => $newValue,
            ];
        }

        return $changes;
    }

    private function storeAsset(
        UploadedFile $file,
        string $folder,
        int $maxDimension,
        string $errorField,
    ): string {
        return $this->imageOptimizer->optimizeAndStore(
            $file,
            $folder,
            $maxDimension,
            $errorField,
        );
    }

    private function deleteAssetWhenUnused(string $path, ?int $exceptVersionId = null): void
    {
        if (! $this->isManagedAsset($path)) {
            return;
        }

        if (Schema::hasTable('site_branding_versions')) {
            $inUse = SiteBrandingVersion::query()
                ->when(
                    $exceptVersionId,
                    fn ($query) => $query->where('id', '!=', $exceptVersionId),
                )
                ->get(['settings'])
                ->contains(function (SiteBrandingVersion $version) use ($path) {
                    return in_array($path, $version->settings ?? [], true);
                });

            if ($inUse) {
                return;
            }
        }

        $activeSettings = Schema::hasTable('site_settings')
            ? DB::table('site_settings')
                ->whereIn('key', $this->assetKeys())
                ->pluck('value')
                ->all()
            : [];

        if (in_array($path, $activeSettings, true)) {
            return;
        }

        $this->deleteManagedAsset($path);
    }

    private function deleteManagedAsset(string $path): void
    {
        if ($this->isManagedAsset($path)) {
            Storage::disk($this->disk())->delete($path);
        }
    }

    private function isManagedAsset(string $path): bool
    {
        return str_starts_with($path, 'branding/');
    }

    private function assetUrl(string $path): string
    {
        if ($path === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        if ($this->disk() === 'public') {
            return '/storage/'.ltrim($path, '/');
        }

        return Storage::disk($this->disk())->url($path);
    }

    private function disk(): string
    {
        return (string) config('site_branding.disk', 'public');
    }

    /** @param array<string, mixed> $settings
     *  @return array<string, string>
     */
    private function normalizeRawSettings(array $settings): array
    {
        if (! isset($settings['app_logo_path']) && isset($settings['app_logo_url'])) {
            $settings['app_logo_path'] = $this->legacyUrlToPath((string) $settings['app_logo_url']);
        }

        if (! isset($settings['instansi_logo_path']) && isset($settings['instansi_logo_url'])) {
            $settings['instansi_logo_path'] = $this->legacyUrlToPath((string) $settings['instansi_logo_url']);
        }

        $settings['favicon_path'] ??= $settings['app_logo_path'] ?? null;

        $normalized = [];
        foreach ($this->defaults() as $key => $default) {
            $value = array_key_exists($key, $settings)
                ? $settings[$key]
                : $default;
            $normalized[$key] = (string) ($value ?? $default);
        }

        return $normalized;
    }

    private function legacyUrlToPath(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $path = parse_url($value, PHP_URL_PATH) ?: $value;

        if (str_starts_with($path, '/storage/')) {
            return ltrim(substr($path, strlen('/storage/')), '/');
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        return $path;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }

    /** @return array<string, string> */
    private function defaults(): array
    {
        return config('site_branding.defaults', []);
    }

    /** @return array<int, string> */
    private function textKeys(): array
    {
        return config('site_branding.text_keys', []);
    }

    /** @return array<int, string> */
    private function assetKeys(): array
    {
        return config('site_branding.asset_keys', []);
    }

    /** @param array<string, mixed> $details */
    private function recordAudit(?User $actor, string $action, array $details): void
    {
        if (! Schema::hasTable('history_logs')) {
            return;
        }

        $requestContext = [];
        if (app()->bound('request')) {
            $request = request();
            $requestContext = [
                'ip_address' => $request->ip(),
                'request_id' => $request->headers->get('X-Request-ID'),
                'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            ];
        }

        try {
            HistoryLog::create([
                'user_id' => $actor?->id,
                'actor' => $actor?->name ?? 'Sistem',
                'action' => $action,
                'details' => json_encode(
                    [...$details, ...array_filter($requestContext)],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ),
            ]);
        } catch (Throwable $exception) {
            // Audit logging is important, but a temporary logging failure must
            // not make a committed branding change appear to have failed.
            report($exception);
        }
    }
}
