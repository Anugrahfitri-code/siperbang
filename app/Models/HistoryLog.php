<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'actor',
        'action',
        'details',
        'ip_address',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Automatically capture IP address on every log creation
     * so individual controllers don't need to pass it explicitly.
     */
    protected static function booted(): void
    {
        static::creating(function (self $log) {
            if (empty($log->ip_address)) {
                $log->ip_address = request()?->ip();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
