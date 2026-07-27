# Perbaikan Kategori Master Barang 1.01.03

Perbaikan ini membatasi master barang, filter kategori, pencarian stok, unggah Excel, dan kode kuitansi pada kelompok resmi:

`1.01.03 - ALAT/BAHAN UNTUK KEGIATAN KANTOR`

## Hasil perbaikan

- Filter menampilkan 17 subkategori resmi berdasarkan urutan kode `1.01.03.01` sampai `1.01.03.99`.
- Filter tidak lagi mengambil seluruh isi tabel `kategori_barang`, sehingga kategori di luar 1.01.03 tidak muncul.
- Alias lama seperti `Alat Tulis Kantor (ATK)`, `Alat/Bahan Kebersihan`, `Peralatan Komputer / Elektronik`, dan `Lain-lain` dinormalisasi ke kategori resmi.
- Kategori barang selalu ditentukan dari kode persediaan. Pengguna tidak dapat membuat pasangan kode dan kategori yang berbeda.
- Seeder utama hanya menjalankan master 1.01.03 dan aman dijalankan berulang.
- Master resmi berisi 111 kode barang dari dokumen sumber.

## Penerapan pada database yang sudah ada

Jalankan dari direktori proyek:

```bash
php artisan migrate --force
php artisan db:seed --class=OfficeActivityInventoryCodeSeeder --force
php artisan optimize:clear
```

## Penerapan pada instalasi baru

```bash
php artisan migrate --seed
```

Seeder kompatibilitas `KategoriDanKodePersediaanSeeder` tetap tersedia, tetapi sekarang meneruskan proses ke `OfficeActivityInventoryCodeSeeder` agar dokumentasi atau skrip lama tidak membuat kategori ganda.
