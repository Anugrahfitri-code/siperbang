<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteBrandingVersion extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'label',
        'settings',
        'status',
        'effective_from',
        'effective_until',
        'notes',
        'created_by',
        'published_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
