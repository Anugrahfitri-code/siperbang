# SETUP_DEV.md — Panduan Onboarding Developer Baru

Dokumen ini memandu developer baru dari nol sampai bisa menjalankan SIPERBANG di lokal.

---

## Prasyarat

Install semua tools berikut sebelum mulai:

| Tool | Versi Minimum | Cara Install |
|---|---|---|
| PHP | 8.4 | https://windows.php.net/download (Windows) atau `apt install php8.4` |
| Composer | 2.x | https://getcomposer.org/download |
| Node.js | 20.x LTS | https://nodejs.org |
| Python | 3.10+ | https://www.python.org/downloads |
| Git | 2.x | https://git-scm.com |

**PHP Extensions yang harus aktif** (cek di `php.ini`):
```
extension=pdo_sqlite
extension=mbstring
extension=fileinfo
extension=zip
extension=gd
extension=curl
```

---

## Langkah 1: Clone & Setup

```bash
git clone <repo-url> siperbang
cd siperbang

# Salin env
cp .env.example .env

# Install semua dependency + migrate + build (satu perintah)
composer setup
```

Perintah `composer setup` melakukan:
1. `composer install`
2. Generate `APP_KEY`
3. Jalankan migrations
4. `npm install`
5. `npm run build`

---

## Langkah 2: Konfigurasi Environment

Buka `.env` dan sesuaikan:

```env
APP_NAME=SIPERBANG
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database — gunakan SQLite untuk dev
DB_CONNECTION=sqlite
# DB_DATABASE otomatis ke database/database.sqlite

# OCR Service (opsional untuk dev tanpa fitur OCR)
OCR_SERVICE_URL=http://127.0.0.1:8001
OCR_SERVICE_TOKEN=your-secret-token-here

# Queue — gunakan database driver untuk dev
QUEUE_CONNECTION=database
```

---

## Langkah 3: Seed Data Awal

```bash
# Seed kategori dan kode persediaan (master data wajib)
php artisan db:seed --class=KategoriDanKodePersediaanSeeder

# Seed semua (termasuk user dummy)
php artisan db:seed
```

---

## Langkah 4: Jalankan Aplikasi

```bash
# Jalankan semua service sekaligus (Laravel + Vite + Queue worker)
composer dev
```

Akses aplikasi di: http://localhost:8000

---

## Langkah 5: Setup OCR Service (Opsional)

Diperlukan hanya jika ingin menggunakan fitur upload kuitansi dengan OCR.

```bash
cd ocr-service

# Buat virtual environment
python -m venv .venv

# Aktifkan venv
.venv\Scripts\activate        # Windows PowerShell
# source .venv/bin/activate   # Linux/macOS

# Install dependencies (butuh waktu karena PaddleOCR cukup besar ~1.5GB)
pip install -r requirements.txt

# Jalankan OCR server
.\run-server.ps1
# atau langsung: uvicorn app.main:app --host 127.0.0.1 --port 8001 --reload
```

OCR service berjalan di http://127.0.0.1:8001

---

## Menjalankan Tests

```bash
# PHP Tests
composer test
# atau:
php artisan test

# Test spesifik
php artisan test tests/Feature/ReceiptDocumentTest.php

# Python OCR Tests
cd ocr-service
.\run_tests.ps1
```

---

## Akun Default

Setelah seeding, gunakan akun ini untuk login:

| Username | Password | Role |
|---|---|---|
| admin | password | Superadmin |
| petugas | password | Petugas Persediaan |
| ketua | password | Ketua Tim |

> **PENTING:** Password default adalah `password`. Segera ganti di halaman User Management setelah login pertama kali.

---

## Struktur Kode yang Perlu Dipahami Dulu

Sebagai developer baru, baca file-file ini secara berurutan:

1. `routes/web.php` — semua route ada di sini
2. `app/Http/Middleware/RoleMiddleware.php` — cara RBAC bekerja
3. `app/Models/User.php` — model user + roles
4. `app/Models/StokUpload.php` — contoh model dengan SoftDeletes + status machine
5. `app/Services/ExcelPersediaanImportService.php` — business logic terberat
6. `app/Jobs/ProcessReceiptOcr.php` — cara queue job bekerja
7. `resources/js/App.tsx` — entry point React + routing
8. `resources/js/api.ts` — semua pemanggilan API dari frontend

---

## Perintah Artisan yang Sering Dipakai

```bash
# Reset database dari nol
php artisan migrate:fresh --seed

# Cek route yang terdaftar
php artisan route:list

# Jalankan queue worker manual
php artisan queue:work --queue=ocr

# Clear semua cache
php artisan optimize:clear

# Tinker (REPL interaktif)
php artisan tinker
```

---

## Troubleshooting Umum

**Error: `SQLSTATE[HY000]: General error: 1 no such table`**
→ Jalankan `php artisan migrate`

**Error: `ilike` operator di pencarian stok**
→ Terjadi karena menggunakan SQLite. `ILIKE` hanya ada di PostgreSQL. Untuk dev pakai MySQL atau ganti query ke `LIKE` sementara.

**OCR tidak bekerja / timeout**
→ Pastikan OCR service berjalan di port 8001. Cek `OCR_SERVICE_URL` di `.env`.

**Queue job tidak diproses**
→ Pastikan queue worker berjalan: `php artisan queue:work --queue=ocr`

**Vite HMR tidak berjalan**
→ Pastikan `npm run dev` berjalan paralel dengan `php artisan serve`
