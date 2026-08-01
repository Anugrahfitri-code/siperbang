# FEATURES.md — Daftar Lengkap Fitur SIPERBANG

Dokumen ini merangkum fitur aktif. Struktur file diperbarui pada 2026-07-30.

---

## Modul 1: Autentikasi & Manajemen User

### 1.1 Login / Logout
- **File utama:** `routes/api.php` (POST `/api/login`, POST `/api/logout`)
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
- **Endpoint:** GET `/api/stocks/search`
- **Dependency:** `App\Models\Barang` (model `barang` tabel, bukan `stock_items`)
- **Akses:** Semua role terautentikasi
- **Test:** Tidak ada
- **Status:** Berfungsi normal
- **Catatan:** Menggunakan `ILIKE` — hanya kompatibel PostgreSQL. Akan error di SQLite/MySQL.

### 2.2 Full Stock List
- **File utama:** `app/Http/Controllers/Api/StockController.php` → `index()`
- **Endpoint:** GET `/api/stocks`
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
  - `app/Http/Controllers/Web/StokUploadController.php`
  - `app/Services/Inventory/ExcelPersediaanImportService.php`
  - `app/Services/Inventory/StokFinalizationService.php`
  - `app/Services/Inventory/StokCancellationService.php`
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
- **Seeder:** `database/seeders/Inventory/OfficeActivityInventoryCodeSeeder.php`
- **Status:** Berfungsi normal

### 3.2 Master Kode Persediaan
- **Tabel:** `kode_persediaan`
- **Model:** `app/Models/KodePersediaan.php`
- **Service:** `app/Services/Inventory/KodePersediaanService.php` (fuzzy matching kode)
- **Status:** Berfungsi normal
- **Catatan:** Digunakan untuk suggest kode saat verifikasi Excel upload.

---

## Modul 4: BON Digital (Permintaan Barang)

### 4.1 Buat BON / Permintaan Barang
- **Endpoint:** POST `/api/requests`
- **File:** `app/Http/Controllers/Api/RequestController.php` → `store()`
- **Frontend:** `resources/js/features/requests/components/BonDigitalForm.tsx`
- **Dependency:** `App\Models\ItemRequest`, `App\Models\BonHeader`
- **Test:** Tidak ada
- **Status:** Berfungsi normal
- **Catatan:** BON number di-generate otomatis: `BON/YYYY/MM/DD/NNN`. Satu BON bisa multi-item.

### 4.2 List & Filter BON
- **Endpoint:** GET `/api/requests`
- **File:** `RequestController@index`
- **Akses:** Ketua Tim hanya lihat BON seksinya sendiri
- **Status:** Berfungsi normal

### 4.3 Perbarui Draft BON
- **Endpoint:** PUT `/api/requests/bon/{id}`
- **File:** `RequestController@updateDraft`
- **Akses:** `Ketua Tim`, `Ketua Tim Kerja`, `Superadmin`
- **Status:** Berfungsi normal

### 4.4 Pemeriksaan dan Pembaruan Status Item
- **Endpoint:** PUT `/api/requests/{itemRequest}/status`
- **File:** `RequestController@updateStatus`
- **Akses:** `Petugas Persediaan`, `Superadmin`
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
- **Frontend:** `resources/js/features/requests/components/BonMonitoringList.tsx`, `KetuaTimDashboard.tsx`
- **Status:** Berfungsi normal

---

## Modul 5: Kuitansi & OCR

### 5.1 Upload Dokumen Kuitansi (dengan OCR)
- **Endpoint:** POST `/api/receipt-documents`
- **File:** `app/Http/Controllers/Api/ReceiptDocumentController.php` → `store()`
- **Job:** `app/Jobs/Receipt/ProcessReceiptOcr.php`
- **Service:** `app/Services/Ocr/OcrServiceClient.php`
- **OCR Engine:** Python FastAPI + PaddleOCR (`ocr-service/`)
- **Test:** `tests/Feature/Receipt/ReceiptDocumentTest.php`
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

### 5.6 Pengelolaan Kuitansi
- **Endpoint:** GET/POST `/api/receipts`
- **Update item:** PUT `/api/receipts/{receipt}/items`
- **Batalkan verifikasi:** PUT `/api/receipts/{receipt}/unverify`
- **Ekspor:** POST `/api/receipts/export-excel`
- **File:** `app/Http/Controllers/Api/ReceiptController.php`
- **Tabel:** `receipts`, `receipt_items`
- **Status:** Berfungsi normal

### 5.7 Frontend OCR Processor
- **File:** `resources/js/features/receipts/components/ReceiptOCRProcessor.tsx`
- **Status:** Berfungsi normal

---

## Modul 6: Distribusi & Pengadaan

### 6.1 Distribution Procurement View
- **Frontend:** `resources/js/features/inventory/components/DistributionProcurement.tsx`
- **Status:** Berfungsi normal

---

## Modul 7: Pelaporan & Ekspor

### 7.1 Ekspor Rekap Kuitansi (CSV)
- **Endpoint:** GET `/api/export-excel`
- **File:** `app/Http/Controllers/Api/LogController.php` → `exportExcel()`
- **Filter:** year, month, search, annual
- **Akses:** `Petugas Persediaan`, `Superadmin`
- **Test:** `tests/Feature/Receipt/ReceiptExportTest.php`
- **Status:** Berfungsi normal
- **Catatan:** Laporan dihasilkan langsung sebagai file CSV (StreamedResponse) dari sisi server.

### 7.2 Report Export Frontend
- **Frontend:** `resources/js/features/reports/components/ReportExport.tsx`
- **Status:** Berfungsi normal

---

## Modul 8: History & Audit Log

### 8.1 History Log
- **Endpoint:** GET `/api/logs`, POST `/api/logs`
- **File:** `app/Http/Controllers/Api/LogController.php`
- **Tabel:** `history_logs`
- **Frontend:** `resources/js/features/audit/components/HistoryLog.tsx`
- **Status:** Berfungsi normal
- **Catatan:** Ada dua sistem log yang terpisah: `history_logs` (prototype lama) dan `audit_logs` (baru). Keduanya ditulis bersamaan di `StokFinalizationService`. Redundan.

### 8.2 Audit Log
- **Tabel:** `audit_logs`
- **Model:** `app/Models/AuditLog.php`
- **Status:** Berfungsi normal, tapi hanya ditulis di finalisasi stok. Belum cover semua aksi sensitif.

---

## Modul 9: Dashboard

### 9.1 Dashboard Stats
- **Frontend:** `resources/js/features/dashboard/components/DashboardStats.tsx`
- **Status:** Berfungsi normal

### 9.2 Stock Checking
- **Frontend:** `resources/js/features/inventory/components/StockChecking.tsx`
- **Status:** Berfungsi normal

### 9.3 Stock Management
- **Frontend:** `resources/js/features/inventory-upload/components/StockManagement.tsx`
- **Status:** Berfungsi normal

---

## Komponen arsip dan catatan arsitektur

### Dipisahkan dari runtime

| Lokasi | Keterangan |
|---|---|
| `archive/prototypes/desain-temp/` | Prototipe frontend lama yang tidak masuk input Vite aktif |
| `archive/frontend/unused/` | Source frontend tanpa referensi pada graph import aktif |
| `archive/legacy/laravel/` | Controller, request, view, layout, dan test lama |
| `archive/assets/unused/` | Aset tanpa referensi runtime |
| `tools/legacy/` | Script perbaikan satu kali dan patch lama |
| `tools/manual-tests/` | Pengujian endpoint secara manual |
| `tools/diagnostics/` | Alat pemeriksaan lokal |

Controller `PerbaikiDataController`, `VerifikasiKodePersediaanController`, dan `StokPengadaanController` sudah dipindahkan ke arsip setelah pemeriksaan route dan referensi aktif. `BarangController` tetap aktif pada `app/Http/Controllers/Web/BarangController.php` karena digunakan oleh route master barang.

`Barang` dan `StockItem` tetap menjadi dua model aktif. Keduanya mempunyai penggunaan yang berbeda pada alur stok lama dan alur permintaan. Penyatuan model memerlukan migrasi data dan berada di luar perapian folder.

## Modul 9: Identitas Situs dan Branding Tahunan

### 9.1 Identitas aktif
- **Endpoint publik:** GET `/api/settings`
- **Service:** `app/Services/SiteBrandingService.php`
- **Sumber aktif:** `site_settings`
- **Surface:** React, Blade, title, favicon, footer, laporan, dan metadata workbook
- **Status:** Aktif

### 9.2 Versioning, jadwal, dan rollback
- **Akses:** Superadmin
- **Tabel:** `site_branding_versions`
- **Status:** `draft`, `scheduled`, `published`, `archived`
- **Scheduler command:** `php artisan branding:publish-due`
- **Test:** `tests/Feature/SiteBranding/SiteBrandingSettingsTest.php`
- **Catatan:** Rollback membuat versi publikasi baru, bukan mengubah snapshot arsip.

### 9.3 Upload aset branding
- **Format:** PNG, JPEG, WebP
- **Pemrosesan:** server-side validation, resize, re-encode, dan penghapusan metadata melalui PHP GD
- **Storage:** path relatif pada disk public di bawah `branding/`
- **Keamanan:** SVG tidak diterima; file lama dipertahankan selama masih direferensikan versi aktif/arsip.

