<?php

declare(strict_types=1);
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/bootstrap.php';

$jobs = DB::table('failed_jobs')
    ->orderByDesc('id')
    ->get();

if ($jobs->isEmpty()) {
    fwrite(STDOUT, "Tidak ada failed job.\n");
    exit(0);
}

foreach ($jobs as $job) {
    $preview = substr((string) $job->exception, 0, 400);
    fwrite(STDOUT, "{$job->failed_at} | {$preview}\n---\n");
}
