<?php

use App\Models\BonHeader;
use App\Models\ItemRequest;
use Illuminate\Contracts\Http\Kernel;

require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$bons = BonHeader::where('bon_no', 'like', 'BON/2026/08/03/%')->get();

foreach ($bons as $bon) {
    ItemRequest::where('bon_header_id', $bon->id)->update(['bon_no' => $bon->bon_no]);
    echo 'Updated ItemRequest for '.$bon->bon_no."\n";
}
echo "Done.\n";
