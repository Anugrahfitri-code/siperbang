<?php

namespace Database\Seeders;

use App\Models\KategoriBarang;
use App\Models\KodePersediaan;
use Illuminate\Database\Seeder;

class KategoriDanKodePersediaanSeeder extends Seeder
{
    public function run(): void
    {
        $atk = KategoriBarang::create(['nama' => 'Alat Tulis Kantor (ATK)']);
        $kebersihan = KategoriBarang::create(['nama' => 'Alat/Bahan Kebersihan']);
        $komputer = KategoriBarang::create(['nama' => 'Peralatan Komputer / Elektronik']);
        $lain = KategoriBarang::create(['nama' => 'Lain-lain']);

        $items = [
            // ATK
            ['kategori_barang_id' => $atk->id, 'kode' => '1010301001', 'nama_barang' => 'Pulpen'],
            ['kategori_barang_id' => $atk->id, 'kode' => '1010301003', 'nama_barang' => 'Penjepit Kertas / Paper Clip Rose Gold'],
            ['kategori_barang_id' => $atk->id, 'kode' => '1010301005', 'nama_barang' => 'Buku Folio Paperline 200 Lembar'],
            ['kategori_barang_id' => $atk->id, 'kode' => '1010301006', 'nama_barang' => 'Map Komdigi 2026'],
            ['kategori_barang_id' => $atk->id, 'kode' => '1010301008', 'nama_barang' => 'Gunting'],
            ['kategori_barang_id' => $atk->id, 'kode' => '1010301010', 'nama_barang' => 'Lakban / Tape'],
            ['kategori_barang_id' => $atk->id, 'kode' => '1010302001', 'nama_barang' => 'Kertas A4'],

            // Kebersihan
            ['kategori_barang_id' => $kebersihan->id, 'kode' => '1010305002', 'nama_barang' => 'Tissue & Lap Pembersih'],
            ['kategori_barang_id' => $kebersihan->id, 'kode' => '1010305004', 'nama_barang' => 'Kantong Sampah'],
            ['kategori_barang_id' => $kebersihan->id, 'kode' => '1010305008', 'nama_barang' => 'Alat / Bahan Pembersih Cair'],
            ['kategori_barang_id' => $kebersihan->id, 'kode' => '1010305009', 'nama_barang' => 'Gelas Kertas'],

            // Komputer
            ['kategori_barang_id' => $komputer->id, 'kode' => '1010304006', 'nama_barang' => 'Flashdisk'],
            ['kategori_barang_id' => $komputer->id, 'kode' => '1010304010', 'nama_barang' => 'Mouse USB/Wireless'],
            ['kategori_barang_id' => $komputer->id, 'kode' => '1010306010', 'nama_barang' => 'Baterai'],

            // Lain-lain
            ['kategori_barang_id' => $lain->id, 'kode' => '1010399999', 'nama_barang' => 'Barang Lain-lain'],
        ];

        foreach ($items as $item) {
            KodePersediaan::create($item);
        }
    }
}
