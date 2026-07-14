<?php
$doc = \App\Models\ReceiptDocument::find(14);
$doc->status = \App\Enums\ReceiptDocumentStatus::QUEUED;
$doc->save();
\App\Jobs\ProcessReceiptOcr::dispatchSync($doc);
echo "OCR dispatched for doc " . $doc->id . " (" . $doc->original_filename . ")\n";
echo "Status: " . $doc->fresh()->status . "\n";
echo "Parsed: " . json_encode($doc->fresh()->parsed_result) . "\n";
