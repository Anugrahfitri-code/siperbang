<?php

declare(strict_types=1);

$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');
$handle = curl_init($baseUrl.'/api/receipt-documents');

if ($handle === false) {
    fwrite(STDERR, "Gagal membuat sesi cURL.\n");
    exit(1);
}

curl_setopt_array($handle, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'X-CSRF-TOKEN: test',
    ],
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($handle);
$statusCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
$error = curl_error($handle);
curl_close($handle);

if ($response === false) {
    fwrite(STDERR, "Request gagal: {$error}\n");
    exit(1);
}

fwrite(STDOUT, "HTTP {$statusCode}\n{$response}\n");
