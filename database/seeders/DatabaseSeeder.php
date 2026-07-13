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
        User::create([
            'name' => 'Iwan Setiawan',
            'email' => 'iwan@example.com',
            'username' => 'iwan.s',
            'password' => bcrypt('password'),
            'role' => 'Petugas Persediaan',
            'section' => 'Persediaan',
        ]);

        User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'username' => 'budi.tu',
            'password' => bcrypt('password'),
            'role' => 'Ketua Tim Kerja',
            'section' => 'Tata Usaha',
        ]);

        User::create([
            'name' => 'Admin Utama',
            'email' => 'admin@example.com',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'Superadmin',
            'section' => 'Admin',
        ]);

        $this->call(KategoriDanKodePersediaanSeeder::class);
    }
}
