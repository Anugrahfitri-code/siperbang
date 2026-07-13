<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    use HasFactory;

    protected $table = 'stok_histories';
    protected $guarded = [];

    protected $casts = [
        'qty_change' => 'integer',
        'qty_before' => 'integer',
        'qty_after' => 'integer',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'stock_item_id');
    }

    public function stokUpload()
    {
        return $this->belongsTo(StokUpload::class, 'stok_upload_id');
    }
}
