# FEATURES.md — Daftar Lengkap Fitur SIPERBANG

Dokumen ini adalah hasil inventarisasi Tahap 1. Diperbarui: 2026-07-19.

---

## Modul 1: Autentikasi & Manajemen User

### 1.1 Login / Logout
- **File utama:** `routes/web.php` (POST `/api/login`, POST `/api/logout`)
- **Dependency:** Laravel Session Auth, `Auth::attempt()`
- **Test:** Tidak ada test spesifik untuk login
- **Dokumentasi:** Tidak ada
- **Status:** Berfungsi normal
- **Catatan:** Login menggunakan `username` (bukan email). Session-based, bukan token.

### 1.2 User Management (CRUD)
- **File utama:** `app/Http/Controllers/Api/UserController.php`
- **Endpoint:** GET/POST/PUT/DELETE `/api/users`
- **Dependency:** `App\Models\User`
- **Akses:** Superadmin only (middleware `role:Superadmin`)
- **Test:** Tidak ada
- **Dokumentasi:** Tidak ada
- **Status:** Berfungsi normal
- **Catatan:** Password default baru adalah string literal `'password'` — PERLU DIUBAH.

### 1.3 Role-Based Access Control
- **File utama:** `app/Http/Middleware/RoleMiddleware.php`
- **Role yang ada:** `Superadmin`, `Petugas Persediaan`, `Ketua Tim`, `Ketua Tim Kerja`
- **Test:** Tidak ada
- **Status:** Berfungsi normal
- **Catatan:** Superadmin selalu bypass semua role check.

---

## Modul 2: Manajemen Stok (Master Data)

### 2.1 Daftar & Pencarian Stok
- **File utama:** `app/Http/Controllers/Api/StockController.php` → `search()`
- **Endpoint:** GET `/api/stock/search`
- **Dependency:** `App\Models\Barang` (model `barang` tabel, bukan `stock_items`)
- **Akses:** Semua role terautentikasi
- **Test:** Tidak ada
- **Status:** Berfungsi normal
- **Catatan:** Menggunakan `ILIKE` — hanya kompatibel PostgreSQL. Akan error di SQLite/MySQL.

### 2.2 Full Stock List
- **File utama:** `app/Http/Controllers/Api/StockController.php` → `index()`
- **Endpoint:** GET `/api/stock`
- **Dependency:** `App\Models\StockItem`
- **Akses:** `Petugas Persediaan`, `Superadmin`
- **Status:** Berfungsi normal
- **Catatan:** Tidak ada pagination — berpotensi lambat jika data besar.

### 2.3 Upload Stok via Excel (Stepper 4-Step)
Workflow utama pengisian stok dari file Excel supplier.

| Step | Nama | Endpoint | Keterangan |
|---|---|---|---|
| 1 | Upload | POST `/stok-upload` | Upload file `.xlsx`, parsing & validasi |
| 2 | Pemeriksaan | GET `/stok-upload/{id}/stepper` | Review data, tampilkan error |
| 3 | Verifikasi Kode | POST `/stok-upload/{id}/verifikasi` | Tetapkan kode persediaan per baris |
| 4 | Finalisasi | POST `/stok-upload/{id}/finalisasi` | Commit ke stok master |

- **File utama:**
  - `app/Http/Controllers/StokUploadController.php`
  - `app/Services/ExcelPersediaanImportService.php`
  - `app/Services/StokFinalizationService.php`
  - `app/Services/StokCancellationService.php`
- **Dependency:** `phpoffice/phpspreadsheet`
- **Test:** Tidak ada test untuk Excel import
- **Status:** Berfungsi normal
- **Catatan:** File disimpan di `storage/private/uploads`. Ada SoftDeletes untuk batch (trash/restore).

### 2.4 Riwayat Upload Stok
- **Endpoint:** GET `/stok-upload/riwayat`
- **File:** `StokUploadController@riwayat`
- **Status:** Berfungsi normal

### 2.5 Tempat Sampah Upload (Soft Delete)
- **Endpoint:** GET `/stok-upload/sampah`, POST `/stok-upload/{id}/restore`, DELETE `/stok-upload/{id}`
- **File:** `StokUploadController@trash`, `@restore`, `@destroy`
- **Status:** Berfungsi normal

### 2.6 Template Excel Download
- **Endpoint:** GET `/stok-upload/template`
- **Status:** Berfungsi normal

---

## Modul 3: Kategori & Kode Persediaan

### 3.1 Master Kategori Barang
- **Tabel:** `kategori_barang`
- **Model:** `app/Models/KategoriBarang.php`
- **Seeder:** `database/seeders/KategoriDanKodePersediaanSeeder.php`
- **Status:** Berfungsi normal

### 3.2 Master Kode Persediaan
- **Tabel:** `kode_persediaan`
- **Model:** `app/Models/KodePersediaan.php`
- **Service:** `app/Services/KodePersediaanService.php` (fuzzy matching kode)
- **Status:** Berfungsi normal
- **Catatan:** Digunakan untuk suggest kode saat verifikasi Excel upload.

---

## Modul 4: BON Digital (Permintaan Barang)

### 4.1 Buat BON / Permintaan Barang
- **Endpoint:** POST `/api/requests`
- **File:** `app/Http/Controllers/Api/RequestController.php` → `store()`
- **Frontend:** `resources/js/components/BonDigitalForm.tsx`
- **Dependency:** `App\Models\ItemRequest`, `App\Models\BonHeader`
- **Test:** Tidak ada
- **Status:** Berfungsi normal
- **Catatan:** BON number di-generate otomatis: `BON/YYYY/MM/DD/NNN`. Satu BON bisa multi-item.

### 4.2 List & Filter BON
- **Endpoint:** GET `/api/requests`
- **File:** `RequestController@index`
- **Akses:** Ketua Tim hanya lihat BON seksinya sendiri
- **Status:** Berfungsi normal

### 4.3 Verifikasi BON (Ketua Tim)
- **Endpoint:** PUT `/api/requests/{id}/verify`
- **File:** `RequestController@verify`
- **Status:** Berfungsi normal

### 4.4 Proses BON (Petugas Persediaan)
- **Endpoint:** PUT `/api/requests/{id}/process`
- **File:** `RequestController@process`
- **Akses:** `Petugas Persediaan`
- **Status:** Berfungsi normal

### 4.5 Distribusi Stok
- **Endpoint:** POST `/api/requests/{id}/distribute`
- **File:** `RequestController@distribute`
- **Tabel:** `distributions`
- **Status:** Berfungsi normal

### 4.6 Pengadaan (Procurement)
- **Endpoint:** POST `/api/requests/{id}/procure`
- **File:** `RequestController@procure`
- **Tabel:** `procurements`
- **Status:** Berfungsi normal

### 4.7 Monitor BON (Dashboard Ketua Tim)
- **Frontend:** `resources/js/components/BonMonitoringList.tsx`, `KetuaTimDashboard.tsx`
- **Status:** Berfungsi normal

---

## Modul 5: Kuitansi & OCR

### 5.1 Upload Dokumen Kuitansi (dengan OCR)
- **Endpoint:** POST `/api/receipt-documents`
- **File:** `app/Http/Controllers/Api/ReceiptDocumentController.php` → `store()`
- **Job:** `app/Jobs/ProcessReceiptOcr.php`
- **Service:** `app/Services/Ocr/OcrServiceClient.php`
- **OCR Engine:** Python FastAPI + PaddleOCR (`ocr-service/`)
- **Test:** `tests/Feature/ReceiptDocumentTest.php`
- **Status:** Berfungsi normal
- **Catatan:** Asynchronous via queue `ocr`. Status: `uploaded → queued → processing → needs_review / verified / failed`.

### 5.2 List Dokumen Kuitansi
- **Endpoint:** GET `/api/receipt-documents`
- **Status:** Berfungsi normal

### 5.3 Detail Dokumen + Parsed Result
- **Endpoint:** GET `/api/receipt-documents/{id}`
- **Status:** Berfungsi normal

### 5.4 Verifikasi Manual Kuitansi
- **Endpoint:** PUT `/api/receipt-documents/{id}/verify`
- **File:** `ReceiptDocumentController@verify`
- **Status:** Berfungsi normal

### 5.5 Retry OCR
- **Endpoint:** POST `/api/receipt-documents/{id}/retry`
- **File:** `ReceiptDocumentController@retry`
- **Status:** Berfungsi normal

### 5.6 CRUD Kuitansi (Receipt)
- **Endpoint:** GET/POST/PUT/DELETE `/api/receipts`
- **File:** `app/Http/Controllers/Api/ReceiptController.php`
- **Tabel:** `receipts`, `receipt_items`
- **Status:** Berfungsi normal

### 5.7 Frontend OCR Processor
- **File:** `resources/js/components/ReceiptOCRProcessor.tsx`
- **Status:** Berfungsi normal

---

## Modul 6: Distribusi & Pengadaan

### 6.1 Distribution Procurement View
- **Frontend:** `resources/js/components/DistributionProcurement.tsx`
- **Status:** Berfungsi normal

---

## Modul 7: Pelaporan & Ekspor

### 7.1 Ekspor Rekap Kuitansi (CSV)
- **Endpoint:** GET `/api/export-excel`
- **File:** `app/Http/Controllers/Api/LogController.php` → `exportExcel()`
- **Filter:** year, month, search, annual
- **Akses:** `Petugas Persediaan`, `Superadmin`
- **Test:** Tidak ada
- **Status:** Berfungsi normal
- **Catatan:** Output CSV bukan Excel meskipun nama method `exportExcel`. Nama menyesatkan.

### 7.2 Report Export Frontend
- **Frontend:** `resources/js/components/ReportExport.tsx`
- **Status:** Berfungsi normal

---

## Modul 8: History & Audit Log

### 8.1 History Log
- **Endpoint:** GET `/api/logs`, POST `/api/logs`
- **File:** `app/Http/Controllers/Api/LogController.php`
- **Tabel:** `history_logs`
- **Frontend:** `resources/js/components/HistoryLog.tsx`
- **Status:** Berfungsi normal
- **Catatan:** Ada dua sistem log yang terpisah: `history_logs` (prototype lama) dan `audit_logs` (baru). Keduanya ditulis bersamaan di `StokFinalizationService`. Redundan.

### 8.2 Audit Log
- **Tabel:** `audit_logs`
- **Model:** `app/Models/AuditLog.php`
- **Status:** Berfungsi normal, tapi hanya ditulis di finalisasi stok. Belum cover semua aksi sensitif.

---

## Modul 9: Dashboard

### 9.1 Dashboard Stats
- **Frontend:** `resources/js/components/DashboardStats.tsx`
- **Status:** Berfungsi normal

### 9.2 Stock Checking
- **Frontend:** `resources/js/components/StockChecking.tsx`
- **Status:** Berfungsi normal

### 9.3 Stock Management
- **Frontend:** `resources/js/components/StockManagement.tsx`
- **Status:** Berfungsi normal

---

## File / Komponen Perlu Klarifikasi (Dead Code Candidates)

| File | Alasan Ditandai |
|---|---|
| `read_excel.php` | Script PHP standalone di root, tidak dipanggil dari mana pun |
| `reprocess_ocr.php` | Script standalone, tampaknya utility manual — belum jelas apakah masih dipakai |
| `test_mime.php` | File test manual di root — bukan test PHPUnit |
| `test_parser.py` | Script test Python manual di root |
| `test_regex.py` | Script test Python manual di root |
| `scratch/` | Folder scratch — kemungkinan besar dead code / experimental |
| `desain_temp/` | Folder desain sementara — perlu dikonfirmasi apakah masih relevan |
| `hasil-cepat.json` | File JSON di root — tidak jelas fungsinya |
| `the assistant.json` | File konfigurasi editor di root — bukan kode aplikasi |
| `app/Http/Controllers/PerbaikiDataController.php` | Nama mengindikasikan fitur lama yang mungkin sudah digantikan stepper |
| `app/Http/Controllers/VerifikasiKodePersediaanController.php` | Mungkin sudah digantikan oleh `StokUploadController@saveVerifikasi` |
| `app/Http/Controllers/StokPengadaanController.php` | Belum jelas apakah masih dipakai atau sudah digantikan `RequestController` |
| `app/Http/Controllers/BarangController.php` | Belum diverifikasi apakah masih dipakai |
| `app/Models/Barang.php` vs `app/Models/StockItem.php` | DUA model untuk konsep yang sama — salah satunya mungkin deprecated |

> **Catatan:** File-file di atas TIDAK dihapus. Ditandai sebagai kandidat untuk dikonfirmasi lebih lanjut.
