<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'actor',
        'action',
        'details',
    ];

    public function user(){
        
    return $this->belongsTo(User::class, 'actor', 'name'); // Menghubungkan log dengan data user
}
}
