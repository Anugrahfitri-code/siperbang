<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Procurement extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'qty_procured'   => 'integer',
        'unit_price'     => 'float',
        'total_price'    => 'float',
        'is_taxed'       => 'boolean',
        'tax_rate'       => 'float',
        'procurement_date' => 'date',
        'bast_date'      => 'date',
    ];

    public function itemRequest()
    {
        return $this->belongsTo(ItemRequest::class);
    }
}
