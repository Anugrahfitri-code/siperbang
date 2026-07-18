<?php
$data = json_encode(['username' => 'iwan.s', 'password' => 'password']);
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\nAccept: application/json\r\n",
        'method'  => 'POST',
        'content' => $data,
        'ignore_errors' => true,
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents('http://127.0.0.1:8000/api/login', false, $context);
echo "Status: " . $http_response_header[0] . "\n";
echo "Result: " . $result . "\n";
