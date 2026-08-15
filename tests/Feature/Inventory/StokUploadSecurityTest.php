<?php

namespace Tests\Feature\Inventory;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StokUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'role' => 'Petugas Persediaan',
        ]);
        Storage::fake('local');
    }

    public function test_valid_excel_upload_accepted_and_stored_privately()
    {
        $file = UploadedFile::fake()->create('data.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($this->user)->post('/stok-upload', [
            'file_excel' => $file,
        ]);

        $response->assertStatus(302);
    }

    public function test_invalid_mime_rejected()
    {
        $file = UploadedFile::fake()->create('script.php', 100, 'text/x-php');

        $response = $this->actingAs($this->user)->post('/stok-upload', [
            'file_excel' => $file,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('file_excel');
    }

    public function test_oversized_rejected()
    {
        // 11MB file
        $file = UploadedFile::fake()->create('large.xlsx', 11000, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($this->user)->post('/stok-upload', [
            'file_excel' => $file,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('file_excel');
    }
}
