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
        'qty'              => 'integer',
        'price_unit'       => 'decimal:2',
        'price_unit_taxed' => 'decimal:2',
        'total_excel'      => 'decimal:2',
        'total_calculated' => 'decimal:2',
        'is_taxed'         => 'boolean',
        'is_duplicate'     => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────

    public function stokUpload()
    {
        return $this->belongsTo(StokUpload::class, 'stok_upload_id');
    }

    // ── Helpers ──────────────────────────────────────────────

    /** Parse the pipe-separated notes_error into a structured array */
    public function parsedErrors(): array
    {
        if (empty($this->notes_error)) {
            return [];
        }

        return array_map(fn ($e) => trim($e), explode('|', $this->notes_error));
    }

    /** Map a simple error message to a human-readable column label */
    public function errorColumnLabel(): ?string
    {
        if ($this->error_column) {
            return match (strtoupper($this->error_column)) {
                'B' => 'Kode Persediaan',
                'C' => 'Nama Barang',
                'D' => 'Jumlah (Qty)',
                'E' => 'Satuan',
                'F' => 'Harga Satuan',
                'G' => 'Total / Harga Setelah Pajak',
                'H' => 'Total Excel',
                'I' => 'Pajak',
                default => $this->error_column,
            };
        }
        return null;
    }

    /** Suggest a fix message based on notes_error content */
    public function fixSuggestion(): ?string
    {
        if (empty($this->notes_error)) {
            return null;
        }

        $note = strtolower($this->notes_error);

        if (str_contains($note, 'kode persediaan tidak cocok')) {
            return $this->suggested_kode_persediaan
                ? "Gunakan kode saran sistem: {$this->suggested_kode_persediaan}"
                : 'Pilih kode yang sesuai dari daftar master kode persediaan.';
        }
        if (str_contains($note, 'kode persediaan wajib')) {
            return 'Isi kolom B dengan kode persediaan yang valid.';
        }
        if (str_contains($note, 'nama barang')) {
            return 'Isi kolom C dengan nama barang yang lengkap.';
        }
        if (str_contains($note, 'jumlah')) {
            return 'Isi kolom D dengan angka lebih dari 0.';
        }
        if (str_contains($note, 'satuan')) {
            return 'Isi kolom E dengan satuan barang, misal: Rim, Buah, Kotak.';
        }
        if (str_contains($note, 'harga')) {
            return 'Isi kolom F dengan harga satuan lebih dari 0.';
        }
        if (str_contains($note, 'total')) {
            return 'Total di Excel tidak sesuai perhitungan sistem. Periksa formula atau nilai kolom qty & harga.';
        }

        return 'Perbaiki data pada kolom yang ditandai dan simpan ulang.';
    }
}
