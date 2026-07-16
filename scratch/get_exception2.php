<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$job = Illuminate\Support\Facades\DB::table('failed_jobs')->orderBy('id', 'desc')->first();
if ($job) {
    echo $job->exception;
} else {
    echo "No failed jobs";
}
