# SIPERBANG — Sistem Informasi Persediaan Barang

SIPERBANG adalah aplikasi manajemen persediaan barang internal berbasis web untuk instansi/organisasi. Sistem ini mengelola stok barang, permintaan (BON digital), kuitansi OCR, pengadaan, distribusi, dan pelaporan.

## Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | PHP 8.4 + Laravel 13 |
| Frontend | React 19 + TypeScript + Tailwind CSS v4 |
| Build tool | Vite 8 |
| Database | SQLite (dev) / MySQL atau PostgreSQL (prod) |
| OCR Service | Python FastAPI + PaddleOCR |
| Queue | Laravel Queue (database driver) |
| Auth | Laravel Session-based Auth |

## Prasyarat

- PHP >= 8.4 dengan ekstensi: `dom`, `curl`, `libxml`, `pdo`, `mbstring`, `fileinfo`, `zip`, `gd`
- Composer >= 2.x
- Node.js >= 22.x + npm
- Python >= 3.10 (untuk OCR service)
- SQLite (dev) atau MySQL 8+ / PostgreSQL 15+ (prod)

## Instalasi Cepat

```bash
# 1. Clone repo
git clone <repo-url> siperbang
cd siperbang

# 2. Jalankan setup otomatis (install deps + migrate + build assets)
composer setup

# 3. Jalankan dev server
composer dev
```

Atau manual:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
npm ci
npm run build
php artisan serve
```

## Menjalankan Aplikasi (Development)

```bash
composer dev
```

Perintah ini menjalankan secara bersamaan:
- Laravel dev server (`php artisan serve`)
- Vite HMR (`npm run dev`)
- Laravel Queue worker

## Menjalankan OCR Service

```bash
cd ocr-service
python -m venv .venv
.venv\Scripts\activate        # Windows
# source .venv/bin/activate   # Linux/macOS
pip install -r requirements.txt
.\scripts\run-server.ps1              # atau: uvicorn app.main:app --port 8001
```

## Environment Variables Penting

Lihat `.env.example` untuk daftar lengkap. Variabel kritis:

```
APP_KEY=           # Di-generate otomatis saat setup
APP_ENV=           # local | staging | production
DB_CONNECTION=     # sqlite | mysql | pgsql
OCR_SERVICE_URL=   # URL OCR service, default: http://127.0.0.1:8001
OCR_SERVICE_TOKEN= # Bearer token untuk OCR service
```

## Dokumentasi Lanjutan

| Dokumen | Deskripsi |
|---|---|
| [ARCHITECTURE.md](architecture/ARCHITECTURE.md) | Diagram arsitektur & keputusan desain |
| [FEATURES.md](reference/FEATURES.md) | Daftar lengkap fitur per modul |
| [API_REFERENCE.md](reference/API_REFERENCE.md) | Semua endpoint API |
| [DATABASE.md](reference/DATABASE.md) | Skema database & relasi |
| [SETUP_DEV.md](development/SETUP_DEV.md) | Panduan onboarding developer baru |
| [DEPLOYMENT.md](operations/DEPLOYMENT.md) | Panduan deploy ke staging/production |
| [SECURITY.md](operations/SECURITY.md) | Kebijakan keamanan |
| [BRANDING.md](operations/BRANDING.md) | Operasional identitas dan pergantian branding tahunan |
| [RELEASE_PACKAGING.md](operations/RELEASE_PACKAGING.md) | Membuat paket source tanpa secret/data runtime |
| [CHANGELOG.md](../CHANGELOG.md) | Riwayat perubahan |
| [ROADMAP.md](planning/ROADMAP.md) | Rencana pengembangan |
| [PROJECT_STRUCTURE.md](development/PROJECT_STRUCTURE.md) | Struktur folder dan aturan penempatan file |
| [CLEANUP_REPORT.md](development/CLEANUP_REPORT.md) | Hasil audit dan perapian struktur proyek |
| [FOLDER_AUDIT.md](development/FOLDER_AUDIT.md) | Audit kesesuaian setiap folder dan keputusan penempatan file |
| [maintenance/](maintenance/) | Catatan perbaikan dan pemeliharaan lama |
