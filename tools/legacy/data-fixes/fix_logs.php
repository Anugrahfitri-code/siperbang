<?php

use App\Models\BonHeader;
use App\Models\HistoryLog;

$app = require dirname(__DIR__, 2).'/bootstrap.php';

foreach (HistoryLog::whereNull('user_id')->get() as $log) {
    // try to find the BON number from the details
    if (preg_match('/BON\/[0-9]{4}\/[0-9]{2}\/[0-9]{2}\/[0-9]+/', $log->details, $matches)) {
        $bonNo = $matches[0];
        $bon = BonHeader::where('bon_no', $bonNo)->first();
        if ($bon && $bon->user_id) {
            $log->update(['user_id' => $bon->user_id]);
            echo "Updated Log {$log->id} for BON {$bonNo} to user_id {$bon->user_id}\n";
        }
    }
}
