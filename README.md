# SIPERBANG

SIPERBANG adalah aplikasi pengelolaan persediaan barang untuk instansi pemerintah. Proyek ini terdiri dari aplikasi utama Laravel dan layanan OCR terpisah berbasis FastAPI.

## Kebutuhan Sistem Produksi (Baseline)

- **PHP**: 8.4+
- **Node.js**: `^20.19.0` atau `>=22.12.0` (dibutuhkan pada host yang melakukan proses build frontend)
- **Database**: PostgreSQL baseline 17.11
- **OCR Host Architecture**: x86_64 / AMD64 (ARM64 saat ini tidak disupport)
- **OCR Runtime**: Docker (wajib untuk layanan OCR)

## Panduan Deployment

Deployment ke server produksi **wajib** mengikuti panduan resmi dan melakukan validasi *preflight*.
Jalankan skrip berikut di server untuk memastikan semua komponen siap:

```bash
bash scripts/deployment/preflight.sh --with-ocr
```

Detail panduan deployment produksi dapat dilihat di: [docs/operations/DEPLOYMENT.md](docs/operations/DEPLOYMENT.md).

## Pengembangan (Development)

Untuk pengembangan lokal, lihat panduan di [docs/development/SETUP_DEV.md](docs/development/SETUP_DEV.md).
Lihat [docs/development/PROJECT_STRUCTURE.md](docs/development/PROJECT_STRUCTURE.md) untuk penjelasan rinci.

## Pengujian

Laravel:

```bash
php artisan test
```

Frontend:

```bash
npm ci
npm run typecheck
npm run lint
npm run build
npm run verify:build
```

OCR:

```bash
cd ocr-service
python -m pytest
```

## Status Produksi

Repository security dan kontrol data integrity telah di-harden dan diverifikasi secara teknis di level source code. Namun, **final company-environment acceptance tetap dibutuhkan (seperti verifikasi patch OS/kernel host dan uji coba inference OCR terautentikasi di infrastruktur nyata) sebelum production go-live disetujui.**

## Dokumentasi

- [Panduan developer](docs/development/SETUP_DEV.md)
- [Arsitektur](docs/architecture/ARCHITECTURE.md)
- [Referensi API](docs/reference/API_REFERENCE.md)
- [Struktur database](docs/reference/DATABASE.md)
- [Deployment](docs/operations/DEPLOYMENT.md)
- [Keamanan](docs/operations/SECURITY.md)
- [Laporan perapian proyek](docs/development/CLEANUP_REPORT.md)
- [Audit kesesuaian folder](docs/development/FOLDER_AUDIT.md)
