# API_REFERENCE.md — Referensi Endpoint SIPERBANG

Base URL: `http://localhost:8000` (dev)

Semua endpoint `/api/*` menggunakan session cookie untuk autentikasi. Kirim CSRF token di header `X-XSRF-TOKEN` untuk method selain GET.

---

## Autentikasi

### POST /api/login
Login dan buat session.

**Request Body:**
```json
{
  "username": "petugas01",
  "password": "password123"
}
```

**Response 200:**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "Budi Santoso",
    "username": "petugas01",
    "role": "Petugas Persediaan",
    "section": "Tata Usaha"
  }
}
```

**Response 401:**
```json
{ "message": "Kredensial tidak valid" }
```

---

### POST /api/logout
Logout dan hapus session.

**Response 200:**
```json
{ "message": "Logout successful" }
```

---

### GET /api/user
Ambil data user yang sedang login.

**Auth:** Required

**Response 200:** Object user (sama seperti di atas)

**Response 401:**
```json
{ "message": "Unauthenticated" }
```

---

## Stok Upload (Excel Workflow)

> Semua endpoint ini menggunakan Blade view (bukan JSON) karena merupakan bagian dari stepper workflow. Response berupa redirect.

### GET /stok-upload
Halaman upload Excel.
**Auth:** `Petugas Persediaan`, `Superadmin`

### POST /stok-upload
Upload file Excel persediaan.
**Auth:** `Petugas Persediaan`, `Superadmin`
**Body:** `multipart/form-data` dengan field `file_excel` (`.xlsx`, maks 10MB)

### GET /stok-upload/template
Download template Excel kosong.

### GET /stok-upload/riwayat
Riwayat semua batch upload.

### GET /stok-upload/sampah
Daftar batch yang di-soft-delete.

### GET /stok-upload/{id}/stepper
Tampilan stepper (Step 2–4) untuk batch tertentu.

### POST /stok-upload/{id}/verifikasi
Simpan hasil verifikasi kode persediaan per baris.
**Body:** Array mapping `detail_id` → `verified_kode_persediaan`

### POST /stok-upload/{id}/finalisasi
Finalisasi batch — commit data ke stok master.

### POST /stok-upload/{id}/batal
Batalkan batch upload.

### POST /stok-upload/{id}/restore
Restore batch yang di-soft-delete.

### DELETE /stok-upload/{id}
Hapus permanen batch (hard delete, hanya dari trash).

---

## Stock API

### GET /api/stock
Daftar lengkap semua stok.
**Auth:** `Petugas Persediaan`, `Superadmin`

**Response 200:**
```json
[
  {
    "id": 1,
    "code": "1010101001",
    "name": "Kertas A4 80gr",
    "category": "ATK",
    "qty": 150,
    "unit": "RIM",
    "storage_location": "Rak A-1",
    "last_updated": "2026-07-15"
  }
]
```

---

### GET /api/stock/search
Pencarian stok dengan filter dan pagination.
**Auth:** Semua role

**Query Params:**
| Param | Type | Keterangan |
|---|---|---|
| `q` | string | Cari berdasarkan nama, kode, atau kategori |
| `category` | string | Filter kategori (exact match) |
| `status` | string | `tersedia` / `terbatas` / `kosong` |
| `per_page` | integer | Jumlah item per halaman (default: 20, maks: 100) |
| `page` | integer | Nomor halaman |

**Response 200:**
```json
{
  "data": [
    {
      "id": 1,
      "kode": "1010101001",
      "nama": "Kertas A4 80gr",
      "kategori": "ATK",
      "stok": 150,
      "satuan": "RIM",
      "lokasi": "Rak A-1",
      "status": "tersedia"
    }
  ],
  "current_page": 1,
  "last_page": 5,
  "total": 98
}
```

---

## Requests / BON Digital

### GET /api/requests
Daftar semua BON.
**Auth:** Semua role (Ketua Tim hanya lihat seksinya sendiri)

**Response 200:** Array of ItemRequest dengan relasi `distribution` dan `procurements`.

---

### POST /api/requests
Buat BON baru.
**Auth:** Semua role

**Request Body:**
```json
{
  "keperluan": "Kebutuhan rapat bulanan",
  "catatan": "Mohon diprioritaskan",
  "status": "Menunggu Verifikasi",
  "items": [
    {
      "barang_id": 1,
      "jumlah_diminta": 5,
      "catatan": "Merek apa saja"
    }
  ]
}
```

**Response 201:**
```json
{
  "bon_no": "BON/2026/07/19/001",
  "status": "Menunggu Verifikasi",
  "items": [...]
}
```

---

### PUT /api/requests/{id}/verify
Verifikasi BON oleh Ketua Tim.
**Auth:** `Ketua Tim`, `Ketua Tim Kerja`, `Superadmin`

**Request Body:**
```json
{
  "approved": true,
  "notes": "Disetujui"
}
```

---

### PUT /api/requests/{id}/process
Proses BON oleh Petugas Persediaan.
**Auth:** `Petugas Persediaan`, `Superadmin`

---

### POST /api/requests/{id}/distribute
Distribusikan stok untuk BON.
**Auth:** `Petugas Persediaan`, `Superadmin`

**Request Body:**
```json
{
  "qty_distributed": 5,
  "notes": "Diserahkan langsung"
}
```

---

### POST /api/requests/{id}/procure
Catat pengadaan untuk item yang tidak tersedia di stok.
**Auth:** `Petugas Persediaan`, `Superadmin`

**Request Body:**
```json
{
  "method": "Pasar",
  "vendor_name": null,
  "qty": 3,
  "invoice_no": "INV-001",
  "bast_name": "BAST-001",
  "bast_date": "2026-07-19"
}
```

---

### DELETE /api/requests/{id}
Hapus BON (soft delete atau hard delete bergantung status).
**Auth:** Pemilik BON atau `Superadmin`

---

## Receipt (Kuitansi)

### GET /api/receipts
Daftar semua kuitansi.
**Auth:** `Petugas Persediaan`, `Superadmin`

### POST /api/receipts
Buat kuitansi manual.
**Auth:** `Petugas Persediaan`, `Superadmin`

### PUT /api/receipts/{id}
Update kuitansi.

### DELETE /api/receipts/{id}
Hapus kuitansi.

---

## Receipt Documents (OCR)

### GET /api/receipt-documents
Daftar dokumen kuitansi yang diupload.
**Auth:** `Petugas Persediaan`, `Superadmin`

**Response 200:**
```json
[
  {
    "id": 1,
    "original_filename": "nota_toko_abc.jpg",
    "status": "verified",
    "attempts": 1,
    "overall_confidence": 0.92,
    "processed_at": "2026-07-19T02:00:00Z",
    "uploader": { "id": 2, "name": "Budi Santoso" }
  }
]
```

---

### POST /api/receipt-documents
Upload dokumen kuitansi untuk diproses OCR.
**Auth:** `Petugas Persediaan`, `Superadmin`
**Body:** `multipart/form-data` dengan field `document` (jpg/png/pdf, maks 10MB default)

**Response 202:**
```json
{
  "message": "Dokumen diterima dan dimasukkan ke antrean OCR.",
  "data": {
    "id": 1,
    "status": "queued"
  }
}
```

---

### GET /api/receipt-documents/{id}
Detail dokumen + hasil OCR (termasuk bounding box pages).
**Auth:** `Petugas Persediaan`, `Superadmin`

---

### PUT /api/receipt-documents/{id}/verify
Verifikasi manual hasil OCR.
**Auth:** `Petugas Persediaan`, `Superadmin`

---

### POST /api/receipt-documents/{id}/retry
Masukkan kembali dokumen ke antrean OCR.
**Auth:** `Petugas Persediaan`, `Superadmin`
**Syarat:** Status harus `failed` atau `uploaded`

**Response 202:**
```json
{
  "message": "Dokumen dimasukkan kembali ke antrean OCR.",
  "data": { "id": 1, "status": "queued", "attempts": 2 }
}
```

---

## Logs

### GET /api/logs
Daftar history log.
**Auth:** `Petugas Persediaan`, `Superadmin`

### POST /api/logs
Buat log entry baru.
**Auth:** `Petugas Persediaan`, `Superadmin`

**Request Body:**
```json
{
  "actor": "Budi Santoso",
  "action": "Update Stok",
  "details": "Menambahkan 50 RIM Kertas A4"
}
```

---

### GET /api/export-excel
Ekspor rekap kuitansi terverifikasi ke format CSV.
**Auth:** `Petugas Persediaan`, `Superadmin`

**Query Params:**
| Param | Type | Default | Keterangan |
|---|---|---|---|
| `year` | string | `2026` | Filter tahun (`All` untuk semua) |
| `month` | string | `All` | Filter bulan (1–12 atau `All`) |
| `search` | string | | Cari nama toko, no nota, atau nama barang |
| `annual` | string | | Set `true` untuk rekap tahunan |

**Response:** File CSV download

---

## User Management

> Semua endpoint di bawah ini hanya bisa diakses oleh **Superadmin**.

### GET /api/users
Daftar semua user.

### POST /api/users
Buat user baru. Password default: `password` (harus segera diubah).

**Request Body:**
```json
{
  "name": "Siti Rahayu",
  "username": "siti.rahayu",
  "role": "Ketua Tim",
  "section": "Keuangan",
  "status": "active"
}
```

### PUT /api/users/{id}
Update data user.

### DELETE /api/users/{id}
Hapus user.

---

## Status Codes

| Code | Arti |
|---|---|
| 200 | OK |
| 201 | Created |
| 202 | Accepted (async, e.g. OCR queued) |
| 401 | Unauthenticated |
| 403 | Forbidden (role tidak sesuai) |
| 409 | Conflict (e.g. retry OCR pada status yang salah) |
| 422 | Validation error |
| 500 | Server error |
