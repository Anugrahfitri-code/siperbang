<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Fake a receipt document
$docId = Illuminate\Support\Facades\DB::table('receipt_documents')->insertGetId([
    'original_filename' => '260212 New Agung 80.000.pdf',
    'file_path' => 'ocr-test/260212 New Agung 80.000.pdf',
    'status' => 'queued',
    'uploaded_by' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "Created document ID: $docId\n";

try {
    $client = new \App\Services\Ocr\OcrServiceClient();
    $result = $client->processReceipt(__DIR__ . '/ocr-test/260212 New Agung 80.000.pdf', '260212 New Agung 80.000.pdf');
    echo "Direct Process Result: " . json_encode($result['success']) . "\n";
} catch (\Exception $e) {
    echo "Direct Process Error: " . $e->getMessage() . "\n";
}
