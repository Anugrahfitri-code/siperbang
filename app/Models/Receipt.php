<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_taxed' => 'boolean',
        'is_verified' => 'boolean',
        'date' => 'date',
        'bast_date' => 'date',
        'tax_rate' => 'float',
        'subtotal' => 'float',
        'tax_amount' => 'float',
        'total' => 'float',
    ];

    public function items()
    {
        return $this->hasMany(ReceiptItem::class);
    }
}
