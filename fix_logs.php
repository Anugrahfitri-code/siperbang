<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach(\App\Models\HistoryLog::whereNull('user_id')->get() as $log) {
    // try to find the BON number from the details
    if (preg_match('/BON\/[0-9]{4}\/[0-9]{2}\/[0-9]{2}\/[0-9]+/', $log->details, $matches)) {
        $bonNo = $matches[0];
        $bon = \App\Models\BonHeader::where('bon_no', $bonNo)->first();
        if ($bon && $bon->user_id) {
            $log->update(['user_id' => $bon->user_id]);
            echo "Updated Log {$log->id} for BON {$bonNo} to user_id {$bon->user_id}\n";
        }
    }
}
