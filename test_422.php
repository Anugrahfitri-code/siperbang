<?php
$ch = curl_init('http://127.0.0.1:8000/api/receipt-documents');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'X-CSRF-TOKEN: test']);
$res = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo 'HTTP Code: ' . $http_code . PHP_EOL . 'Response: ' . $res . PHP_EOL;
