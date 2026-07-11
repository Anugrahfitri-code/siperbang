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
        'price' => 'float',
        'subtotal' => 'float',
    ];

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }
}
