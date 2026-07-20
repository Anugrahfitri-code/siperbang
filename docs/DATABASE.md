# DATABASE.md — Skema Database SIPERBANG

Database: SQLite (development) / MySQL 8+ atau PostgreSQL 15+ (production)

---

## Diagram Relasi (ERD Ringkas)

```
users
  │
  ├─── stok_uploads (user_id)
  │       └─── stok_upload_details (stok_upload_id)
  │               └─── [commit ke] barang / stock_items
  │
  ├─── bon_headers (user_id)
  │       └─── item_requests (bon_header_id)
  │               ├─── distributions (item_request_id → stock_items)
  │               └─── procurements (item_request_id)
  │
  ├─── receipt_documents (uploaded_by)
  │       └─── receipts (receipt_id)
  │               └─── receipt_items (receipt_id)
  │
  └─── audit_logs (user_id)

kategori_barang
  └─── kode_persediaan (kategori_barang_id)

stock_items / barang
  ├─── stok_histories (stock_item_id)
  ├─── distributions (stock_item_id)
  └─── item_requests (stock_item_id)
```

---

## Tabel: users

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | varchar | Nama lengkap |
| username | varchar UNIQUE | Digunakan untuk login |
| email | varchar UNIQUE | Opsional, belum dipakai untuk login |
| password | varchar | Bcrypt hash |
| role | varchar | `Superadmin` / `Petugas Persediaan` / `Ketua Tim` / `Ketua Tim Kerja` |
| section | varchar NULL | Seksi/unit kerja user |
| status | varchar | `active` / `inactive` (ditambah via UserController) |
| remember_token | varchar NULL | Laravel remember me |
| created_at, updated_at | timestamp | |

---

## Tabel: stock_items

Master data stok barang (model lama, digunakan oleh `RequestController`).

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| category | varchar | Kategori barang |
| code | varchar | Kode persediaan (non-unique setelah migration 2026-07-14) |
| name | varchar | Nama barang |
| qty | integer | Jumlah stok saat ini |
| unit | varchar | Satuan (RIM, PCS, BOX, dll.) |
| storage_location | varchar NULL | Lokasi penyimpanan fisik |
| last_updated | date NULL | Tanggal update terakhir |
| created_at, updated_at | timestamp | |

> **Catatan:** Ada dua model untuk stok — `StockItem` dan `Barang`. `Barang` adalah model yang dipakai `StokFinalizationService`. Keduanya mungkin merujuk tabel yang sama atau berbeda. **Perlu klarifikasi.**

---

## Tabel: barang

Digunakan oleh `StokFinalizationService` dan `StockController@search`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| code | varchar | Kode persediaan |
| name | varchar | Nama barang |
| category | varchar NULL | Kategori |
| qty | integer | Jumlah stok |
| unit | varchar | Satuan |
| is_active | boolean | Apakah aktif di katalog |
| storage_location | varchar NULL | Lokasi penyimpanan |
| last_updated | timestamp NULL | |
| last_upload_id | bigint NULL | FK ke stok_uploads |
| created_at, updated_at | timestamp | |

---

## Tabel: kategori_barang

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| nama | varchar UNIQUE | Nama kategori |
| created_at, updated_at | timestamp | |

---

## Tabel: kode_persediaan

Master referensi kode persediaan untuk fuzzy-match saat verifikasi Excel upload.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| kategori_barang_id | bigint NULL FK | Referensi ke `kategori_barang` |
| kode | varchar UNIQUE | Kode persediaan (contoh: `1010101001`) |
| nama_barang | varchar | Nama barang standar |
| created_at, updated_at | timestamp | |

---

## Tabel: stok_uploads

Header untuk setiap batch upload Excel.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| file_name_original | varchar | Nama file asli yang diupload |
| file_name_stored | varchar | Nama file di storage |
| user_id | bigint FK | User yang mengupload |
| upload_date | datetime | Waktu upload |
| sheets_count | integer | Jumlah sheet di file |
| rows_count | integer | Total baris data |
| valid_rows_count | integer | Baris yang valid |
| error_rows_count | integer | Baris dengan error |
| rejected_rows_count | integer | Baris yang ditolak |
| current_step | integer | Step stepper saat ini (1-4) |
| status | varchar | `Draft` / `Perlu Perbaikan` / `Menunggu Verifikasi` / `Siap Difinalisasi` / `Selesai` / `Dibatalkan` |
| cancelled_at | datetime NULL | Waktu pembatalan |
| deleted_at | timestamp NULL | SoftDelete |
| created_at, updated_at | timestamp | |

---

## Tabel: stok_upload_details

Detail setiap baris dari file Excel yang diupload.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| stok_upload_id | bigint FK | Referensi ke `stok_uploads` |
| sheet_name | varchar | Nama sheet asal |
| supplier | varchar NULL | Nama supplier dari header sheet |
| no_urut | integer NULL | Nomor urut di sheet |
| kode_persediaan_excel | varchar NULL | Kode yang tertulis di file Excel |
| suggested_kode_persediaan | varchar NULL | Kode yang disuggest oleh sistem |
| nama_barang | varchar | Nama barang |
| qty | integer | Jumlah |
| unit | varchar | Satuan |
| storage_location | varchar NULL | Lokasi penyimpanan |
| price_unit | decimal(15,2) | Harga satuan |
| price_unit_taxed | decimal(15,2) NULL | Harga satuan setelah pajak |
| total_excel | decimal(15,2) | Total dari file Excel |
| total_calculated | decimal(15,2) | Total dihitung ulang oleh sistem |
| is_taxed | boolean | Apakah dikenakan PPN |
| status_validation | varchar | `Menunggu Verifikasi` / `Perlu Perbaikan` |
| status_verification | varchar | `Pending` / `Setuju` / `Perbaiki` / `Tolak` |
| verified_kode_persediaan | varchar NULL | Kode yang dikonfirmasi oleh petugas |
| notes_error | text NULL | Catatan error validasi |
| is_duplicate | boolean | Apakah terdeteksi duplikat dalam batch |
| created_at, updated_at | timestamp | |

---

## Tabel: stok_histories

Riwayat perubahan stok.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| stock_item_id | bigint FK | Referensi ke `stock_items` |
| stok_upload_id | bigint NULL FK | Batch upload penyebab perubahan |
| qty_change | integer | Delta perubahan qty (positif = tambah, negatif = kurang) |
| qty_before | integer | Stok sebelum perubahan |
| qty_after | integer | Stok setelah perubahan |
| type | varchar | `Upload Excel` / `BON Digital` / `Penyesuaian` |
| notes | varchar NULL | Catatan tambahan |
| created_at, updated_at | timestamp | |

---

## Tabel: bon_headers

Header untuk satu BON (bisa multi-item).

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| bon_no | varchar UNIQUE | Nomor BON (format: `BON/YYYY/MM/DD/NNN`) |
| user_id | bigint NULL FK | User pembuat BON |
| section | varchar | Seksi pemohon |
| requester | varchar | Nama pemohon |
| date | date | Tanggal BON |
| status | varchar | `Draft` / `Menunggu Verifikasi` / `Disetujui` / `Diproses` / `Selesai` / `Ditolak` |
| keperluan | text NULL | Alasan/keperluan permintaan |
| catatan | text NULL | Catatan tambahan |
| last_updated | date NULL | |
| created_at, updated_at | timestamp | |

---

## Tabel: item_requests

Detail item dalam sebuah BON.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| bon_header_id | bigint NULL FK | Referensi ke `bon_headers` |
| bon_no | varchar | Nomor BON (denormalized dari header) |
| user_id | bigint NULL FK | |
| stock_item_id | bigint NULL FK | Referensi ke `stock_items` |
| section | varchar | |
| item_name | varchar | Nama barang yang diminta |
| qty_requested | integer | Jumlah yang diminta |
| qty_available | integer | Stok tersedia saat BON dibuat |
| qty_fulfilled | integer | Jumlah yang terpenuhi |
| qty_to_procure | integer | Jumlah yang perlu diadakan |
| stock_allocated | boolean | Apakah stok sudah dialokasikan |
| unit | varchar | |
| status | varchar | Status item request |
| notes | text NULL | |
| verifier_notes | text NULL | Catatan dari verifikator |
| procurement_method | varchar NULL | `Pasar` / `Vendor` / `ATK` |
| vendor_name | varchar NULL | Nama vendor (jika Pengadaan Vendor) |
| date | date | |
| requester | varchar | |
| last_updated | date NULL | |
| created_at, updated_at | timestamp | |

---

## Tabel: distributions

Catatan distribusi fisik stok ke pemohon.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| item_request_id | bigint FK | |
| stock_item_id | bigint FK | |
| qty_distributed | integer | |
| distributed_by | varchar | Nama petugas yang mendistribusikan |
| distributed_at | date | |
| notes | text NULL | |
| created_at, updated_at | timestamp | |

---

## Tabel: procurements

Catatan pengadaan untuk item yang tidak tersedia di stok.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| item_request_id | bigint FK | |
| method | varchar | `Pasar` / `Vendor` / `ATK` |
| qty | integer | |
| status | varchar | `Diproses` / `Diterima` / `Dibatalkan` |
| invoice_no | varchar NULL | |
| bast_name | varchar NULL | Nomor BAST |
| bast_date | date NULL | |
| contract_no | varchar NULL | Nomor kontrak (Vendor) |
| processed_by | varchar | |
| procurement_date | date | |
| created_at, updated_at | timestamp | |

---

## Tabel: receipts

Data kuitansi (manual atau dari OCR).

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| invoice_no | varchar UNIQUE | Nomor nota/faktur |
| store_name | varchar | Nama toko/supplier |
| date | date | Tanggal kuitansi |
| is_taxed | boolean | Apakah ada PPN |
| tax_rate | decimal(5,2) | Persentase PPN |
| subtotal | decimal(15,2) | |
| tax_amount | decimal(15,2) | |
| total | decimal(15,2) | |
| is_verified | boolean | Sudah diverifikasi atau belum |
| status | varchar | Status kuitansi |
| method | varchar NULL | Metode pembayaran |
| bast_name | varchar NULL | Nama BAST |
| bast_date | date NULL | Tanggal BAST |
| created_at, updated_at | timestamp | |

---

## Tabel: receipt_items

Item dalam kuitansi.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| receipt_id | bigint FK | |
| name | varchar | Nama item |
| qty | integer | |
| price | decimal(15,2) | Harga satuan |
| subtotal | decimal(15,2) NULL | |
| created_at, updated_at | timestamp | |

---

## Tabel: receipt_documents

Dokumen fisik kuitansi yang diupload untuk diproses OCR.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| receipt_id | bigint NULL FK | Terhubung ke `receipts` setelah verifikasi |
| uploaded_by | bigint NULL FK | |
| original_filename | varchar | |
| storage_path | varchar | Path di storage Laravel |
| mime_type | varchar | |
| size_bytes | bigint unsigned | |
| sha256 | varchar INDEX | Hash untuk deteksi duplikat |
| status | varchar INDEX | `uploaded` / `queued` / `processing` / `needs_review` / `verified` / `failed` |
| ocr_engine | varchar NULL | Engine yang dipakai |
| ocr_engine_version | varchar NULL | |
| raw_text | longtext NULL | Teks mentah dari OCR |
| raw_result | json NULL | Hasil OCR lengkap (bounding box, confidence) |
| parsed_result | json NULL | Hasil parsing terstruktur |
| overall_confidence | decimal(5,4) NULL | Skor kepercayaan OCR (0.0–1.0) |
| error_message | text NULL | Pesan error jika OCR gagal |
| attempts | integer unsigned | Jumlah percobaan OCR |
| processed_at | timestamp NULL | |
| verified_at | timestamp NULL | |
| created_at, updated_at | timestamp | |

---

## Tabel: audit_logs

Log audit untuk aksi-aksi sensitif.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| user_id | bigint NULL FK | |
| action | varchar | Nama aksi (contoh: `Finalisasi Stok Excel`) |
| description | text | Deskripsi detail |
| ip_address | varchar NULL | IP address klien |
| created_at, updated_at | timestamp | |

---

## Tabel: history_logs

Log history lama (prototype). **Catatan:** Redundan dengan `audit_logs`, perlu dikonsolidasikan.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| actor | varchar | Nama pelaku (string, bukan FK) |
| action | varchar | |
| details | text | |
| created_at, updated_at | timestamp | |

---

## Tabel: bon_status_histories

Riwayat perubahan status BON.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| bon_header_id | bigint FK | |
| status_before | varchar NULL | |
| status_after | varchar | |
| changed_by | varchar | Nama user yang mengubah |
| notes | text NULL | |
| created_at, updated_at | timestamp | |

---

## Catatan Penting

1. **Dua model stok:** `StockItem` (tabel `stock_items`) dan `Barang` (tabel `barang`). Keduanya muncul di codebase dengan fungsi yang tumpang tindih. Ini adalah technical debt yang perlu dikonsolidasikan.
2. **Status sebagai string:** Semua kolom status menggunakan `varchar` tanpa constraint ENUM di database. Rentan typo. Direkomendasikan migrasi ke ENUM atau pakai PHP Backed Enum konsisten.
3. **Soft Delete:** Hanya `stok_uploads` yang menggunakan SoftDeletes. Model lain hard delete.
4. **PostgreSQL ILIKE:** `StockController@search` menggunakan `ilike` yang hanya ada di PostgreSQL. Saat pakai SQLite/MySQL akan error.
