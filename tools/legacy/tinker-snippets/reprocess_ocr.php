<?php

use App\Enums\Receipt\ReceiptDocumentStatus;
use App\Jobs\Receipt\ProcessReceiptOcr;
use App\Models\ReceiptDocument;

$doc = ReceiptDocument::find(14);
$doc->status = ReceiptDocumentStatus::QUEUED;
$doc->save();
ProcessReceiptOcr::dispatchSync($doc->id);
echo 'OCR dispatched for doc '.$doc->id.' ('.$doc->original_filename.")\n";
echo 'Status: '.$doc->fresh()->status."\n";
echo 'Parsed: '.json_encode($doc->fresh()->parsed_result)."\n";
