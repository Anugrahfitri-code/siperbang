<?php

namespace Tests\Feature\Security;

use App\Models\BonHeader;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_ketua_tim_cannot_read_others_bon()
    {
        $owner = User::create(['name' => 'Owner', 'username' => 'owner', 'email' => 'owner@test.com', 'password' => bcrypt('password'), 'role' => 'Ketua Tim', 'status' => 'Aktif']);
        $other = User::create(['name' => 'Other', 'username' => 'other', 'email' => 'other@test.com', 'password' => bcrypt('password'), 'role' => 'Ketua Tim', 'status' => 'Aktif']);

        $bon = BonHeader::create(['bon_no' => 'BON/TEST/001', 'user_id' => $owner->id, 'requester' => 'Owner', 'section' => 'TU', 'date' => today(), 'status' => 'Draft', 'last_updated' => today()]);

        $this->actingAs($other);
        $response = $this->getJson('/api/requests/bon/'.$bon->id);
        $response->assertStatus(403);
    }

    public function test_ketua_tim_cannot_update_others_bon()
    {
        $owner = User::create(['name' => 'Owner2', 'username' => 'owner2', 'email' => 'owner2@test.com', 'password' => bcrypt('password'), 'role' => 'Ketua Tim', 'status' => 'Aktif']);
        $other = User::create(['name' => 'Other2', 'username' => 'other2', 'email' => 'other2@test.com', 'password' => bcrypt('password'), 'role' => 'Ketua Tim', 'status' => 'Aktif']);

        $bon = BonHeader::create(['bon_no' => 'BON/TEST/002', 'user_id' => $owner->id, 'requester' => 'Owner2', 'section' => 'TU', 'date' => today(), 'status' => 'Draft', 'last_updated' => today()]);

        $this->actingAs($other);
        $response = $this->putJson('/api/requests/bon/'.$bon->id, [
            'keperluan' => 'Test',
            'status' => 'Draft',
            'items' => [
                [
                    'barang_id' => null,
                    'nama_barang' => 'Barang A',
                    'jumlah_diminta' => 1,
                ],
            ],
        ]);
        $response->assertStatus(403);
    }

    public function test_ketua_tim_cannot_delete_others_bon()
    {
        $owner = User::create(['name' => 'Owner3', 'username' => 'owner3', 'email' => 'owner3@test.com', 'password' => bcrypt('password'), 'role' => 'Ketua Tim', 'status' => 'Aktif']);
        $other = User::create(['name' => 'Other3', 'username' => 'other3', 'email' => 'other3@test.com', 'password' => bcrypt('password'), 'role' => 'Ketua Tim', 'status' => 'Aktif']);

        $bon = BonHeader::create(['bon_no' => 'BON/TEST/003', 'user_id' => $owner->id, 'requester' => 'Owner3', 'section' => 'TU', 'date' => today(), 'status' => 'Draft', 'last_updated' => today()]);

        $this->actingAs($other);
        $response = $this->deleteJson('/api/requests/bon/'.$bon->id);
        $response->assertStatus(403);
    }

    public function test_owner_can_read_own_bon()
    {
        $owner = User::create(['name' => 'Owner4', 'username' => 'owner4', 'email' => 'owner4@test.com', 'password' => bcrypt('password'), 'role' => 'Ketua Tim', 'status' => 'Aktif']);
        $bon = BonHeader::create(['bon_no' => 'BON/TEST/004', 'user_id' => $owner->id, 'requester' => 'Owner4', 'section' => 'TU', 'date' => today(), 'status' => 'Draft', 'last_updated' => today()]);

        $this->actingAs($owner);
        $response = $this->getJson('/api/requests/bon/'.$bon->id);
        $response->assertStatus(200);
    }

    public function test_superadmin_can_read_and_update_others_bon()
    {
        $owner = User::create(['name' => 'Owner5', 'username' => 'owner5', 'email' => 'owner5@test.com', 'password' => bcrypt('password'), 'role' => 'Ketua Tim', 'status' => 'Aktif']);
        $admin = User::create(['name' => 'Admin', 'username' => 'admin_test', 'email' => 'admin@test.com', 'password' => bcrypt('password'), 'role' => 'Superadmin', 'status' => 'Aktif']);

        $bon = BonHeader::create(['bon_no' => 'BON/TEST/005', 'user_id' => $owner->id, 'requester' => 'Owner5', 'section' => 'TU', 'date' => today(), 'status' => 'Draft', 'last_updated' => today()]);

        $this->actingAs($admin);

        $response = $this->getJson('/api/requests/bon/'.$bon->id);
        $response->assertStatus(200);

        $response = $this->putJson('/api/requests/bon/'.$bon->id, [
            'keperluan' => 'Superadmin Edit',
            'status' => 'Draft',
            'requester' => $owner->username,
            'items' => [
                [
                    'barang_id' => null,
                    'nama_barang' => 'Barang A',
                    'jumlah_diminta' => 1,
                ],
            ],
        ]);
        $response->assertStatus(200);
    }
}
