# SECURITY.md — Kebijakan Keamanan SIPERBANG

## Melaporkan Vulnerability

Jika menemukan celah keamanan, **jangan buat issue publik**. Hubungi tim melalui:
- Email langsung ke pengelola sistem
- Atau laporkan secara internal ke Superadmin

Sertakan: deskripsi masalah, langkah reproduksi, dan dampak potensial.

---

## Autentikasi & Sesi

- Autentikasi menggunakan **Laravel Session-based Auth** (bukan token/JWT)
- Session di-regenerate setelah login (`session()->regenerate()`)
- Session di-invalidate dan token di-regenerate setelah logout
- Login menggunakan `username`, bukan email
- Password di-hash menggunakan **bcrypt** via Laravel `Hash::make()`
- Terdapat penegakan *active-user* (inactive user enforcement) pada rute aplikasi yang terlindungi (termasuk `/api/user`). Session dari user yang statusnya berubah menjadi Nonaktif akan ditolak.
- Klien tidak dapat menentukan identitas requester secara bebas; identitas otorisasi selalu diambil dari sesi server.

### Yang Perlu Diperbaiki (Saat Ini)
- [ ] Tidak ada kebijakan kompleksitas password
- [ ] Tidak ada two-factor authentication (2FA)

---

## Otorisasi (RBAC)

Sistem menggunakan **Role-Based Access Control** via `RoleMiddleware`:

| Role | Akses |
|---|---|
| Superadmin | Akses penuh ke semua fitur |
| Petugas Persediaan | Stok, kuitansi, BON processing, export |
| Ketua Tim | BON milik seksinya sendiri |
| Ketua Tim Kerja | Sama seperti Ketua Tim |

**Aturan penting:**
- Superadmin selalu bypass semua role check
- Ketua Tim hanya bisa melihat BON dari seksinya sendiri
- Endpoint user management hanya untuk Superadmin

### Peningkatan lanjutan
- [ ] Pertimbangkan enum/database constraint untuk nilai role.
- [ ] Lengkapi audit log untuk login, logout, gagal login, dan akses ditolak.
- [ ] Konsolidasikan otorisasi controller lama ke middleware/policy secara bertahap.

---

## Proteksi CSRF

- Laravel CSRF protection aktif secara default untuk semua form POST/PUT/DELETE
- Frontend harus menyertakan `X-XSRF-TOKEN` header pada setiap mutasi
- Route API di dalam `web.php` terlindungi CSRF via Laravel session

---

## Validasi Input

- Upload Excel: validasi mime type (`xlsx`), ukuran file via `UploadStokExcelRequest`
- Upload dokumen OCR: validasi mime type (jpg/png/pdf) dan ukuran via `ReceiptDocumentController`
- BON request: validasi via inline `$request->validate()`

### Yang Perlu Diperbaiki
- [ ] Beberapa controller masih menggunakan inline validation, sebaiknya pindah ke Form Request class
- [ ] Query pencarian stok menggunakan string interpolasi langsung: `"%{$q}%"` — aman karena dipakai di parameter binding Eloquent, tapi perlu dikonfirmasi

---

## Keamanan File Upload

- File Excel disimpan di `storage/private/uploads` (tidak bisa diakses langsung via URL)
- Dokumen OCR disimpan di path storage private
- SHA256 hash dihitung dan disimpan untuk setiap dokumen OCR (deteksi duplikat)
- Nama file disanitasi di `OcrServiceClient::sanitizeFilename()`

### Identitas situs

- Logo branding dibatasi ke PNG, JPEG, dan WebP menggunakan validasi server.
- Gambar ditulis ulang dengan PHP GD, diperkecil, dan metadata bawaan dibuang.
- SVG tidak diterima agar active content tidak disajikan langsung.
- Path aset disimpan relatif pada disk Laravel, bukan sebagai URL domain absolut.

### Peningkatan lanjutan
- [ ] Migrasikan nama file upload lama yang masih predictable ke UUID/ULID.
- [ ] Tambahkan pemindaian malware untuk dokumen upload berisiko tinggi bila aplikasi dibuka lebih luas.

---

## Secrets & Credential

- `APP_KEY` di-generate saat setup dan disimpan di `.env`
- OCR service token disimpan di `.env` sebagai `OCR_SERVICE_TOKEN`
- File `.env` ada di `.gitignore`, tetapi `.gitignore` tidak melindungi arsip ZIP yang dibuat secara manual.
- Gunakan `php scripts/package-release.php` untuk menghasilkan paket source tersanitasi.
- Paket source tidak boleh berisi `.env`, database runtime, kuitansi, log, token OCR, atau storage privat.

### Yang Perlu Diperbaiki
- [ ] Terapkan rotasi secret berkala dan segera rotasi bila pernah masuk arsip yang dibagikan.
- [ ] Gunakan secret manager pada production (Vault, AWS Secrets Manager, atau ekuivalen).

---

## Rate Limiting

- Rate limiting telah diimplementasikan pada endpoint login (`/api/login`) dan telah diverifikasi.
- Saat ini belum ada pembatasan khusus pada endpoint fungsional lainnya.

### Yang Harus Ditambahkan
- [ ] Rate limit pada endpoint upload OCR (file besar, resource intensif)
- [ ] Rate limit umum pada semua endpoint API

---

## Audit Trail

- `audit_logs` mencatat finalisasi stok Excel.
- `history_logs` mencatat aksi operasional dan perubahan versi identitas situs.
- `bon_status_histories` mencatat perubahan status BON.
- Endpoint pencatatan history tidak mempercayai `user_id` atau nama aktor dari browser; aktor diambil dari sesi server.
- Audit branding menyimpan versi, key yang berubah, versi sebelumnya, user, IP, request ID, dan user-agent tanpa menyalin binary gambar atau HTML penuh.

### Yang Perlu Diperbaiki
- [ ] Audit log belum mencakup seluruh login, logout, gagal login, akses ditolak, delete data, dan update user.
- [ ] `history_logs` dan `audit_logs` masih tumpang tindih; rencanakan konsolidasi skema.
- [ ] Pertimbangkan kebijakan retensi dan ekspor audit ke penyimpanan append-only.

## Penanganan Error / Error Leakage

Rute yang sensitif terhadap keamanan saat ini telah direview dan terverifikasi mengembalikan pesan error publik/generik ke sisi klien, tanpa mengekspos informasi eksepsi internal, log PDO, atau query SQL mentah.

---

## Integritas Stok / Data Integrity

**Non-bulk stock (Single Request):**
- Dilindungi oleh database transaction.
- Menggunakan *row locking* (`lockForUpdate`) saat pembacaan stok berjalan bersamaan.
- Konsistensi antara mutasi stok dan ledger (`StockHistory`) dijaga.
- *PostgreSQL concurrency acceptance* pada beban konkuren (non-bulk) berstatus PASS.

**Bulk / Finalization:**
- Endpoint usang `/api/stocks/bulk` telah dihapus.
- Validasi dan verifikasi batch dilakukan secara atomik.
- Finalisasi diserialisasi menggunakan *database locking* di tingkat BON.
- Sistem mencegah duplikasi finalisasi secara ketat.
- Pembaruan terhadap stok yang sama secara konkuren serta pembuatan item logis (kode aktivitas/persediaan) yang belum ada pada saat bersamaan diatur secara aman (*coordinated*).
- Status akhir BON, StockHistory, dan mutasi stok di-commit ke database secara atomik.

---

## Dependency / Supply-Chain Security

Sistem integrasi *continuous dependency security* memblokir potensi serangan melalui mekanisme berikut:
- **Composer**: `composer audit` bersifat *blocking*.
- **npm**: *Production dependency audit* bersifat *blocking*, dengan pengecualian *accepted-risk* yang teridentifikasi secara spesifik dan sementara.
- **Python OCR**: `pip-audit` bersifat *blocking*.
- **Container / Image**: Pemindaian kerentanan Trivy CRITICAL bersifat *blocking*, sedangkan HIGH bersifat *report*.
- Pemindaian dijadwalkan berjalan harian.
- Semua aksi GitHub eksternal (*external GitHub Actions*) menggunakan rujukan *full immutable commit SHA*.

*(Catatan: Blokade kerentanan di atas tidak menjanjikan risiko rantaian pasok nol secara permanen. Adopsi paket baru selalu wajib di-review).*

### Pengecualian Risiko Residual Sementara (Expiry: 2026-09-15)

**npm Accepted Risks:**
- `GHSA-rgw5-rvv9-x895` (brace-expansion)
- `GHSA-w5hq-g745-h8pq` (uuid)
  - Pengecualian ini didasarkan pada tinjauan ketidakterjangkauan (*reachability*) di dalam kode aplikasi spesifik saat ini. Pengecualian bersifat *advisory-specific*, tidak mengizinkan kerentanan masa depan dari paket yang sama, dan temuan CRITICAL akan tetap memblokir deployment.

**Trivy Exceptions:**
- `CVE-2025-37777`, `CVE-2026-52989`, `CVE-2026-53215`
  - Trivy memetakan kerentanan kernel Linux di atas pada dependensi `linux-libc-dev` di dalam image. Karena arsitektur container Docker menggunakan kernel host (bukan menjalankan kernel internal dari image), ini merupakan pengecualian aplikabilitas container yang sangat sempit (*narrow applicability exception*).
  - **PENTING:** Ini TIDAK berarti OS host otomatis aman. Validasi *patch* keamanan kernel host milik perusahaan tetap WAJIB dilakukan secara independen sebelum *go-live*.

Pengecualian risiko di atas kedaluwarsa pada **2026-09-15** dan wajib ditinjau, dihapus, atau diperpanjang melalui persetujuan secara spesifik.

---

## Security Headers

Pastikan server/nginx dikonfigurasi dengan header:

```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'
Strict-Transport-Security: max-age=31536000; includeSubDomains (hanya HTTPS)
```

---

## Checklist Keamanan Sebelum Deploy ke Production

- [ ] Set `APP_ENV=production` dan `APP_DEBUG=false`
- [x] Tidak menyediakan kredensial privileged/default password di source.
- [ ] Set OCR_SERVICE_TOKEN yang kuat (minimal 32 karakter random)
- [ ] Aktifkan HTTPS dan set `APP_URL` ke https://
- [ ] Konfigurasi security headers di nginx/Apache
- [x] Rate limiting pada endpoint login telah diterapkan.
- [ ] Evaluasi kebutuhan rate limiting khusus endpoint upload berdasarkan threat model dan beban operasional.
- [ ] Pastikan `.env` tidak bisa diakses via web dan tidak masuk ZIP/backup source
- [ ] Pastikan hanya `public/storage` yang dapat diakses web dan `storage/app/private` tetap tertutup
- [ ] Pastikan `public/hot` tidak ada di production
- [ ] Jalankan secret scan pada paket rilis
- [ ] Verifikasi Laravel Scheduler aktif untuk publikasi branding terjadwal
- [ ] Review dan audit semua user dan role sebelum go-live
- [ ] Aktifkan logging terstruktur dan kirim ke sistem monitoring
