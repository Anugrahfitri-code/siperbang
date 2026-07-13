<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriBarang extends Model
{
    use HasFactory;

    protected $table = 'kategori_barang';
    protected $guarded = [];

    public function kodePersediaan()
    {
        return $this->hasMany(KodePersediaan::class, 'kategori_barang_id');
    }
}
