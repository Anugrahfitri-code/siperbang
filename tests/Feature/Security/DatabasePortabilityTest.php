<?php

namespace Tests\Feature\Security;

use App\Models\Barang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabasePortabilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'role' => 'Superadmin',
        ]);
    }

    public function test_barang_search_is_case_insensitive_and_portable()
    {
        Barang::create([
            'code' => '1.01.03.01.001',
            'name' => 'Kertas HVS A4',
            'category' => 'Alat Tulis Kantor',
            'unit' => 'Rim',
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        // Exact match
        $response = $this->getJson('/master-barang/search?query='.urlencode('Kertas HVS A4'));
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['nama_barang' => 'Kertas HVS A4']);

        // Lowercase search
        $response = $this->getJson('/master-barang/search?query='.urlencode('kertas hvs a4'));
        $response->assertStatus(200);
        $response->assertJsonCount(1);

        // Uppercase search
        $response = $this->getJson('/master-barang/search?query='.urlencode('KERTAS HVS A4'));
        $response->assertStatus(200);
        $response->assertJsonCount(1);

        // Mixed case search
        $response = $this->getJson('/master-barang/search?query='.urlencode('KeRtAs hVs A4'));
        $response->assertStatus(200);
        $response->assertJsonCount(1);

        // No match
        $response = $this->getJson('/master-barang/search?query='.urlencode('Buku'));
        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    public function test_wildcard_characters_are_escaped()
    {
        Barang::create([
            'code' => '1.01.03.01.999',
            'name' => 'Produk 100% Asli',
            'category' => 'Lain-lain',
            'unit' => 'Pcs',
            'is_active' => true,
        ]);

        Barang::create([
            'code' => '1.01.03.01.998',
            'name' => 'Produk 100 Asli', // Tanpa persen
            'category' => 'Lain-lain',
            'unit' => 'Pcs',
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        // Search for literal %
        $response = $this->getJson('/master-barang/search?query='.urlencode('100%'));
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['nama_barang' => 'Produk 100% Asli']);

        Barang::create([
            'code' => '1.01.03.01.997',
            'name' => 'Buku C\D',
            'category' => 'Lain-lain',
            'unit' => 'Pcs',
            'is_active' => true,
        ]);
        Barang::create([
            'code' => '1.01.03.01.996',
            'name' => 'Buku C!D',
            'category' => 'Lain-lain',
            'unit' => 'Pcs',
            'is_active' => true,
        ]);

        // Search for literal \
        $response = $this->getJson('/master-barang/search?query='.urlencode('C\D'));
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['nama_barang' => 'Buku C\D']);

        // Search for literal !
        $response = $this->getJson('/master-barang/search?query='.urlencode('C!D'));
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['nama_barang' => 'Buku C!D']);
    }

    public function test_sql_injection_attempts_are_bound()
    {
        Barang::create([
            'code' => '1.01.03.01.002',
            'name' => 'Kertas Biasa',
            'category' => 'Alat Tulis Kantor',
            'unit' => 'Rim',
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        // This attempts to inject an OR clause and comment out the rest
        $maliciousPayload = "%' OR 1=1 --";
        $response = $this->getJson('/master-barang/search?query='.urlencode($maliciousPayload));

        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    public function test_stock_search_is_portable()
    {
        Barang::create([
            'code' => '1.01.03.01.002',
            'name' => 'Bolpoin Hitam',
            'category' => 'Alat Tulis Kantor',
            'unit' => 'Pack',
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        // Test lowercase
        $response = $this->getJson('/api/stocks?search=bolpoin hitam');
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Bolpoin Hitam']);

        // Test uppercase
        $response = $this->getJson('/api/stocks?search=BOLPOIN HITAM');
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Bolpoin Hitam']);
    }
}
