<?php

use App\Models\HistoryLog;

$app = require dirname(__DIR__, 2).'/bootstrap.php';

$logs = HistoryLog::where('details', 'like', '%Anugrahfitri%')->get();
foreach ($logs as $log) {
    $log->details = str_replace('Anugrahfitri', 'anugrah', $log->details);
    $log->save();
    echo "Fixed log {$log->id}\n";
}
