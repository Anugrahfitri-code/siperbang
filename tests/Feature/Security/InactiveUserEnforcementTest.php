<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InactiveUserEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_login()
    {
        $user = User::factory()->create([
            'status' => 'Nonaktif',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'password123',
        ]);

        // Login fails with generic message
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Password atau username salah, mohon coba lagi.']);
        $this->assertGuest();
    }

    public function test_stale_session_is_denied_on_protected_get()
    {
        $user = User::factory()->create([
            'status' => 'Aktif',
            'role' => 'Petugas Persediaan',
        ]);

        // Login first
        $this->actingAs($user);

        // Verify can access protected route initially
        $response = $this->getJson('/api/stocks');
        $response->assertStatus(200);

        // Deactivate user in database without destroying session
        $user->update(['status' => 'Nonaktif']);

        // Next request with same session should be denied
        $response = $this->getJson('/api/stocks');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
        $this->assertGuest(); // Middleware logs out the user
    }

    public function test_stale_session_is_denied_on_protected_post()
    {
        $user = User::factory()->create([
            'status' => 'Aktif',
            'role' => 'Petugas Persediaan',
        ]);

        $this->actingAs($user);

        // Deactivate user in database
        $user->update(['status' => 'Nonaktif']);

        // Next POST request should be denied
        $response = $this->postJson('/api/requests', [
            'keperluan' => 'Test',
            'status' => 'draft',
            'items' => [
                ['nama_barang' => 'Test', 'jumlah_diminta' => 1],
            ],
        ]);

        $response->assertStatus(401);
        $this->assertGuest();
    }

    public function test_stale_privileged_session_is_denied()
    {
        $user = User::factory()->create([
            'status' => 'Aktif',
            'role' => 'Superadmin',
        ]);

        $this->actingAs($user);

        // Deactivate user in database
        $user->update(['status' => 'Nonaktif']);

        // Next request to privileged endpoint should be denied
        $response = $this->getJson('/api/users');

        $response->assertStatus(401);
        $this->assertGuest();
    }

    public function test_database_sessions_are_revoked_on_deactivation()
    {
        $admin = User::factory()->create([
            'status' => 'Aktif',
            'role' => 'Superadmin',
        ]);

        $target = User::factory()->create([
            'status' => 'Aktif',
        ]);

        $other = User::factory()->create([
            'status' => 'Aktif',
        ]);

        // Create synthetic sessions
        DB::table('sessions')->insert([
            ['id' => 'session_target_1', 'user_id' => $target->id, 'payload' => 'dummy', 'last_activity' => time()],
            ['id' => 'session_target_2', 'user_id' => $target->id, 'payload' => 'dummy', 'last_activity' => time()],
            ['id' => 'session_other_1', 'user_id' => $other->id, 'payload' => 'dummy', 'last_activity' => time()],
            ['id' => 'session_admin_1', 'user_id' => $admin->id, 'payload' => 'dummy', 'last_activity' => time()],
        ]);

        $this->actingAs($admin);

        // Admin deactivates target
        $response = $this->putJson("/api/users/{$target->id}", [
            'name' => $target->name,
            'username' => $target->username,
            'role' => $target->role,
            'status' => 'Nonaktif',
        ]);

        $response->assertStatus(200);

        // Assert target's sessions are deleted
        $this->assertEquals(0, DB::table('sessions')->where('user_id', $target->id)->count());

        // Assert other sessions are preserved
        $this->assertEquals(1, DB::table('sessions')->where('user_id', $other->id)->count());
        $this->assertEquals(1, DB::table('sessions')->where('user_id', $admin->id)->count());
    }

    public function test_ordinary_update_preserves_sessions()
    {
        $admin = User::factory()->create([
            'status' => 'Aktif',
            'role' => 'Superadmin',
        ]);

        $target = User::factory()->create([
            'status' => 'Aktif',
            'name' => 'Old Name',
        ]);

        DB::table('sessions')->insert([
            ['id' => 'session_target_update', 'user_id' => $target->id, 'payload' => 'dummy', 'last_activity' => time()],
        ]);

        $this->actingAs($admin);

        // Admin updates name, keeps status Aktif
        $response = $this->putJson("/api/users/{$target->id}", [
            'name' => 'New Name',
            'username' => $target->username,
            'role' => $target->role,
            'status' => 'Aktif',
        ]);

        $response->assertStatus(200);

        // Assert target's session is preserved
        $this->assertEquals(1, DB::table('sessions')->where('user_id', $target->id)->count());
    }

    public function test_reactivation_does_not_resurrect_sessions()
    {
        $admin = User::factory()->create([
            'status' => 'Aktif',
            'role' => 'Superadmin',
        ]);

        $target = User::factory()->create([
            'status' => 'Aktif',
        ]);

        DB::table('sessions')->insert([
            ['id' => 'session_reactivation', 'user_id' => $target->id, 'payload' => 'dummy', 'last_activity' => time()],
        ]);

        $this->actingAs($admin);

        // 1. Deactivate
        $this->putJson("/api/users/{$target->id}", [
            'name' => $target->name,
            'username' => $target->username,
            'role' => $target->role,
            'status' => 'Nonaktif',
        ]);

        $this->assertEquals(0, DB::table('sessions')->where('user_id', $target->id)->count());

        // 2. Reactivate
        $this->putJson("/api/users/{$target->id}", [
            'name' => $target->name,
            'username' => $target->username,
            'role' => $target->role,
            'status' => 'Aktif',
        ]);

        // Sessions remain 0
        $this->assertEquals(0, DB::table('sessions')->where('user_id', $target->id)->count());
    }
}
