<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProvisionSuperadminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:provision-superadmin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provision the initial Superadmin account securely';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Safety check: does a Superadmin already exist?
        $superadminExists = User::where('role', 'Superadmin')->exists();

        if ($superadminExists) {
            $this->info('SUPERADMIN ALREADY EXISTS — NO CHANGES MADE');
            return self::SUCCESS; // Safe no-op
        }

        $this->info('Provisioning initial Superadmin account.');

        // 2. Interactive inputs
        $name = $this->ask('Full Name');
        if (empty($name)) {
            $this->error('Name cannot be empty.');
            return self::FAILURE;
        }

        $username = $this->ask('Username');
        if (empty($username)) {
            $this->error('Username cannot be empty.');
            return self::FAILURE;
        }

        // Validate username uniqueness (or if non-Superadmin has it)
        $usernameExists = User::where('username', $username)->exists();
        if ($usernameExists) {
            $this->error("User with username '{$username}' already exists. No user created.");
            return self::FAILURE;
        }

        // 3. Password input
        $password = $this->secret('Password (min 12 characters)');
        $confirmPassword = $this->secret('Confirm Password');

        if ($password !== $confirmPassword) {
            $this->error('Passwords do not match. NO USER CREATED.');
            return self::FAILURE;
        }

        $validator = Validator::make(['password' => $password], [
            'password' => ['required', 'string', \Illuminate\Validation\Rules\Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            $this->error('Password does not meet the application policy. NO USER CREATED.');
            return self::FAILURE;
        }

        // 4. Create the Superadmin
        try {
            User::create([
                'name' => $name,
                'username' => $username,
                'email' => null, // Optional, can be updated later in UI
                'password' => Hash::make($password),
                'role' => 'Superadmin',
                'section' => 'Admin',
                'status' => 'Aktif',
            ]);

            $this->info('Superadmin provisioned successfully.');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create user due to database error. No user created.');
            // Do not print raw exception to avoid leaking details
            return self::FAILURE;
        }
    }
}
