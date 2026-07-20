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

### Yang Perlu Diperbaiki (Saat Ini)
- [ ] Tidak ada pembatasan percobaan login (rate limiting) — rentan brute force
- [ ] Password default user baru adalah string literal `'password'` — harus dipaksa ganti
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

### Yang Perlu Diperbaiki
- [ ] Role disimpan sebagai string — tidak ada validasi enum di level database
- [ ] Tidak ada audit log untuk aksi login/logout/akses ditolak
- [ ] `StokUploadController` menggunakan method `authorizeRole()` sendiri, tidak menggunakan `RoleMiddleware` secara konsisten

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

### Yang Perlu Diperbaiki
- [ ] Nama file tersimpan menggunakan `time() . '_' . $originalName` — predictable dan bisa collision. Sebaiknya gunakan UUID
- [ ] Tidak ada pembatasan tipe file di level server (hanya validasi mime dari client)

---

## Secrets & Credential

- `APP_KEY` di-generate saat setup dan disimpan di `.env`
- OCR service token disimpan di `.env` sebagai `OCR_SERVICE_TOKEN`
- File `.env` sudah ada di `.gitignore` — tidak akan ter-commit

### Yang Perlu Diperbaiki
- [ ] Tidak ada rotasi secret secara berkala
- [ ] Tidak ada penggunaan secret manager (Vault, AWS Secrets Manager, dll.)

---

## Rate Limiting

Saat ini **tidak ada** rate limiting di semua endpoint.

### Yang Harus Ditambahkan
- [ ] Rate limit pada endpoint login (`/api/login`) — minimal 5 percobaan per menit per IP
- [ ] Rate limit pada endpoint upload OCR (file besar, resource intensif)
- [ ] Rate limit umum pada semua endpoint API

---

## Audit Trail

- `audit_logs` mencatat: finalisasi stok Excel
- `history_logs` mencatat: finalisasi stok (duplikat)
- `bon_status_histories` mencatat: perubahan status BON

### Yang Perlu Diperbaiki
- [ ] Audit log belum mencakup: login, logout, gagal login, akses ditolak, delete data, update user
- [ ] `history_logs` dan `audit_logs` redundan — perlu dikonsolidasikan
- [ ] `history_logs.actor` menggunakan string (nama), bukan FK ke `users` — data bisa inconsistent

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
- [ ] Ganti semua password default user
- [ ] Set OCR_SERVICE_TOKEN yang kuat (minimal 32 karakter random)
- [ ] Aktifkan HTTPS dan set `APP_URL` ke https://
- [ ] Konfigurasi security headers di nginx/Apache
- [ ] Aktifkan rate limiting di endpoint login dan upload
- [ ] Pastikan `.env` tidak bisa diakses via web
- [ ] Pastikan `storage/` tidak bisa diakses langsung via web (hanya `public/`)
- [ ] Review dan audit semua user dan role sebelum go-live
- [ ] Aktifkan logging terstruktur dan kirim ke sistem monitoring
