<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);

$request = Request::create('/stok-upload/template', 'GET');
auth()->loginUsingId(1);
$response = $kernel->handle($request);

if ($response instanceof BinaryFileResponse) {
    echo 'Downloading file: '.$response->getFile()->getPathname()."\n";
} else {
    echo 'Response status: '.$response->getStatusCode()."\n";
    echo 'Response content: '.substr($response->getContent(), 0, 100)."\n";
}
