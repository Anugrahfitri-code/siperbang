<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);

$autoload = $projectRoot . '/vendor/autoload.php';
$bootstrap = $projectRoot . '/bootstrap/app.php';

if (! is_file($autoload)) {
    fwrite(STDERR, "Dependensi Composer belum terpasang. Jalankan composer install.\n");
    exit(1);
}

require $autoload;

$app = require $bootstrap;
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

return $app;
