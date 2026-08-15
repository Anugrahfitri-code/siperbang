<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorDisclosureTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_stok_upload_does_not_leak_internal_exception()
    {
        $user = User::factory()->create(['role' => 'Petugas Persediaan']);
        $this->actingAs($user);

        // Force an exception in finalization service
        $this->mock(\App\Services\Inventory\StokFinalizationService::class, function ($mock) {
            $mock->shouldReceive('finalize')->andThrow(new \RuntimeException('SENSITIVE_INTERNAL_EXCEPTION_36_3A'));
        });

        $batch = clone \App\Models\StokUpload::create([
            'status' => 'Menunggu Finalisasi',
            'user_id' => $user->id,
            'file_name_original' => 'test.xlsx',
            'file_name_stored' => 'test/test.xlsx',
            'upload_date' => now(),
        ]);

        $response = $this->postJson("/api/stok-upload/{$batch->id}/finalisasi-api");

        $response->assertStatus(400);
        $response->assertDontSee('SENSITIVE_INTERNAL_EXCEPTION_36_3A');
        
        // Assert generic error
        $response->assertJsonPath('error', 'Terjadi kesalahan sistem saat memproses data.');
    }

    public function test_web_stok_upload_does_not_leak_internal_exception()
    {
        $user = User::factory()->create(['role' => 'Petugas Persediaan']);
        $this->actingAs($user);

        $this->mock(\App\Services\Inventory\StokFinalizationService::class, function ($mock) {
            $mock->shouldReceive('finalize')->andThrow(new \RuntimeException('SENSITIVE_INTERNAL_EXCEPTION_36_3A'));
        });

        $batch = \App\Models\StokUpload::create([
            'status' => 'Menunggu Finalisasi',
            'user_id' => $user->id,
            'file_name_original' => 'test.xlsx',
            'file_name_stored' => 'test/test.xlsx',
            'upload_date' => now(),
        ]);

        $response = $this->from('/stok-upload/riwayat')->post("/stok-upload/{$batch->id}/finalisasi");

        $response->assertRedirect('/stok-upload/riwayat');
        $response->assertSessionHas('error');
        $this->assertStringNotContainsString('SENSITIVE_INTERNAL_EXCEPTION_36_3A', session('error'));
    }
}
