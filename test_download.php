<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/stok-upload/template', 'GET');
auth()->loginUsingId(1);
$response = $kernel->handle($request);

if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
    echo "Downloading file: " . $response->getFile()->getPathname() . "\n";
} else {
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response content: " . substr($response->getContent(), 0, 100) . "\n";
}
