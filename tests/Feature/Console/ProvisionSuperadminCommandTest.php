<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProvisionSuperadminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisioning_fails_if_superadmin_already_exists()
    {
        $existing = User::factory()->create([
            'role' => 'Superadmin',
            'username' => 'admin.one',
        ]);
        $originalHash = $existing->password;

        $this->artisan('app:provision-superadmin')
            ->expectsOutput('SUPERADMIN ALREADY EXISTS — NO CHANGES MADE')
            ->assertSuccessful(); // Custom logic returns self::SUCCESS

        // Ensure no new superadmin was created
        $this->assertEquals(1, User::where('role', 'Superadmin')->count());

        $existing->refresh();
        $this->assertEquals($originalHash, $existing->password);
    }

    public function test_provisioning_interactive_success()
    {
        $this->artisan('app:provision-superadmin')
            ->expectsOutput('Provisioning initial Superadmin account.')
            ->expectsQuestion('Full Name', 'Master Admin')
            ->expectsQuestion('Username', 'masteradmin')
            ->expectsQuestion('Password (min 12 characters)', 'SuperSecurePwd123!')
            ->expectsQuestion('Confirm Password', 'SuperSecurePwd123!')
            ->expectsOutput('Superadmin provisioned successfully.')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'username' => 'masteradmin',
            'role' => 'Superadmin',
            'name' => 'Master Admin',
        ]);

        $user = User::where('username', 'masteradmin')->first();
        $this->assertTrue(Hash::check('SuperSecurePwd123!', $user->password));
        $this->assertNotEquals('SuperSecurePwd123!', $user->password);
    }

    public function test_provisioning_fails_on_password_mismatch()
    {
        $this->artisan('app:provision-superadmin')
            ->expectsQuestion('Full Name', 'Master Admin')
            ->expectsQuestion('Username', 'masteradmin')
            ->expectsQuestion('Password (min 12 characters)', 'supersecurepwd123')
            ->expectsQuestion('Confirm Password', 'differentpwd')
            ->expectsOutput('Passwords do not match. NO USER CREATED.')
            ->assertFailed();

        $this->assertDatabaseEmpty('users');
    }

    public function test_provisioning_fails_on_short_password()
    {
        $this->artisan('app:provision-superadmin')
            ->expectsQuestion('Full Name', 'Master Admin')
            ->expectsQuestion('Username', 'masteradmin')
            ->expectsQuestion('Password (min 12 characters)', 'short')
            ->expectsQuestion('Confirm Password', 'short')
            ->assertFailed();

        $this->assertDatabaseEmpty('users');
    }

    public function test_provisioning_fails_on_duplicate_username()
    {
        User::factory()->create([
            'username' => 'duplicate_user',
            'role' => 'Petugas Persediaan',
        ]);

        $this->artisan('app:provision-superadmin')
            ->expectsQuestion('Full Name', 'Master Admin')
            ->expectsQuestion('Username', 'duplicate_user')
            ->expectsOutput("User with username 'duplicate_user' already exists. No user created.")
            ->assertFailed();

        $this->assertEquals(0, User::where('role', 'Superadmin')->count());
    }

    public function test_provisioning_does_not_have_password_cli_option()
    {
        $this->expectException(\Symfony\Component\Console\Exception\RuntimeException::class);
        // Artisan should throw if we pass an option that doesn't exist.
        // We will just verify it throws an exception or doesn't have it.
        $this->artisan('app:provision-superadmin --password=foo');
    }
}
