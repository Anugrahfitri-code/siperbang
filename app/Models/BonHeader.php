<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonHeader extends Model
{
    use HasFactory;

    protected $table = 'bon_headers';

    protected $guarded = [];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'last_updated' => 'date:Y-m-d',
    ];

    public function items()
    {
        return $this->hasMany(ItemRequest::class, 'bon_header_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(BonStatusHistory::class, 'bon_header_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
