# Perbaikan Modul Excel & Kode Persediaan

Tanggal audit dan perbaikan: 27 Juli 2026

## Ruang lingkup pemeriksaan

Pemeriksaan dilakukan pada alur lengkap modul Excel dan Kode Persediaan, mulai dari tampilan React, formulir upload, rute Laravel, validasi request, controller, layanan pembacaan Excel, template Excel, halaman stepper, riwayat upload, hingga navigasi kembali ke modul utama.

Struktur proyek yang diperiksa mencakup 277 file. Fokus teknis utama berada pada:

- `resources/js/features/inventory-upload/components/StockManagement.tsx`
- `resources/js/App.tsx`
- `routes/web.php`
- `app/Http/Requests/Inventory/UploadStokExcelRequest.php`
- `app/Http/Controllers/Web/StokUploadController.php`
- `app/Services/Inventory/ExcelPersediaanImportService.php`
- `resources/views/stok-upload/*.blade.php`
- `public/templates/Belanja Persediaan 2026.xlsx`

## Masalah utama yang ditemukan

### 1. Upload pada halaman React hanya simulasi

Komponen lama tidak mengirim file ke endpoint Laravel. Proses upload hanya menjalankan jeda melalui JavaScript dan membuat data contoh. Karena itu, logika backend yang sudah tersedia tidak pernah dipanggil dari halaman utama.

### 2. Tata letak header dan navigasi saling bertabrakan

Judul, tab navigasi, indikator stok, dan tombol aksi ditempatkan pada area yang sama tanpa pembagian grid yang jelas. Pada ukuran layar tertentu, elemen terlihat bertumpuk dan tidak konsisten.

### 3. Unduhan template memakai lokasi komputer pengembang

Controller mengarah ke lokasi absolut `D:/Belanja Persediaan 2026.xlsx`. Lokasi tersebut hanya berlaku pada komputer tertentu dan gagal pada server lain.

### 4. Riwayat upload tidak mempunyai jalur kembali yang jelas

Halaman riwayat menggunakan tampilan Blade terpisah dari halaman React. Pengguna tidak mendapat tombol kembali yang tegas ke menu Excel & Kode Persediaan.

### 5. Nama file tersimpan berpotensi bentrok

File sebelumnya disimpan menggunakan nama asli. Dua unggahan dengan nama yang sama dapat saling mengganggu atau menyulitkan pelacakan.

## Perbaikan yang diterapkan

### Halaman utama Excel & Kode Persediaan

- Mengganti upload simulasi menjadi form multipart yang benar-benar mengirim `file_excel` ke `POST /stok-upload`.
- Menambahkan token CSRF dari meta halaman Laravel.
- Menambahkan pemilihan file, drag and drop, validasi ekstensi, validasi file kosong, dan batas 10 MB.
- Menambahkan status file terpilih, tombol ganti file, tombol hapus pilihan, serta status pemrosesan.
- Menonaktifkan tombol proses sebelum file valid dipilih.
- Menyusun ulang header, tab modul, kartu upload, alur pemrosesan, panduan format, dan tabel stok aktif.
- Menambahkan pencarian dan filter kategori pada daftar stok.
- Menghubungkan tombol ke URL backend yang nyata untuk riwayat upload, master barang, dan template.

### Backend upload

- Mengizinkan Petugas Persediaan dan Superadmin melalui request authorization.
- Menyimpan file dengan nama unik berbasis timestamp dan UUID.
- Menghapus file sementara ketika proses import gagal karena exception umum.
- Mengarahkan kesalahan validasi ke halaman upload server agar rincian masalah dapat ditampilkan.
- Mengganti sumber template menjadi `public/templates/Belanja Persediaan 2026.xlsx`.

### Riwayat upload

- Menambahkan tombol `Kembali` yang mengarah ke `/?module=excel`.
- Menambahkan tombol `Upload Baru` yang kembali ke modul React yang sama.
- Menambahkan tab konsisten untuk Upload Excel, Riwayat Upload, dan Master Barang.
- Menata ulang header, ringkasan jumlah batch, tabel, status, dan tombol aksi.
- Mempertahankan seluruh logika status, verifikasi, finalisasi, penghapusan, dan pembatalan yang sudah ada.

### Pemulihan tab modul

`App.tsx` sekarang membaca parameter `?module=excel`. Saat pengguna kembali dari halaman Blade, sistem langsung membuka menu Excel & Kode Persediaan untuk Petugas Persediaan maupun Superadmin. Parameter kemudian dibersihkan dari URL tanpa memuat ulang halaman.

## Verifikasi teknis

Pemeriksaan yang telah dijalankan:

- Sintaks komponen `StockManagement.tsx` dan `App.tsx` berhasil diparsing oleh TypeScript.
- Seluruh 67 file PHP pada area aplikasi, rute, dan migrasi yang diperiksa lulus `php -l`.
- Keseimbangan directive Blade pada halaman upload dan riwayat diperiksa.
- Referensi lokasi absolut `D:/Belanja Persediaan 2026.xlsx` sudah dihapus.
- Kode upload simulasi sudah dihapus dari komponen utama.
- Template Excel berhasil dibaca. Template terdiri dari 8 sheet dan mendukung struktur tanpa pajak serta struktur dengan pajak.

## Catatan instalasi

Arsip proyek tidak menyertakan folder `vendor` dan `node_modules`. Jalankan perintah berikut pada lingkungan pengembangan:

```bash
composer install
npm install
php artisan migrate
npm run build
```

Untuk pengembangan lokal:

```bash
php artisan serve
npm run dev
```

Pastikan `.env` telah dikonfigurasi dan jalankan:

```bash
php artisan key:generate
php artisan storage:link
```

## Catatan pengujian

Build Vite penuh tidak dapat dijalankan di sandbox karena paket npm tidak tersedia pada cache lokal dan instalasi jaringan tidak berhasil. Pemeriksaan sintaks TypeScript dan PHP telah lulus. Pengujian integrasi akhir tetap perlu dijalankan pada komputer proyek setelah `composer install` dan `npm install` selesai.
