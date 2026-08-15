<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_unauthenticated_cannot_access_operational_route()
    {
        $response = $this->getJson('/api/stocks');
        $response->assertStatus(401);
    }

    public function test_ketua_tim_cannot_access_stock_privileged_action()
    {
        $user = User::create(['name' => 'Ketua Test', 'username' => 'ketua_test', 'email' => 'ketua@test.com', 'password' => bcrypt('password'), 'role' => 'Ketua Tim', 'status' => 'Aktif']);
        $this->actingAs($user);

        $response = $this->postJson('/api/stok-upload/1/finalisasi-api');
        $response->assertStatus(403);

        $response = $this->post('/stok-upload/1/batalkan');
        $response->assertStatus(403);
    }

    public function test_ketua_tim_cannot_access_superadmin_action()
    {
        $user = User::create(['name' => 'Ketua Test 2', 'username' => 'ketua_test_2', 'email' => 'ketua2@test.com', 'password' => bcrypt('password'), 'role' => 'Ketua Tim', 'status' => 'Aktif']);
        $this->actingAs($user);

        $response = $this->getJson('/api/users');
        $response->assertStatus(403);
    }

    public function test_petugas_cannot_access_superadmin_action()
    {
        $user = User::create(['name' => 'Petugas Test', 'username' => 'petugas_test', 'email' => 'petugas@test.com', 'password' => bcrypt('password'), 'role' => 'Petugas Persediaan', 'status' => 'Aktif']);
        $this->actingAs($user);

        $response = $this->postJson('/api/settings');
        $response->assertStatus(403);
    }

    public function test_superadmin_can_access_superadmin_action()
    {
        $user = User::create(['name' => 'Super Test', 'username' => 'super_test', 'email' => 'super@test.com', 'password' => bcrypt('password'), 'role' => 'Superadmin', 'status' => 'Aktif']);
        $this->actingAs($user);

        $response = $this->getJson('/api/users');
        $response->assertStatus(200);
    }
}
