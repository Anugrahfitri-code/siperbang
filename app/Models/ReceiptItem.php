<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'qty' => 'integer',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    public function inventoryCodeMaster()
    {
        return $this->belongsTo(
            KodePersediaan::class,
            'inventory_code',
            'kode',
        );
    }
}
