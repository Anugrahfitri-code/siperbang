<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credentials_can_login()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'status' => 'Aktif',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_password_generates_login_failure()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'status' => 'Aktif',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
        $this->assertGuest();
    }

    public function test_repeated_invalid_attempts_are_throttled()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'status' => 'Aktif',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/login', [
                'username' => 'testuser',
                'password' => 'wrongpassword',
            ]);
            $response->assertStatus(401);
            $response->assertJson(['message' => 'Password atau username salah, mohon coba lagi.']);
        }

        // The 6th attempt should be throttled
        $response = $this->postJson('/api/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
        $response->assertJsonStructure(['message']);
        $this->assertStringContainsString('Terlalu banyak percobaan login', $response->json('message'));
        $this->assertGuest();
    }

    public function test_attempt_below_threshold_is_not_throttled()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'status' => 'Aktif',
        ]);

        for ($i = 0; $i < 4; $i++) {
            $response = $this->postJson('/api/login', [
                'username' => 'testuser',
                'password' => 'wrongpassword',
            ]);
            $response->assertStatus(401);
        }

        $this->assertGuest();
    }

    public function test_successful_login_clears_rate_limit()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'status' => 'Aktif',
        ]);

        // Fail 4 times
        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/login', [
                'username' => 'testuser',
                'password' => 'wrongpassword',
            ]);
        }

        // 5th time succeeds
        $response = $this->postJson('/api/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);
        $response->assertStatus(200);

        Auth()->logout();

        // After successful login, failure counter should be reset,
        // so a new failure won't immediately hit the 5 limit limit.
        for ($i = 0; $i < 4; $i++) {
            $response = $this->postJson('/api/login', [
                'username' => 'testuser',
                'password' => 'wrongpassword',
            ]);
            $response->assertStatus(401);
        }

        $this->assertGuest();
    }

    public function test_rate_limit_key_does_not_block_everyone_globally()
    {
        // First user fails 5 times and gets locked out
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'username' => 'userA',
                'password' => 'wrongpassword',
            ]);
        }

        $responseA = $this->postJson('/api/login', [
            'username' => 'userA',
            'password' => 'wrongpassword',
        ]);
        $responseA->assertStatus(429);

        // Second user should not be locked out
        $responseB = $this->postJson('/api/login', [
            'username' => 'userB',
            'password' => 'wrongpassword',
        ]);
        $responseB->assertStatus(401); // Normal failure, not throttled
    }

    public function test_throttled_login_response_does_not_leak_sensitive_details()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'username' => 'testuser',
                'password' => 'supersecretpassword',
            ]);
        }

        $response = $this->postJson('/api/login', [
            'username' => 'testuser',
            'password' => 'supersecretpassword',
        ]);

        $response->assertStatus(429);
        $content = $response->getContent();

        // Assert generic message
        $response->assertJsonStructure(['message']);

        // Ensure sensitive details are not leaked
        $this->assertStringNotContainsString('supersecretpassword', $content);
        $this->assertStringNotContainsString('Exception', $content);
        $this->assertStringNotContainsString('Trace', $content);
        $this->assertStringNotContainsString('user testuser ditemukan', $content);
    }
}
