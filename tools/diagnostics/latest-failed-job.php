<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$job = Illuminate\Support\Facades\DB::table('failed_jobs')
    ->orderByDesc('id')
    ->first();

if ($job === null) {
    fwrite(STDOUT, "Tidak ada failed job.\n");
    exit(0);
}

fwrite(STDOUT, (string) $job->exception . PHP_EOL);
