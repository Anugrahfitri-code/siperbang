# SIPERBANG

SIPERBANG adalah aplikasi pengelolaan persediaan barang untuk instansi pemerintah. Proyek ini terdiri dari aplikasi utama Laravel dan layanan OCR terpisah berbasis FastAPI.

## Teknologi utama

| Bagian | Teknologi |
|---|---|
| Backend | PHP 8.4 dan Laravel 13 |
| Frontend | React 19, TypeScript, Tailwind CSS 4, Vite 8 |
| Database | SQLite untuk pengembangan, PostgreSQL atau MySQL untuk produksi |
| OCR | Python, FastAPI, PaddleOCR |
| Queue | Laravel database queue |

## Kebutuhan sistem

- PHP 8.4 atau lebih baru
- Composer 2
- Node.js 20 atau lebih baru
- npm
- Python 3.10 atau lebih baru untuk layanan OCR
- Ekstensi PHP: `pdo`, `mbstring`, `fileinfo`, `zip`, dan `gd`

## Instalasi aplikasi utama

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm ci
npm run build
```

Konfigurasi awal menggunakan SQLite. Pastikan file database tersedia:

```bash
mkdir -p database
touch database/database.sqlite
```

Untuk PostgreSQL, sesuaikan variabel `DB_*` pada `.env`.

## Menjalankan aplikasi

Jalankan Laravel:

```bash
php artisan serve
```

Jalankan Vite pada terminal lain:

```bash
npm run dev
```

Jalankan queue worker pada terminal lain:

```bash
php artisan queue:work
```

Perintah `composer dev` memakai PowerShell melalui `scripts/dev.ps1`. Gunakan perintah tersebut pada Windows.

## Menjalankan layanan OCR

```bash
cd ocr-service
python -m venv .venv
```

Windows:

```powershell
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
Copy-Item .env.example .env
.\scripts\run-server.ps1
```

Linux atau macOS:

```bash
source .venv/bin/activate
pip install -r requirements.txt
cp .env.example .env
uvicorn app.main:app --host 127.0.0.1 --port 8001
```

Nilai `OCR_SERVICE_TOKEN` pada `.env` utama harus sama dengan nilai pada `ocr-service/.env`.

## Struktur proyek

```text
app/                    kode aplikasi Laravel
archive/                prototipe, aset, dan source lama di luar runtime
config/                 konfigurasi Laravel
database/               migrasi, factory, dan seeder
docs/                   dokumentasi teknis
ocr-service/            layanan OCR FastAPI
public/                 entry point dan aset publik
resources/              React, CSS, dan Blade
routes/                  definisi route
scripts/                 script pengembangan aktif
storage/                 file kerja Laravel
tests/                   test otomatis Laravel
tools/                   diagnostik, test manual, dan script lama
```

Lihat [docs/development/PROJECT_STRUCTURE.md](docs/development/PROJECT_STRUCTURE.md) untuk penjelasan rinci.

## Pengujian

Laravel:

```bash
php artisan test
```

Frontend:

```bash
npm run build
```

OCR:

```bash
cd ocr-service
python -m pytest
```

## Dokumentasi

- [Panduan developer](docs/development/SETUP_DEV.md)
- [Arsitektur](docs/architecture/ARCHITECTURE.md)
- [Referensi API](docs/reference/API_REFERENCE.md)
- [Struktur database](docs/reference/DATABASE.md)
- [Deployment](docs/operations/DEPLOYMENT.md)
- [Keamanan](docs/operations/SECURITY.md)
- [Laporan perapian proyek](docs/development/CLEANUP_REPORT.md)
- [Audit kesesuaian folder](docs/development/FOLDER_AUDIT.md)
