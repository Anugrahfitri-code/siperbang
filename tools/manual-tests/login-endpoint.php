<?php

declare(strict_types=1);

$username = $argv[1] ?? null;
$password = $argv[2] ?? null;
$baseUrl = rtrim($argv[3] ?? 'http://127.0.0.1:8000', '/');

if ($username === null || $password === null) {
    fwrite(STDERR, "Gunakan: php tools/manual-tests/login-endpoint.php <username> <password> [base-url]\n");
    exit(1);
}

$payload = json_encode([
    'username' => $username,
    'password' => $password,
], JSON_THROW_ON_ERROR);

$options = [
    'http' => [
        'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
        'method' => 'POST',
        'content' => $payload,
        'ignore_errors' => true,
        'timeout' => 15,
    ],
];

$context = stream_context_create($options);
$result = file_get_contents($baseUrl . '/api/login', false, $context);
$status = $http_response_header[0] ?? 'Status tidak tersedia';

fwrite(STDOUT, $status . PHP_EOL);
fwrite(STDOUT, ($result === false ? 'Request gagal.' : $result) . PHP_EOL);
