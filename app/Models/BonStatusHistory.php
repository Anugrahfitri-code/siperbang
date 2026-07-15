<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'bon_status_histories';
    protected $guarded = [];

    public function header()
    {
        return $this->belongsTo(BonHeader::class, 'bon_header_id');
    }
}
