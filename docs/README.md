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

- PHP >= 8.4 dengan ekstensi: `pdo`, `mbstring`, `fileinfo`, `zip`, `gd`
- Composer >= 2.x
- Node.js >= 20.x + npm
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
npm install
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
.\run-server.ps1              # atau: uvicorn app.main:app --port 8001
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
| [ARCHITECTURE.md](ARCHITECTURE.md) | Diagram arsitektur & keputusan desain |
| [FEATURES.md](FEATURES.md) | Daftar lengkap fitur per modul |
| [API_REFERENCE.md](API_REFERENCE.md) | Semua endpoint API |
| [DATABASE.md](DATABASE.md) | Skema database & relasi |
| [SETUP_DEV.md](SETUP_DEV.md) | Panduan onboarding developer baru |
| [DEPLOYMENT.md](DEPLOYMENT.md) | Panduan deploy ke staging/production |
| [SECURITY.md](SECURITY.md) | Kebijakan keamanan |
| [CHANGELOG.md](../CHANGELOG.md) | Riwayat perubahan |
| [ROADMAP.md](ROADMAP.md) | Rencana pengembangan |
