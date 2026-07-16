<?php
require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $response = Http::acceptJson()
        ->withHeaders([
            'X-Service-Token' => 'token-rahasia-yang-sama',
        ])
        ->connectTimeout(3)
        ->timeout(25)
        ->attach(
            'document',
            file_get_contents(__DIR__ . '/ocr-test/260212 New Agung 80.000.pdf'),
            'test.pdf'
        )
        ->post('http://127.0.0.1:8001/internal/v1/receipt-ocr');

    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
