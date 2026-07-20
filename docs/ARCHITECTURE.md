# ARCHITECTURE.md — Arsitektur SIPERBANG

## Gambaran Umum

SIPERBANG adalah aplikasi **monolith modular** dengan arsitektur **Laravel + React SPA**. Backend Laravel menangani semua logika bisnis dan menyajikan API JSON. Frontend React berjalan sebagai SPA yang dikompilasi oleh Vite dan di-serve oleh Laravel.

Satu microservice eksternal terpisah: **OCR Service** (Python FastAPI) yang hanya berkomunikasi dengan Laravel melalui HTTP internal.

```
┌─────────────────────────────────────────────┐
│                  BROWSER                    │
│         React 19 SPA (TypeScript)           │
│         Tailwind CSS v4 + Lucide            │
└──────────────────┬──────────────────────────┘
                   │ HTTP (Session Cookie)
                   │ /api/* endpoints
┌──────────────────▼──────────────────────────┐
│              LARAVEL 13 (PHP 8.4)           │
│                                             │
│  Routes (web.php)                           │
│      │                                      │
│  Middleware (Auth, RoleMiddleware)           │
│      │                                      │
│  Controllers                                │
│  ├── Api/ (JSON responses)                  │
│  └── Web/ (Blade views for SPA shell)       │
│      │                                      │
│  Services (Business Logic)                  │
│  ├── ExcelPersediaanImportService           │
│  ├── StokFinalizationService                │
│  ├── StokCancellationService                │
│  ├── KodePersediaanService                  │
│  └── Ocr/OcrServiceClient                  │
│      │                                      │
│  Models (Eloquent ORM)                      │
│  Jobs (Queue: ProcessReceiptOcr)            │
│      │                   │                  │
│  Database (SQLite/MySQL) │                  │
│                     Queue Worker            │
└─────────────────────┬───────────────────────┘
                       │ HTTP POST (internal)
                       │ Bearer Token
┌──────────────────────▼──────────────────────┐
│          OCR SERVICE (Python)               │
│    FastAPI + PaddleOCR + pypdfium2          │
│    Port: 8001 (default)                     │
└─────────────────────────────────────────────┘
```

## Layer Aplikasi

### 1. Presentation Layer
- **React SPA** (`resources/js/`) — semua UI dirender di sisi klien
- **Blade** (`resources/views/welcome.blade.php`) — hanya sebagai shell untuk inject React
- **Komponen utama:** 16 komponen React di `resources/js/components/`

### 2. Application Layer (Controllers)
- `app/Http/Controllers/Api/` — 6 controller API (JSON)
- `app/Http/Controllers/` — 4 controller web (legacy + StokUpload)
- `app/Http/Middleware/RoleMiddleware.php` — RBAC check
- `app/Http/Requests/` — 2 Form Request untuk validasi input

### 3. Business Logic Layer (Services)
- `ExcelPersediaanImportService` — parsing & validasi file Excel persediaan
- `StokFinalizationService` — commit stok ke master setelah verifikasi
- `StokCancellationService` — pembatalan batch upload
- `KodePersediaanService` — fuzzy matching kode persediaan dari Excel
- `OcrServiceClient` — HTTP client ke Python OCR service

### 4. Data Access Layer (Models)
18 Eloquent models. Lihat [DATABASE.md](DATABASE.md) untuk relasi lengkap.

### 5. Infrastructure Layer
- **Queue:** Laravel database queue, satu queue bernama `ocr`
- **Storage:** Laravel filesystem (`storage/private/uploads`, `storage/ocr-documents`)
- **Cache:** Laravel cache (database driver di dev)

## Alur Data Utama

### Alur Upload Stok Excel
```
User upload .xlsx
    → UploadStokExcelRequest (validasi mime/size)
    → StokUploadController@upload
    → ExcelPersediaanImportService@import
        → Parse setiap sheet
        → Deteksi supplier dari header
        → Validasi setiap baris (qty, harga, kode)
        → Fuzzy match kode persediaan (KodePersediaanService)
        → Simpan ke stok_uploads + stok_upload_details
    → Redirect ke stepper
    → [User verifikasi kode] → saveVerifikasi
    → [User finalisasi] → StokFinalizationService@finalize
        → Update/insert tabel barang
        → Catat StockHistory + AuditLog
```

### Alur OCR Kuitansi
```
User upload gambar/PDF kuitansi
    → ReceiptDocumentController@store (validasi, simpan file, catat SHA256)
    → ProcessReceiptOcr::dispatch() → masuk queue 'ocr'
    → Queue worker mengambil job
    → OcrServiceClient@processReceipt → HTTP POST ke Python service
    → Python: PaddleOCR → ekstrak teks → parse struktur kuitansi
    → Hasil disimpan ke receipt_documents.raw_result + parsed_result
    → Status: needs_review (jika confidence rendah) atau verified
    → User review manual → ReceiptDocumentController@verify
```

### Alur BON Digital
```
User buat BON (BonDigitalForm)
    → POST /api/requests
    → Generate bon_no (BON/YYYY/MM/DD/NNN, atomic dengan retry)
    → Buat BonHeader + ItemRequest(s)
    → Ketua Tim verifikasi → PUT /api/requests/{id}/verify
    → Petugas Persediaan proses:
        ├── Stok tersedia → distribute → StockItem.qty dikurangi → Distribution record
        └── Stok kurang → procure → Procurement record (vendor/pasar/ATK)
```

## Keputusan Desain

Lihat folder [adr/](adr/) untuk Architecture Decision Records.

## Struktur Folder Lengkap

```
siperbang/
├── app/
│   ├── Enums/               # PHP 8.1+ Backed Enums
│   ├── Exceptions/          # Custom exceptions (OcrServiceException, ExcelValidationException)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/         # JSON API controllers
│   │   │   └── *.php        # Web controllers (Blade/stepper)
│   │   ├── Middleware/      # RoleMiddleware
│   │   └── Requests/        # Form Request validation
│   ├── Jobs/                # ProcessReceiptOcr (queue job)
│   ├── Models/              # 18 Eloquent models
│   ├── Providers/           # AppServiceProvider
│   └── Services/            # Business logic services
│       └── Ocr/             # OcrServiceClient
├── database/
│   ├── migrations/          # 13 migration files
│   └── seeders/             # KategoriDanKodePersediaanSeeder
├── ocr-service/             # Python microservice (FastAPI + PaddleOCR)
│   ├── app/                 # FastAPI app
│   └── tests/               # Python tests
├── resources/
│   ├── css/                 # Tailwind entry point
│   ├── js/                  # React SPA
│   │   ├── components/      # 16 React components
│   │   ├── api.ts           # API client functions
│   │   ├── types.ts         # TypeScript types
│   │   └── App.tsx          # Root component + routing
│   └── views/               # Blade templates (hanya welcome.blade.php)
├── routes/
│   └── web.php              # Semua routes (web + api)
├── tests/
│   ├── Feature/             # ReceiptDocumentTest + ExampleTest
│   └── Unit/                # ExampleTest
└── docs/                    # Dokumentasi ini
```
