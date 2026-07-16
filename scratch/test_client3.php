<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $client = new \App\Services\Ocr\OcrServiceClient();
    $result = $client->processReceipt(__DIR__ . '/../ocr-test/260212 New Agung 80.000.pdf', '260212 New Agung 80.000.pdf');
    echo "Direct Process Result: " . json_encode($result['success']) . "\n";
} catch (\Exception $e) {
    echo "Direct Process Error: " . $e->getMessage() . "\n";
}
