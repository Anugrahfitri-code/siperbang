<?php
$items = DB::table('stock_items')->whereRaw("unit ~ '^[0-9]+(\.[0-9]+)?$'")->get();
$count = 0;
foreach ($items as $item) {
    $detail = DB::table('stok_upload_details')
        ->where('nama_barang', $item->name)
        ->whereRaw("unit !~ '^[0-9]+(\.[0-9]+)?$'")
        ->orderBy('stok_upload_id', 'desc')
        ->first();
    if ($detail) {
        DB::table('stock_items')->where('id', $item->id)->update(['unit' => $detail->unit]);
        echo "Berhasil memperbaiki {$item->name}: {$item->unit} -> {$detail->unit}\n";
        $count++;
    }
}
echo "Total {$count} barang diperbaiki.\n";
