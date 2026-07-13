<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KodePersediaan extends Model
{
    use HasFactory;

    protected $table = 'kode_persediaan';
    protected $guarded = [];

    public function kategoriBarang()
    {
        return $this->belongsTo(KategoriBarang::class, 'kategori_barang_id');
    }
}
