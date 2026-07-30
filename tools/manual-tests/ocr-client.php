<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$file = $argv[1] ?? null;

if ($file === null || ! is_file($file)) {
    fwrite(STDERR, "Gunakan: php tools/manual-tests/ocr-client.php <file.pdf>\n");
    exit(1);
}

try {
    $client = app(App\Services\Ocr\OcrServiceClient::class);
    $result = $client->processReceipt($file, basename($file));
    fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
