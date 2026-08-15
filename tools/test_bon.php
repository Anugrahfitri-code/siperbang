<?php

use App\Models\BonHeader;
use Illuminate\Contracts\Http\Kernel;

require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$datePrefix = now()->format('Y/m/d');
$prefix = 'BON/'.$datePrefix.'/';

$lastBon = BonHeader::where('bon_no', 'like', $prefix.'%')
    ->orderBy('bon_no', 'desc')
    ->first();

$nextNum = 1;
if ($lastBon) {
    $lastNumStr = substr($lastBon->bon_no, strrpos($lastBon->bon_no, '/') + 1);
    if (is_numeric($lastNumStr)) {
        $nextNum = intval($lastNumStr) + 1;
    }
}
$bonNo = $prefix.str_pad($nextNum, 3, '0', STR_PAD_LEFT);
echo 'Generated BON: '.$bonNo."\n";
