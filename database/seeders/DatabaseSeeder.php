<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['username' => 'iwan.s'],
            [
                'name' => 'Iwan Setiawan',
                'email' => 'iwan@example.com',
                'password' => bcrypt('password'),
                'role' => 'Petugas Persediaan',
                'section' => 'Persediaan',
            ],
        );

        User::query()->updateOrCreate(
            ['username' => 'budi.tu'],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi@example.com',
                'password' => bcrypt('password'),
                'role' => 'Ketua Tim Kerja',
                'section' => 'Tata Usaha',
            ],
        );

        User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin Utama',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'role' => 'Superadmin',
                'section' => 'Admin',
            ],
        );

        $this->call(OfficeActivityInventoryCodeSeeder::class);
    }
}
