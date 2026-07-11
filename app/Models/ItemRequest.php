<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'qty_requested' => 'integer',
        'qty_available' => 'integer',
        'qty_fulfilled' => 'integer',
        'date' => 'date',
        'last_updated' => 'date',
    ];
}
