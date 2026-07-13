<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'qty'          => 'integer',
        'last_updated' => 'date',
        'is_active'    => 'boolean',
    ];

    public function distributions()
    {
        return $this->hasMany(Distribution::class);
    }

    public function itemRequests()
    {
        return $this->hasMany(ItemRequest::class);
    }
}
