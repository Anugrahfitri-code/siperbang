<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokUpload extends Model
{
    use HasFactory;

    protected $table = 'stok_uploads';
    protected $guarded = [];

    protected $casts = [
        'upload_date' => 'datetime',
        'sheets_count' => 'integer',
        'rows_count' => 'integer',
        'valid_rows_count' => 'integer',
        'error_rows_count' => 'integer',
        'rejected_rows_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details()
    {
        return $this->hasMany(StokUploadDetail::class, 'stok_upload_id');
    }

    public function histories()
    {
        return $this->hasMany(StockHistory::class, 'stok_upload_id');
    }
}
