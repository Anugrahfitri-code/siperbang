<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Distribution extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'qty_distributed' => 'integer',
        'distributed_at'  => 'date',
    ];

    public function itemRequest()
    {
        return $this->belongsTo(ItemRequest::class);
    }

    public function stockItem()
    {
        return $this->belongsTo(StockItem::class);
    }
}
