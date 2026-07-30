<?php

$app = require dirname(__DIR__, 2) . '/bootstrap.php';

foreach(\App\Models\BonHeader::all() as $b) {
    $u = \App\Models\User::where('name', $b->requester)->first();
    if ($u && $b->user_id !== $u->id) {
        $b->update(['user_id' => $u->id]);
        \App\Models\ItemRequest::where('bon_header_id', $b->id)->update(['user_id' => $u->id]);
        echo "Updated BON {$b->bon_no} to user {$u->name}\n";
    }
}
