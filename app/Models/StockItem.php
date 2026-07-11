<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'qty' => 'integer',
        'last_updated' => 'date',
    ];
}
