<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8001/internal/v1/receipt-ocr');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 25);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Service-Token: token-rahasia-yang-sama']);

$cfile = new CURLFile('D:/Project/siperbang/ocr-test/260212 New Agung 80.000.pdf', 'application/pdf', 'test.pdf');
curl_setopt($ch, CURLOPT_POSTFIELDS, ['document' => $cfile]);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch) . "\n";
} else {
    echo "Response: " . $response . "\n";
}
curl_close($ch);
