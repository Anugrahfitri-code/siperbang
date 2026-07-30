# Arsitektur SIPERBANG

## Gambaran umum

SIPERBANG memakai aplikasi Laravel dengan frontend React SPA dan satu layanan OCR FastAPI terpisah.

```text
Browser
  │ session cookie + HTTP
  ▼
Laravel
  ├── routes/web.php
  ├── routes/api.php
  ├── Controller Web
  ├── Controller API
  ├── Service domain
  ├── Eloquent model
  └── Queue job OCR
          │ HTTP internal + token
          ▼
      FastAPI OCR Service
          │
          ▼
      PaddleOCR + parser kuitansi
```

## Presentation layer

### React SPA

Source frontend berada pada `resources/js/`.

- `App.tsx` mengatur state aplikasi dan pemilihan modul.
- `features/` berisi komponen yang khusus untuk satu fitur.
- `shared/components/` berisi komponen lintas fitur.
- `shared/api.ts` menyediakan helper request HTTP.
- `shared/types.ts` berisi tipe domain yang dipakai beberapa fitur.

### Blade

- `welcome.blade.php` menjadi host React.
- `layouts/inventory.blade.php` menjadi layout halaman administrasi stok berbasis Blade.
- View `master-barang/` dan `stok-upload/` mendukung alur web yang masih aktif.

## Routing

- `routes/web.php` memuat halaman web, halaman Blade, download template, dan fallback SPA.
- `routes/api.php` memuat endpoint login berbasis sesi dan endpoint `/api/*`.
- `routes/api.php` dimuat melalui `routes/web.php` agar middleware session Laravel tetap berlaku.

## Application layer

### Controller API

`app/Http/Controllers/Api/` hanya berisi controller dengan respons JSON.

Controller utama:

- `RequestController`
- `StockController`
- `StokUploadController`
- `ReceiptController`
- `ReceiptDocumentController`
- `InventoryCodeController`
- `LogController`
- `UserController`

### Controller web

`app/Http/Controllers/Web/` hanya berisi controller halaman Blade dan download web.

- `StokUploadController`
- `BarangController`

## Business logic layer

```text
app/Services/
├── Inventory/
├── Ocr/
└── Receipt/
```

Domain `Inventory` menangani impor Excel, pencocokan kode, finalisasi, dan pembatalan stok. Domain `Receipt` menangani ekspor serta sinkronisasi item kuitansi. Domain `Ocr` menangani komunikasi HTTP dengan service Python.

## Data layer

Delapan belas model Eloquent berada pada `app/Models/`. Model tetap datar sesuai konvensi Laravel. Struktur database dijelaskan pada [DATABASE.md](../reference/DATABASE.md).

## Queue dan OCR

`app/Jobs/Receipt/ProcessReceiptOcr.php` mengirim dokumen ke layanan OCR. Queue memakai driver Laravel dan antrean `ocr`.

Layanan OCR berada pada `ocr-service/`:

- `ocr-service/app/main.py` menyediakan endpoint FastAPI.
- `ocr-service/app/ocr_engine.py` menjalankan OCR.
- `ocr-service/app/document_loader.py` membaca gambar atau PDF.
- `ocr-service/app/receipt_parser.py` membentuk struktur kuitansi.
- `tests/integration/` menguji endpoint.
- `tests/unit/` menguji parser.

## Alur upload stok Excel

```text
UploadStokExcelRequest
  → Web StokUploadController::upload
  → Inventory/ExcelPersediaanImportService
  → stok_uploads dan stok_upload_details
  → stepper verifikasi
  → Inventory/StokFinalizationService
  → barang, stock history, dan audit log
```

## Alur OCR kuitansi

```text
ReceiptDocumentController::store
  → ProcessReceiptOcr queue job
  → Ocr/OcrServiceClient
  → FastAPI OCR service
  → hasil mentah dan hasil parser
  → review dan verifikasi pengguna
  → Receipt/ReceiptStockSyncService
```

## Alur BON digital

```text
BonDigitalForm
  → RequestController::store
  → BonHeader dan ItemRequest
  → pemeriksaan stok
  → distribusi atau pengadaan
  → pembaruan status BON
```

## Keputusan struktur

- Controller web dan API dipisahkan berdasarkan jenis respons.
- Service dipisahkan berdasarkan domain.
- Komponen React dipisahkan berdasarkan fitur.
- Model dan migrasi tetap mengikuti struktur Laravel yang lazim.
- Source lama disimpan di `archive/`, bukan dicampur dengan source aktif.

Catatan keputusan arsitektur tersedia pada [adr/decisions.md](adr/decisions.md).
