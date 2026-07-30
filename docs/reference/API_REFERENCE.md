# Referensi Endpoint SIPERBANG

Base URL development: `http://localhost:8000`

Aplikasi menggunakan autentikasi session Laravel. Endpoint API tetap memakai middleware `web`, session cookie, dan CSRF untuk request yang mengubah data.

## Autentikasi

| Method | Endpoint | Fungsi |
|---|---|---|
| `POST` | `/api/login` | Login dengan `username` dan `password` |
| `POST` | `/api/logout` | Mengakhiri session |
| `GET` | `/api/user` | Mengambil pengguna yang sedang login |

## Endpoint untuk semua pengguna terautentikasi

| Method | Endpoint | Controller atau fungsi |
|---|---|---|
| `GET` | `/api/requests` | `RequestController@index` |
| `GET` | `/api/requests/bon` | `RequestController@indexBons` |
| `GET` | `/api/requests/bon/{id}` | `RequestController@showBon` |
| `GET` | `/api/logs` | `LogController@index` |
| `POST` | `/api/logs` | `LogController@store` |
| `GET` | `/api/stocks/search` | `StockController@search` |
| `GET` | `/api/stok-upload/riwayat` | `StokUploadController@apiRiwayat` |
| `GET` | `/api/stok-upload/stats` | `StokUploadController@apiStats` |
| `GET` | `/api/stok-upload/{id}/stepper-api` | `StokUploadController@apiStepper` |
| `POST` | `/api/stok-upload/{id}/verifikasi-api` | `StokUploadController@apiSaveVerifikasi` |
| `POST` | `/api/stok-upload/{id}/finalisasi-api` | `StokUploadController@apiFinalisasi` |

## Ketua Tim, Ketua Tim Kerja, dan Superadmin

| Method | Endpoint | Fungsi |
|---|---|---|
| `POST` | `/api/requests` | Membuat BON atau draft pengajuan |
| `PUT` | `/api/requests/bon/{id}` | Memperbarui draft BON |
| `DELETE` | `/api/requests/bon/{id}` | Menghapus draft BON |

## Petugas Persediaan dan Superadmin

### Stok dan permintaan

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/api/stocks` | Daftar stok |
| `POST` | `/api/stocks/bulk` | Menyimpan stok secara massal |
| `PUT` | `/api/requests/{itemRequest}/status` | Memperbarui status dan alokasi permintaan |
| `POST` | `/api/requests/{itemRequest}/distribute` | Mendistribusikan stok |
| `POST` | `/api/requests/{itemRequest}/procure` | Membuat proses pengadaan |
| `POST` | `/api/requests/{itemRequest}/procurements/{procurement}/complete` | Menyelesaikan pengadaan |
| `POST` | `/api/requests/{itemRequest}/reject` | Menolak item permintaan |
| `POST` | `/api/requests/{itemRequest}/complete-partial` | Menyelesaikan pemenuhan parsial |
| `GET` | `/api/inventory-codes` | Daftar kode persediaan resmi |

### Kuitansi

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/api/receipts` | Daftar kuitansi |
| `POST` | `/api/receipts` | Menyimpan kuitansi |
| `POST` | `/api/receipts/export-excel` | Mengekspor data kuitansi ke Excel |
| `PUT` | `/api/receipts/{receipt}/unverify` | Membatalkan status verifikasi kuitansi |
| `PUT` | `/api/receipts/{receipt}/items` | Memperbarui item kuitansi |

### Dokumen OCR

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/api/receipt-documents` | Daftar dokumen OCR |
| `POST` | `/api/receipt-documents` | Mengunggah dokumen OCR |
| `GET` | `/api/receipt-documents/{receiptDocument}` | Detail dokumen dan hasil OCR |
| `GET` | `/api/receipt-documents/{receiptDocument}/file` | Mengambil file dokumen |
| `PUT` | `/api/receipt-documents/{receiptDocument}/draft` | Menyimpan draft koreksi manual |
| `PUT` | `/api/receipt-documents/{receiptDocument}/verify` | Memverifikasi hasil OCR |
| `POST` | `/api/receipt-documents/{receiptDocument}/retry` | Mengulang proses OCR |
| `DELETE` | `/api/receipt-documents/{receiptDocument}` | Menghapus dokumen OCR |

### Ekspor log

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/api/export-excel` | Ekspor data melalui `LogController@exportExcel` |

## Superadmin

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/api/users` | Daftar pengguna |
| `POST` | `/api/users` | Membuat pengguna |
| `PUT` | `/api/users/{user}` | Memperbarui pengguna |
| `DELETE` | `/api/users/{user}` | Menghapus pengguna |

## Endpoint web dan Blade

Endpoint berikut tidak memakai prefix `/api`.

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/stok-upload` | Halaman upload Excel |
| `POST` | `/stok-upload` | Memproses upload Excel |
| `GET` | `/stok-upload/template` | Mengunduh template Excel |
| `GET` | `/stok-upload/riwayat` | Riwayat upload |
| `GET` | `/stok-upload/sampah` | Daftar upload yang dihapus sementara |
| `GET` | `/stok-upload/{id}/stepper` | Stepper pemeriksaan dan finalisasi |
| `POST` | `/stok-upload/{id}/verifikasi` | Menyimpan verifikasi kode |
| `POST` | `/stok-upload/{id}/finalisasi` | Finalisasi batch |
| `POST` | `/stok-upload/{id}/batalkan` | Membatalkan transaksi selesai |
| `DELETE` | `/stok-upload/{id}` | Soft delete batch yang dapat dihapus |
| `POST` | `/stok-upload/{id}/restore` | Memulihkan batch dari sampah |
| `DELETE` | `/stok-upload/{id}/force` | Menghapus batch secara permanen |
| `GET` | `/master-barang` | Halaman master barang |
| `GET` | `/master-barang/search` | Pencarian master barang |
| `POST` | `/master-barang/{id}/update` | Memperbarui master barang |
| `POST` | `/master-barang/{id}/delete` | Menghapus master barang |

## Alias kompatibilitas

URL GET lama berikut tetap tersedia dan mengarahkan pengguna ke stepper terpadu:

- `/stok-upload/{id}/preview`
- `/stok-upload/{id}/verifikasi`
- `/stok-upload/{id}/perbaiki`

## Catatan validasi

Detail field request mengikuti rule validasi pada controller dan Form Request terkait. Gunakan `php artisan route:list` untuk memastikan route pada environment yang sedang dijalankan.
