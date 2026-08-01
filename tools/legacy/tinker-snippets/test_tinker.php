<?php

use App\Jobs\Receipt\ProcessReceiptOcr;
use App\Models\ReceiptDocument;

$doc = ReceiptDocument::where('original_filename', 'like', '%260113 Satu Sama 937.142%')->first();
if (! $doc) {
    echo "Document not found\n";
    exit;
}
$doc->update(['status' => 'queued', 'error_message' => null]);
ProcessReceiptOcr::dispatchSync($doc->id);
$doc->refresh();

dump($doc->status);
dump($doc->error_message);

$result = $doc->parsed_result;
if ($result) {
    dump($result['store_name']);
    dump($result['date']);
    dump('Items count: '.count($result['items']));
    dump($result['subtotal']);
} else {
    dump('No parsed result');
}
