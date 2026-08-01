<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;

$projectRoot = dirname(__DIR__);

$autoload = $projectRoot.'/vendor/autoload.php';
$bootstrap = $projectRoot.'/bootstrap/app.php';

if (! is_file($autoload)) {
    fwrite(STDERR, "Dependensi Composer belum terpasang. Jalankan composer install.\n");
    exit(1);
}

require $autoload;

$app = require $bootstrap;
$app->make(Kernel::class)->bootstrap();

return $app;
