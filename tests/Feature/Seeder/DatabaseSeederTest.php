<?php

namespace Tests\Feature\Seeder;

use App\Models\KategoriBarang;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_does_not_create_superadmin()
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseMissing('users', [
            'role' => 'Superadmin',
        ]);
        $this->assertDatabaseMissing('users', [
            'username' => 'admin',
        ]);
    }

    public function test_database_seeder_does_not_create_default_petugas_or_ketua_tim()
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseMissing('users', [
            'username' => 'iwan.s',
        ]);
        $this->assertDatabaseMissing('users', [
            'username' => 'budi.tu',
        ]);
    }

    public function test_database_seeder_preserves_existing_superadmin_and_does_not_reset_password()
    {
        // Given: an existing Superadmin with a non-default password
        $user = User::factory()->create([
            'username' => 'admin.real',
            'role' => 'Superadmin',
            'password' => Hash::make('my_strong_password_123'),
        ]);

        $originalHash = $user->password;

        // When: seeder runs
        $this->seed(DatabaseSeeder::class);

        // Then: the user is untouched
        $user->refresh();
        $this->assertEquals($originalHash, $user->password);
        $this->assertEquals('Superadmin', $user->role);
        $this->assertEquals('admin.real', $user->username);
    }

    public function test_database_seeder_seeds_reference_data()
    {
        $this->seed(DatabaseSeeder::class);

        // OfficeActivityInventoryCodeSeeder inserts some baseline catalog data.
        // We will just verify that the catalog is not empty.
        $this->assertDatabaseCount('kategori_barang', KategoriBarang::count());
        $this->assertTrue(KategoriBarang::count() > 0, 'KategoriBarang should be seeded');
    }
}
