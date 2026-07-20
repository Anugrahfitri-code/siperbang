# Changelog

Semua perubahan penting pada proyek ini dicatat di sini.
Format mengikuti [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [Unreleased]

### Added
- Folder `/docs` dengan dokumentasi lengkap: README, ARCHITECTURE, FEATURES, API_REFERENCE, DATABASE, SETUP_DEV, DEPLOYMENT, SECURITY, ROADMAP
- Architecture Decision Records (ADR) di `/docs/adr/decisions.md`

---

## [0.1.0] — 2026-07-15

### Added
- **Modul Stok Upload Excel** — workflow 4-step (upload → pemeriksaan → verifikasi kode → finalisasi)
  - `ExcelPersediaanImportService` untuk parsing & validasi file `.xlsx`
  - `StokFinalizationService` untuk commit data ke tabel master
  - `StokCancellationService` untuk pembatalan batch
  - SoftDeletes untuk batch upload (trash/restore)
- **BON Digital** — sistem permintaan barang multi-item dengan header BON
  - Auto-generate `bon_no` format `BON/YYYY/MM/DD/NNN`
  - Status tracking per item dan per BON header
  - `bon_status_histories` untuk audit trail perubahan status
- **OCR Kuitansi** — upload dokumen kuitansi (jpg/png/pdf) dengan pemrosesan OCR async
  - Python microservice FastAPI + PaddleOCR di `ocr-service/`
  - Laravel Queue job `ProcessReceiptOcr` dengan atomic claim
  - Status lifecycle: `uploaded → queued → processing → needs_review/verified/failed`
  - Retry mechanism untuk dokumen yang gagal
  - SHA256 fingerprint untuk deteksi duplikat
- **Master Data Kode Persediaan** — kategori barang + kode persediaan dengan fuzzy matching
- **Distribusi & Pengadaan** — tracking pemenuhan BON via stok atau pengadaan vendor/pasar
- **Ekspor CSV** — rekap kuitansi terverifikasi per bulan atau per tahun
- **User Management** — CRUD user oleh Superadmin, RBAC dengan 4 role
- **Audit Log** — pencatatan aksi finalisasi stok

### Technical
- Laravel 13, PHP 8.4, React 19, TypeScript, Tailwind CSS v4, Vite 8
- `ReceiptDocumentStatus` sebagai PHP Backed Enum
- `RoleMiddleware` untuk RBAC
- 13 migration files, 2 seeders
- 2 Feature tests, 1 Unit test placeholder
