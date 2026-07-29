<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$logs = \App\Models\HistoryLog::where('details', 'like', '%Anugrahfitri%')->get();
foreach ($logs as $log) {
    $log->details = str_replace('Anugrahfitri', 'anugrah', $log->details);
    $log->save();
    echo "Fixed log {$log->id}\n";
}
