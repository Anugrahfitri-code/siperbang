<?php

use App\Models\HistoryLog;
use App\Models\User;

$app = require dirname(__DIR__, 2).'/bootstrap.php';

$logs = HistoryLog::where(function ($q) {
    $q->where('details', 'like', '%Mengajukan permintaan barang (BON:%')
        ->orWhere('details', 'like', '%Kirim Pengajuan BON berhasil%');
})->get();

foreach ($logs as $log) {
    // skip if already has "atas nama"
    if (strpos($log->details, 'atas nama') !== false) {
        continue;
    }

    // Find the BON number from the details
    $bonNo = null;
    if (preg_match('/BON\/[0-9]{4}\/[0-9]{2}\/[0-9]{2}\/[0-9]+/', $log->details, $matches)) {
        $bonNo = $matches[0];
    }
    // If not found in regex, maybe it's the second format?
    // Wait, "Kirim Pengajuan BON berhasil. 1 jenis barang diminta." doesn't have BON number in the string.
    // If we can't find BON number, we can look at the user_id of the log.

    $targetUser = $log->user_id ? User::find($log->user_id) : null;

    // Check if the actor is an admin and the target user is different
    $actorIsAdmin = stripos($log->actor, 'admin') !== false;
    if ($actorIsAdmin && $targetUser && $log->actor !== $targetUser->name) {
        // It was submitted on behalf of targetUser
        $log->update([
            'details' => $log->details." (Diajukan atas nama {$targetUser->name} sebagai Ketua Tim)",
        ]);
        echo "Updated log {$log->id}\n";
    }
}
