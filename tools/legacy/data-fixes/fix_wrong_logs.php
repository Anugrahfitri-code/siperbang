<?php
$app = require dirname(__DIR__, 2) . '/bootstrap.php';

$logs = \App\Models\HistoryLog::where('details', 'like', '%(Diajukan atas nama Admin Utama sebagai Ketua Tim)')->get();
foreach ($logs as $log) {
    $log->details = str_replace('(Diajukan atas nama Admin Utama sebagai Ketua Tim)', '(Diajukan atas nama Anugrahfitri sebagai Ketua Tim)', $log->details);
    $log->user_id = 8;
    $log->save();
    echo "Fixed log {$log->id}\n";
}
