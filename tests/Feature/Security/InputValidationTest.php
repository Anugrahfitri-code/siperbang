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

        $response->assertStatus(422); // Because SafeBusinessException is usually mapped to 422 in this controller
    }
}
