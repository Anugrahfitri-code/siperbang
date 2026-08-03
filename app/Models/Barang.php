<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'stock_items';

    protected $guarded = [];

    protected $casts = [
        'qty' => 'integer',
        'last_updated' => 'date:Y-m-d',
        'is_active' => 'boolean',
    ];

    public function histories()
    {
        return $this->hasMany(StockHistory::class, 'stock_item_id');
    }

    public function kategori()
    {
        return $this->belongsTo(KategoriBarang::class, 'category', 'nama');
    }

    public function kodePersediaan()
    {
        return $this->belongsTo(KodePersediaan::class, 'code', 'kode');
    }

    public function lastUpload()
    {
        return $this->belongsTo(StokUpload::class, 'last_upload_id');
    }
}
