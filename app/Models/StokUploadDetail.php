<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokUploadDetail extends Model
{
    use HasFactory;

    protected $table = 'stok_upload_details';
    protected $guarded = [];

    protected $casts = [
        'qty' => 'integer',
        'price_unit' => 'decimal:2',
        'price_unit_taxed' => 'decimal:2',
        'total_excel' => 'decimal:2',
        'total_calculated' => 'decimal:2',
        'is_taxed' => 'boolean',
        'is_duplicate' => 'boolean',
    ];

    public function stokUpload()
    {
        return $this->belongsTo(StokUpload::class, 'stok_upload_id');
    }
}
