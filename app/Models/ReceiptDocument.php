<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\ReceiptDocumentStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptDocument extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'raw_result',
        'storage_path',
        'sha256',
    ];

    protected $casts = [
        'status' =>
            ReceiptDocumentStatus::class,

        'raw_result' =>
            'array',

        'parsed_result' =>
            'array',

        'manual_draft' =>
            'array',

        'processed_at' =>
            'datetime',

        'verified_at' =>
            'datetime',

        'draft_saved_at' =>
            'datetime',

        'overall_confidence' =>
            'float',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(
            Receipt::class
        );
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by',
        );
    }
}
