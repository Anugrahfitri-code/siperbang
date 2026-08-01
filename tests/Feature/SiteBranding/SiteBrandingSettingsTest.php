<?php

namespace Tests\Feature\SiteBranding;

use App\Models\SiteBrandingVersion;
use App\Models\User;
use App\Services\SiteBrandingService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class SiteBrandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_public_can_read_active_site_identity(): void
    {
        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonPath('app_name', 'SIPERBANG')
            ->assertJsonStructure([
                'app_name',
                'app_subtitle',
                'instansi_name',
                'instansi_sub',
                'login_heading',
                'login_description',
                'footer_copyright',
                'app_logo_url',
                'instansi_logo_url',
                'favicon_url',
            ]);
    }

    public function test_public_settings_only_exposes_whitelisted_fields(): void
    {
        \DB::table('site_settings')->insert([
            'key' => 'internal_secret',
            'value' => 'tidak-boleh-publik',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = $this->getJson('/api/settings')->assertOk()->json();

        $this->assertIsArray($payload);
        $this->assertArrayNotHasKey('internal_secret', $payload);
    }

    public function test_guest_cannot_change_site_identity(): void
    {
        $this->postJson('/api/settings', $this->payload())
            ->assertUnauthorized();
    }

    public function test_blade_entry_page_uses_active_identity_metadata(): void
    {
        $this->withoutVite();

        \DB::table('site_settings')->where('key', 'app_name')->update([
            'value' => 'PORTAL PROFESIONAL',
            'updated_at' => now(),
        ]);
        \DB::table('site_settings')->where('key', 'app_subtitle')->update([
            'value' => 'Portal Persediaan Tahunan',
            'updated_at' => now(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>PORTAL PROFESIONAL</title>', false)
            ->assertSee('name="application-name" content="PORTAL PROFESIONAL"', false)
            ->assertSee('name="description" content="Portal Persediaan Tahunan"', false);
    }

    public function test_non_superadmin_cannot_change_site_identity(): void
    {
        $user = User::factory()->create([
            'role' => 'Petugas Persediaan',
            'status' => 'Aktif',
        ]);

        $this->actingAs($user)
            ->postJson('/api/settings', $this->payload())
            ->assertForbidden();

        $this->assertDatabaseHas('site_settings', [
            'key' => 'app_name',
            'value' => 'SIPERBANG',
        ]);
    }

    public function test_draft_does_not_replace_published_identity(): void
    {
        $admin = $this->superadmin();
        $payload = $this->payload([
            'label' => 'Branding 2027',
            'action' => 'draft',
            'app_name' => 'PORTAL 2027',
        ]);

        $this->actingAs($admin)
            ->postJson('/api/settings/versions', $payload)
            ->assertCreated()
            ->assertJsonPath('version.status', SiteBrandingVersion::STATUS_DRAFT);

        $this->assertDatabaseHas('site_branding_versions', [
            'label' => 'Branding 2027',
            'status' => SiteBrandingVersion::STATUS_DRAFT,
        ]);
        $this->assertDatabaseHas('site_settings', [
            'key' => 'app_name',
            'value' => 'SIPERBANG',
        ]);
    }

    public function test_publish_sanitizes_html_and_updates_active_identity(): void
    {
        $admin = $this->superadmin();
        $payload = $this->payload([
            'label' => 'Branding Baru',
            'app_name' => 'PORTAL PROFESIONAL',
            'login_heading' => '<p onclick="alert(1)">Halo <strong>Tim</strong><script>alert(2)</script></p>',
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/api/settings', $payload)
            ->assertOk()
            ->assertJsonPath('settings.app_name', 'PORTAL PROFESIONAL')
            ->assertJsonPath('version.status', SiteBrandingVersion::STATUS_PUBLISHED);

        $heading = (string) $response->json('settings.login_heading');
        $this->assertStringContainsString('<strong>Tim</strong>', $heading);
        $this->assertStringNotContainsString('<script', $heading);
        $this->assertStringNotContainsString('onclick', $heading);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'app_name',
            'value' => 'PORTAL PROFESIONAL',
        ]);
        $this->assertDatabaseHas('history_logs', [
            'user_id' => $admin->id,
            'actor' => $admin->name,
            'action' => 'BRANDING_PUBLISHED',
        ]);
    }

    public function test_uploaded_logo_is_saved_as_relative_managed_path(): void
    {
        Storage::fake('public');
        $admin = $this->superadmin();

        $response = $this->actingAs($admin)->postJson('/api/settings', [
            ...$this->payload(),
            'app_logo' => UploadedFile::fake()->image('logo.png', 1800, 600),
        ]);

        $response->assertOk();
        $logoUrl = (string) $response->json('settings.app_logo_url');
        $this->assertStringContainsString('/storage/branding/app-logo/', $logoUrl);

        $storedPath = (string) \DB::table('site_settings')
            ->where('key', 'app_logo_path')
            ->value('value');

        $this->assertStringStartsWith('branding/app-logo/', $storedPath);
        $this->assertStringNotContainsString('http://', $storedPath);
        $this->assertStringNotContainsString('https://', $storedPath);
        Storage::disk('public')->assertExists($storedPath);
        [$width, $height] = getimagesize(Storage::disk('public')->path($storedPath));
        $this->assertLessThanOrEqual(1600, max($width, $height));
    }

    public function test_invalid_or_oversized_logo_is_rejected_without_changing_active_identity(): void
    {
        Storage::fake('public');
        $admin = $this->superadmin();

        $this->actingAs($admin)->postJson('/api/settings', [
            ...$this->payload(),
            'app_logo' => UploadedFile::fake()->createWithContent(
                'logo.svg',
                '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
            ),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('app_logo');

        $this->actingAs($admin)->postJson('/api/settings', [
            ...$this->payload(),
            'app_logo' => UploadedFile::fake()->image('terlalu-besar.png', 2100, 10),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('app_logo');

        $this->assertDatabaseHas('site_settings', [
            'key' => 'app_name',
            'value' => 'SIPERBANG',
        ]);
    }

    public function test_archived_branding_assets_remain_available_for_rollback(): void
    {
        Storage::fake('public');
        $admin = $this->superadmin();

        $firstResponse = $this->actingAs($admin)->postJson('/api/settings', [
            ...$this->payload(['label' => 'Logo Pertama']),
            'app_logo' => UploadedFile::fake()->image('pertama.png', 600, 600),
        ])->assertOk();
        $firstVersionId = (int) $firstResponse->json('version.id');
        $firstPath = (string) \DB::table('site_settings')
            ->where('key', 'app_logo_path')
            ->value('value');

        $this->actingAs($admin)->postJson('/api/settings', [
            ...$this->payload(['label' => 'Logo Kedua']),
            'app_logo' => UploadedFile::fake()->image('kedua.png', 600, 600),
        ])->assertOk();
        $secondPath = (string) \DB::table('site_settings')
            ->where('key', 'app_logo_path')
            ->value('value');

        Storage::disk('public')->assertExists($firstPath);
        Storage::disk('public')->assertExists($secondPath);

        $this->actingAs($admin)
            ->postJson("/api/settings/versions/{$firstVersionId}/rollback")
            ->assertOk();

        $this->assertSame(
            $firstPath,
            \DB::table('site_settings')->where('key', 'app_logo_path')->value('value'),
        );
        Storage::disk('public')->assertExists($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    }

    public function test_scheduled_identity_is_published_only_when_due(): void
    {
        Carbon::setTestNow('2026-08-01 08:00:00');
        $admin = $this->superadmin();

        $response = $this->actingAs($admin)
            ->postJson('/api/settings/versions', $this->payload([
                'label' => 'Branding Tahun 2027',
                'action' => 'publish',
                'effective_from' => '2027-01-01 00:00:00',
                'app_name' => 'PORTAL 2027',
            ]))
            ->assertCreated()
            ->assertJsonPath('version.status', SiteBrandingVersion::STATUS_SCHEDULED);

        $versionId = (int) $response->json('version.id');
        $this->assertDatabaseHas('site_settings', [
            'key' => 'app_name',
            'value' => 'SIPERBANG',
        ]);

        Carbon::setTestNow('2027-01-01 00:01:00');
        $published = app(SiteBrandingService::class)->activateDueScheduledVersion();

        $this->assertSame($versionId, $published?->id);
        $this->assertDatabaseHas('site_settings', [
            'key' => 'app_name',
            'value' => 'PORTAL 2027',
        ]);
        $this->assertDatabaseHas('site_branding_versions', [
            'id' => $versionId,
            'status' => SiteBrandingVersion::STATUS_PUBLISHED,
        ]);

        // A second scheduler pass must not duplicate the publication audit.
        $this->assertNull(app(SiteBrandingService::class)->activateDueScheduledVersion());
        $this->assertSame(
            1,
            \DB::table('history_logs')
                ->where('action', 'BRANDING_PUBLISHED')
                ->where('details', 'like', '%"version_id":'.$versionId.'%')
                ->count(),
        );

        Carbon::setTestNow();
    }

    public function test_archived_version_cannot_be_published_directly(): void
    {
        $admin = $this->superadmin();
        $initialVersion = SiteBrandingVersion::query()
            ->where('status', SiteBrandingVersion::STATUS_PUBLISHED)
            ->firstOrFail();

        $this->actingAs($admin)
            ->postJson('/api/settings', $this->payload([
                'label' => 'Branding Pengganti',
                'app_name' => 'NAMA PENGGANTI',
            ]))
            ->assertOk();

        $initialVersion->refresh();
        $this->assertSame(SiteBrandingVersion::STATUS_ARCHIVED, $initialVersion->status);
        $this->assertNotNull($initialVersion->effective_until);

        $this->actingAs($admin)
            ->postJson("/api/settings/versions/{$initialVersion->id}/publish")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('version');
    }

    public function test_login_heading_must_contain_visible_text(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)
            ->postJson('/api/settings', $this->payload([
                'login_heading' => '<p><br></p>',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('login_heading');
    }

    public function test_footer_rejects_unknown_template_tokens(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)
            ->postJson('/api/settings', $this->payload([
                'footer_copyright' => '© {year} {unknown_company}.',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('footer_copyright');

        $this->assertDatabaseHas('site_settings', [
            'key' => 'footer_copyright',
            'value' => '© {year} {instansi_name}. Seluruh hak cipta dilindungi.',
        ]);
    }

    public function test_publish_invalidates_shared_active_branding_cache(): void
    {
        $admin = $this->superadmin();
        $service = app(SiteBrandingService::class);

        $this->assertSame('SIPERBANG', $service->activeRaw()['app_name']);
        $this->assertTrue(Cache::has('site_branding.active.v1'));

        $this->actingAs($admin)
            ->postJson('/api/settings', $this->payload([
                'label' => 'Identitas Cache Baru',
                'app_name' => 'PORTAL TANPA CACHE BASI',
            ]))
            ->assertOk();

        $this->assertTrue(Cache::has('site_branding.active.v1'));
        app()->forgetInstance(SiteBrandingService::class);
        $this->assertSame(
            'PORTAL TANPA CACHE BASI',
            Cache::get('site_branding.active.v1')['app_name'],
        );
    }

    public function test_draft_audit_contains_changed_keys_and_before_after_values(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)
            ->postJson('/api/settings/versions', $this->payload([
                'action' => 'draft',
                'label' => 'Audit Perubahan Nama',
                'app_name' => 'PORTAL TERLACAK',
            ]))
            ->assertCreated();

        $details = (string) \DB::table('history_logs')
            ->where('action', 'BRANDING_DRAFT_CREATED')
            ->latest('id')
            ->value('details');
        $decoded = json_decode($details, true, 512, JSON_THROW_ON_ERROR);

        $this->assertContains('app_name', $decoded['changed_keys']);
        $this->assertSame('SIPERBANG', $decoded['changes']['app_name']['before']);
        $this->assertSame('PORTAL TERLACAK', $decoded['changes']['app_name']['after']);
    }

    public function test_database_failure_removes_new_asset_and_preserves_active_identity(): void
    {
        Storage::fake('public');
        $admin = $this->superadmin();
        $eventName = 'eloquent.creating: '.SiteBrandingVersion::class;
        $listener = static function (): never {
            throw new RuntimeException('Simulasi kegagalan database.');
        };
        Event::listen($eventName, $listener);

        try {
            $this->withoutExceptionHandling();

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Simulasi kegagalan database.');

            $this->actingAs($admin)->postJson('/api/settings', [
                ...$this->payload(),
                'app_logo' => UploadedFile::fake()->image('logo-baru.png', 600, 600),
            ]);
        } finally {
            Event::forget($eventName);

            $this->assertSame([], Storage::disk('public')->allFiles('branding'));
            $this->assertDatabaseHas('site_settings', [
                'key' => 'app_name',
                'value' => 'SIPERBANG',
            ]);
        }
    }

    public function test_rollback_creates_new_published_version_and_restores_identity(): void
    {
        $admin = $this->superadmin();
        $initialVersion = SiteBrandingVersion::query()
            ->where('status', SiteBrandingVersion::STATUS_PUBLISHED)
            ->firstOrFail();

        $this->actingAs($admin)
            ->postJson('/api/settings', $this->payload([
                'label' => 'Branding Sementara',
                'app_name' => 'NAMA SEMENTARA',
            ]))
            ->assertOk();

        $this->actingAs($admin)
            ->postJson("/api/settings/versions/{$initialVersion->id}/rollback")
            ->assertOk()
            ->assertJsonPath('settings.app_name', 'SIPERBANG')
            ->assertJsonPath('version.status', SiteBrandingVersion::STATUS_PUBLISHED);

        $this->assertDatabaseHas('site_settings', [
            'key' => 'app_name',
            'value' => 'SIPERBANG',
        ]);
        $this->assertDatabaseHas('site_branding_versions', [
            'status' => SiteBrandingVersion::STATUS_PUBLISHED,
            'created_by' => $admin->id,
        ]);
    }

    private function superadmin(): User
    {
        return User::factory()->create([
            'role' => 'Superadmin',
            'status' => 'Aktif',
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_replace([
            'label' => 'Identitas '.now()->year,
            'action' => 'publish',
            'effective_from' => null,
            'notes' => 'Pengujian identitas situs.',
            'app_name' => 'SIPERBANG BARU',
            'app_subtitle' => 'Sistem Informasi Persediaan Barang',
            'instansi_name' => 'KOMDIGI',
            'instansi_sub' => 'Kementerian Komunikasi dan Digital Republik Indonesia',
            'login_heading' => '<p>Selamat datang.</p>',
            'login_description' => '<p>Portal persediaan profesional.</p>',
            'footer_copyright' => '© {year} {instansi_name}. Seluruh hak cipta dilindungi.',
        ], $overrides);
    }
}
