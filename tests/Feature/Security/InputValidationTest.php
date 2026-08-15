<?php

namespace Tests\Feature\Security;

use App\Models\BonHeader;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InputValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_invalid_pagination_rejected()
    {
        $user = User::create([
            'name' => 'Petugas Test',
            'username' => 'petugas_test',
            'email' => 'petugas@test.com',
            'password' => bcrypt('password'),
            'role' => 'Petugas Persediaan',
            'status' => 'Aktif',
        ]);
        $this->actingAs($user);

        $response = $this->getJson('/api/stocks/search?per_page=1000');
        $response->assertStatus(422);
    }

    public function test_requester_spoofing_ignored_for_ketua_tim()
    {
        $user = User::create([
            'name' => 'Ketua Test',
            'username' => 'ketua_test',
            'email' => 'ketua@test.com',
            'password' => bcrypt('password'),
            'role' => 'Ketua Tim',
            'status' => 'Aktif',
        ]);
        $this->actingAs($user);

        $response = $this->postJson('/api/requests', [
            'keperluan' => 'Test',
            'status' => 'Draft',
            'requester' => 'Superadmin',
            'items' => [
                [
                    'barang_id' => null,
                    'nama_barang' => 'Barang A',
                    'jumlah_diminta' => 1,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $bon = BonHeader::where('bon_no', $response->json('bon_no'))->first();
        $this->assertEquals($user->name, $bon->requester);
    }

    public function test_invalid_date_rejected()
    {
        $user = User::create([
            'name' => 'Ketua Test 2',
            'username' => 'ketua_test_2',
            'email' => 'ketua2@test.com',
            'password' => bcrypt('password'),
            'role' => 'Ketua Tim',
            'status' => 'Aktif',
        ]);
        $this->actingAs($user);

        $response = $this->getJson('/api/requests/bon?start_date=not-a-date');
        $response->assertStatus(422);
    }

    public function test_nonexistent_requester_fails_safely_for_superadmin()
    {
        $user = User::create([
            'name' => 'Superadmin Test',
            'username' => 'superadmin_test',
            'email' => 'super@test.com',
            'password' => bcrypt('password'),
            'role' => 'Superadmin',
            'status' => 'Aktif',
        ]);
        $this->actingAs($user);

        $response = $this->postJson('/api/requests', [
            'keperluan' => 'Test Delegation',
            'status' => 'Draft',
            'requester' => 'NonExistentUser123',
            'items' => [
                [
                    'barang_id' => null,
                    'nama_barang' => 'Barang A',
                    'jumlah_diminta' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_inactive_requester_fails_safely_for_superadmin()
    {
        $super = User::where('role', 'Superadmin')->first() ?? User::create([
            'name' => 'Superadmin Test 2',
            'username' => 'superadmin_test_2',
            'email' => 'super2@test.com',
            'password' => bcrypt('password'),
            'role' => 'Superadmin',
            'status' => 'Aktif',
        ]);
        $this->actingAs($super);

        $inactiveUser = User::create([
            'name' => 'Inactive User',
            'username' => 'inactive_user',
            'email' => 'inactive@test.com',
            'password' => bcrypt('password'),
            'role' => 'Ketua Tim',
            'status' => 'Nonaktif',
        ]);

        $response = $this->postJson('/api/requests', [
            'keperluan' => 'Test Delegation Inactive',
            'status' => 'Draft',
            'requester' => 'inactive_user',
            'items' => [
                [
                    'barang_id' => null,
                    'nama_barang' => 'Barang A',
                    'jumlah_diminta' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_client_section_and_role_cannot_override_delegation()
    {
        $super = User::where('role', 'Superadmin')->first() ?? User::create([
            'name' => 'Superadmin Test 3',
            'username' => 'superadmin_test_3',
            'email' => 'super3@test.com',
            'password' => bcrypt('password'),
            'role' => 'Superadmin',
            'status' => 'Aktif',
        ]);
        $this->actingAs($super);

        $targetUser = User::create([
            'name' => 'Target User',
            'username' => 'target_user',
            'email' => 'target@test.com',
            'password' => bcrypt('password'),
            'role' => 'Ketua Tim',
            'section' => 'Seksi Asli',
            'status' => 'Aktif',
        ]);

        $response = $this->postJson('/api/requests', [
            'keperluan' => 'Test Override',
            'status' => 'Draft',
            'requester' => 'target_user',
            'section' => 'Seksi Palsu', // Try to override
            'role' => 'Superadmin', // Try to override
            'items' => [
                [
                    'barang_id' => null,
                    'nama_barang' => 'Barang A',
                    'jumlah_diminta' => 1,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $bon = BonHeader::where('bon_no', $response->json('bon_no'))->first();
        $this->assertEquals('Seksi Asli', $bon->section);
        $this->assertEquals('Target User', $bon->requester);
    }

    public function test_log_filters_validated()
    {
        $super = User::where('role', 'Superadmin')->first() ?? User::create([
            'name' => 'Superadmin Test 4',
            'username' => 'superadmin_test_4',
            'email' => 'super4@test.com',
            'password' => bcrypt('password'),
            'role' => 'Superadmin',
            'status' => 'Aktif',
        ]);
        $this->actingAs($super);

        // invalid year
        $this->getJson('/api/export-excel?year=202A')->assertStatus(422);
        // invalid month
        $this->getJson('/api/export-excel?month=99')->assertStatus(422);
        // invalid annual
        $this->getJson('/api/export-excel?annual=nope')->assertStatus(422);
        // oversized search
        $this->getJson('/api/export-excel?search='.str_repeat('A', 300))->assertStatus(422);
        // valid existing filters still work
        $this->getJson('/api/export-excel?year=2026&month=All&annual=true')->assertStatus(200);
    }
}
