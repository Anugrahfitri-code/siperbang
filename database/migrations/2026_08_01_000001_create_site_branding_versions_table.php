<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_branding_versions', function (Blueprint $table) {
            $table->id();
            $table->string('label', 120);
            $table->json('settings');
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('effective_from')->nullable()->index();
            $table->timestamp('effective_until')->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $settings = DB::table('site_settings')
            ->pluck('value', 'key')
            ->map(fn ($value) => (string) ($value ?? ''))
            ->all();

        $settings['app_logo_path'] = $this->legacyAssetPath(
            $settings['app_logo_path'] ?? $settings['app_logo_url'] ?? '/images/brand/siperbang-symbol.png',
        );
        $settings['instansi_logo_path'] = $this->legacyAssetPath(
            $settings['instansi_logo_path'] ?? $settings['instansi_logo_url'] ?? '/images/brand/komdigi-logo.png',
        );
        $settings['favicon_path'] = $this->legacyAssetPath(
            $settings['favicon_path'] ?? $settings['app_logo_path'],
        );

        if (isset($settings['footer_copyright'])) {
            $legacyDefaultFooter = '© 2026 BBPSDM Komunikasi dan Digital Makassar. Seluruh hak cipta dilindungi.';

            if (trim($settings['footer_copyright']) === $legacyDefaultFooter) {
                $settings['footer_copyright'] = '© {year} {instansi_name}. Seluruh hak cipta dilindungi.';
            } else {
                $settings['footer_copyright'] = preg_replace(
                    '/©\s*2026\b/u',
                    '© {year}',
                    $settings['footer_copyright'],
                    1,
                ) ?: $settings['footer_copyright'];
            }
        }

        $allowedKeys = [
            'app_name',
            'app_subtitle',
            'instansi_name',
            'instansi_sub',
            'login_heading',
            'login_description',
            'footer_copyright',
            'app_logo_path',
            'instansi_logo_path',
            'favicon_path',
        ];

        foreach ($allowedKeys as $key) {
            if (! array_key_exists($key, $settings)) {
                continue;
            }

            $existing = DB::table('site_settings')->where('key', $key)->exists();

            if ($existing) {
                DB::table('site_settings')
                    ->where('key', $key)
                    ->update([
                        'value' => $settings[$key],
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('site_settings')->insert([
                    'key' => $key,
                    'value' => $settings[$key],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('site_settings')
            ->whereIn('key', ['app_logo_url', 'instansi_logo_url'])
            ->delete();

        $versionSettings = [];
        foreach ($allowedKeys as $key) {
            if (isset($settings[$key])) {
                $versionSettings[$key] = $settings[$key];
            }
        }

        DB::table('site_branding_versions')->insert([
            'label' => 'Identitas awal sebelum versioning',
            'settings' => json_encode(
                $versionSettings,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ),
            'status' => 'published',
            'effective_from' => now(),
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_branding_versions');
    }

    private function legacyAssetPath(string $value): string
    {
        $path = parse_url($value, PHP_URL_PATH) ?: $value;

        if (str_starts_with($path, '/storage/')) {
            return ltrim(substr($path, strlen('/storage/')), '/');
        }

        // Keep explicitly external assets intact. Only Laravel public-storage
        // URLs are converted into a portable relative path.
        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        return $path;
    }
};
