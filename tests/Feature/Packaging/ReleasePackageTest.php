<?php

namespace Tests\Feature\Packaging;

use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class ReleasePackageTest extends TestCase
{
    private string $tempZip;

    private string $sentinelUntracked;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempZip = sys_get_temp_dir().DIRECTORY_SEPARATOR.'test_release_'.uniqid().'.zip';
        $this->sentinelUntracked = base_path('local-untracked-release-sentinel.txt');

        // Buat local untracked file
        File::put($this->sentinelUntracked, 'RELEASE_TEST_UNTRACKED_SENTINEL');

        // Jika public/hot ada, rename sementara agar tidak block
        if (File::exists(public_path('hot'))) {
            File::move(public_path('hot'), public_path('hot.bak'));
        }
    }

    protected function tearDown(): void
    {
        if (File::exists($this->tempZip)) {
            File::delete($this->tempZip);
        }

        if (File::exists($this->sentinelUntracked)) {
            File::delete($this->sentinelUntracked);
        }

        if (File::exists(public_path('hot.bak'))) {
            File::move(public_path('hot.bak'), public_path('hot'));
        }

        parent::tearDown();
    }

    private function generatePackage(): void
    {
        $script = base_path('scripts/package-release.php');
        $cmd = sprintf('php %s %s', escapeshellarg($script), escapeshellarg($this->tempZip));

        exec($cmd, $output, $code);
        $this->assertEquals(0, $code, 'Packaging failed: '.implode("\n", $output));
        $this->assertFileExists($this->tempZip);
    }

    public function test_default_release_contains_required_assets_and_excludes_sensitive_files(): void
    {
        $this->generatePackage();

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($this->tempZip));

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = $zip->getNameIndex($i);
        }

        $entriesCollection = collect($entries);

        // ASSERT INCLUSIONS
        $requiredAssets = [
            'siperbang/.env.example',
            'siperbang/ocr-service/.env.example',
            'siperbang/composer.json',
            'siperbang/composer.lock',
            'siperbang/package.json',
            'siperbang/package-lock.json',
            'siperbang/docker-compose.yml',
            'siperbang/ocr-service/Dockerfile',
            'siperbang/ocr-service/requirements.txt',
            'siperbang/resources/templates/belanja-persediaan.xlsx',
            'siperbang/public/templates/Template Excel.xlsx',
            'siperbang/scripts/ocr-smoke-test.sh',
            'siperbang/ocr-service/tests/fixtures/synthetic-smoke-receipt.png',
        ];

        foreach ($requiredAssets as $asset) {
            $this->assertContains($asset, $entries, "Asset {$asset} missing from release.");
        }

        // ASSERT EXCLUSIONS
        $this->assertNotContains('siperbang/local-untracked-release-sentinel.txt', $entries);

        // Historical files must not exist
        $historicalExclusions = [
            'siperbang/opencode.json',
            'siperbang/public/templates/Belanja Persediaan 2026.xlsx',
            'siperbang/ocr-service/tests/fixtures/receipt-new-agung.pdf',
            'siperbang/ocr-service/tests/fixtures/receipt-nirmana-aqsha.pdf',
            'siperbang/ocr-service/tests/fixtures/receipt-pos-kcp-pettarani.pdf',
        ];
        foreach ($historicalExclusions as $asset) {
            $this->assertNotContains($asset, $entries, "Historical asset {$asset} must not be in release.");
        }

        // Verify .env is explicitly excluded but .env.example is allowed (which is asserted above)
        $this->assertNotContains('siperbang/.env', $entries, '.env must not be in release.');
        $this->assertNotContains('siperbang/ocr-service/.env', $entries, 'ocr-service/.env must not be in release.');

        // Verify no .git contents
        foreach ($entries as $entry) {
            $this->assertStringNotContainsString('.git/', $entry);
            $this->assertStringNotContainsString('siperbang/vendor/', $entry);
            $this->assertStringNotContainsString('siperbang/node_modules/', $entry);
        }

        $zip->close();
    }
}
