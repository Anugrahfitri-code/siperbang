# Laporan Perapian Struktur Proyek

Tanggal: 30 Juli 2026
Versi hasil: 3

## Status versi sebelumnya

ZIP versi 2 belum memperbaiki seluruh masalah penempatan file. Folder komponen React masih terlalu datar. Beberapa controller, view, route, service, test, dan dokumen juga belum mempunyai batas tanggung jawab yang jelas.

Versi 3 menggantikan ZIP versi 2.

## Perubahan utama

### Frontend

- Menghapus folder komponen React aktif yang datar.
- Memindahkan komponen khusus modul ke `resources/js/features/`.
- Memindahkan dialog, layout, dan branding lintas fitur ke `resources/js/shared/components/`.
- Memindahkan klien API dan tipe bersama ke `resources/js/shared/`.
- Mengelompokkan komponen Blade menjadi `feedback/` dan `navigation/`.
- Mengganti nama layout aktif menjadi `layouts/inventory.blade.php`.

### Backend

- Memisahkan controller respons JSON dan controller halaman web.
- Memisahkan method API stok upload dari controller web.
- Memisahkan route menjadi `routes/web.php` dan `routes/api.php`.
- Mengelompokkan service ke domain `Inventory`, `Receipt`, dan `Ocr`.
- Mengelompokkan exception, enum, job, request, support class, seeder, dan test berdasarkan domain.

### Source lama dan alat bantu

- Memindahkan controller, request, view, layout, dan test contoh yang tidak aktif ke `archive/legacy/laravel/`.
- Memisahkan alat diagnosis, test manual, dan patch lama ke folder yang berbeda.
- Memisahkan test, fixture, script, dan alat diagnosis layanan OCR.
- Menghapus route lama yang menunjuk ke method controller yang tidak tersedia.
- Mempertahankan alias URL lama yang aman sebagai redirect ke stepper baru.

### Aset dan dokumentasi

- Mengelompokkan aset aktif menjadi `brand/`, `landing/`, dan `team/`.
- Memindahkan aset tanpa referensi runtime ke arsip.
- Mengelompokkan dokumentasi menurut arsitektur, pengembangan, operasi, referensi, perencanaan, dan maintenance.
- Menambah audit lengkap pada `docs/development/FOLDER_AUDIT.md`.

## Hasil pemeriksaan source

| Pemeriksaan | Hasil |
|---|---|
| Sintaks PHP | 138 file diperiksa, 0 error |
| Kesesuaian namespace PSR-4 | 56 class diperiksa, 0 mismatch |
| Import class proyek | 110 import diperiksa, 0 target hilang |
| Route ke controller dan method | 57 target diperiksa, 0 target hilang |
| Referensi route bernama | 45 referensi diperiksa, 0 route hilang |
| Referensi view dan Blade component | 23 referensi diperiksa, 0 target hilang |
| Sintaks TypeScript dan TSX | 24 file diperiksa, 0 error sintaks |
| Import relatif frontend | 49 import diperiksa, 0 target hilang |
| Keterjangkauan frontend dari `main.tsx` | 24 dari 24 file aktif terhubung |
| Compile source Python | Lulus |
| Unit test parser OCR | 2 test lulus |
| UTF-8 | 273 file teks diperiksa, 0 error |
| JSON | 7 file diperiksa, seluruhnya valid |
| Gambar | 18 file diperiksa, seluruhnya valid |
| PDF fixture | 3 file diperiksa, seluruhnya valid |
| Template XLSX | 1 file diperiksa, valid sebagai arsip XLSX |
| Referensi aset publik | 14 referensi diperiksa, 0 target hilang |
| Aset aktif tanpa referensi | 0 file |
| Link Markdown lokal | 24 link diperiksa, 0 link rusak |
| Perbandingan file React yang dipindahkan | 22 file, tidak ada perubahan isi di luar import |
| Perbandingan file PHP yang dipindahkan | 22 file, tidak ada perubahan bisnis di luar namespace dan import |
| Perbandingan method controller stok upload | 21 method, isi sama |
| Perbandingan daftar endpoint | Hanya route rusak `POST /stok-upload/{id}/perbaiki` yang dihapus |

## Pemeriksaan yang belum dapat dijalankan penuh

Build Vite belum dapat dijalankan pada lingkungan audit. `npm ci` gagal karena registry internal tidak menyediakan paket `zod-3.25.76.tgz`. Kegagalan terjadi sebelum proses build dan tidak berasal dari source proyek.

Test Laravel penuh belum dapat dijalankan karena Composer dan folder `vendor/` tidak tersedia pada lingkungan audit.

Test integrasi OCR belum dapat dikoleksi karena paket `paddleocr` tidak tersedia. Unit test parser OCR tetap lulus.

Jalankan pemeriksaan runtime berikut pada komputer developer yang memiliki dependensi lengkap:

```bash
composer install
npm ci
npm run build
php artisan test
```

Layanan OCR:

```bash
cd ocr-service
python -m pip install -r requirements.txt
python -m pytest
```

## Dokumen pendukung

- Audit penempatan file: `FOLDER_AUDIT.md`
- Struktur final proyek: `PROJECT_STRUCTURE.md`
- Panduan setup: `SETUP_DEV.md`
