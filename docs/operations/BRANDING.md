# Operasional Identitas Situs dan Branding Tahunan

## Tujuan

Modul ini mengelola identitas aplikasi secara terpusat tanpa menimpa riwayat. Identitas aktif digunakan oleh React, Blade, favicon, metadata halaman, footer, nama berkas ekspor, dan metadata workbook Excel.

## Hak akses

- `GET /api/settings` bersifat publik karena halaman login membutuhkan identitas aktif.
- Semua endpoint perubahan, versi, publikasi, rollback, dan penghapusan hanya tersedia untuk role `Superadmin`.
- Otorisasi ditegakkan oleh middleware route dan `FormRequest::authorize()`.

## Alur pergantian tahunan

1. Buka **Superadmin → Kelola Identitas Situs**.
2. Buat label yang mudah diaudit, misalnya `Identitas 2027`.
3. Isi nama aplikasi, nama instansi, teks login, dan template footer.
4. Unggah logo aplikasi, logo instansi, serta favicon.
5. Periksa pratinjau desktop yang tersedia pada form.
6. Pilih salah satu tindakan:
   - **Simpan sebagai Draft** untuk review internal.
   - **Publikasikan Sekarang** untuk aktivasi langsung.
   - Isi **Tanggal Mulai Berlaku** lalu publikasikan untuk status `scheduled`.
7. Setelah pergantian, versi sebelumnya otomatis menjadi `archived` dan tetap tersedia untuk rollback.

## Format aset

Format yang diterima:

- PNG
- JPG/JPEG
- WebP

Batas:

- Logo aplikasi dan instansi: maksimal 2 MB dan 2000×2000 piksel.
- Favicon: maksimal 1 MB dan 1024×1024 piksel.

Server menulis ulang gambar menggunakan PHP GD, membuang metadata yang tidak dibutuhkan, dan menurunkan dimensi maksimum menjadi:

- 1600 piksel untuk logo.
- 512 piksel untuk favicon.

SVG sengaja tidak diterima agar active content tidak disajikan langsung ke browser.

## Template footer

Token yang didukung:

| Token | Nilai |
|---|---|
| `{year}` | Tahun server saat halaman dirender |
| `{app_name}` | Nama aplikasi aktif |
| `{instansi_name}` | Nama singkat instansi |
| `{instansi_full_name}` | Nama lengkap instansi |

Contoh:

```text
© {year} {instansi_name}. Seluruh hak cipta dilindungi.
```

## Publikasi terjadwal

Laravel Scheduler harus aktif di production:

```cron
* * * * * cd /var/www/siperbang && php artisan schedule:run >> /dev/null 2>&1
```

Perintah operasional:

```bash
php artisan schedule:list
php artisan branding:publish-due
```

Aktivasi memakai transaksi dan row lock. Bila scheduler dan Superadmin memproses versi yang sama secara bersamaan, versi tidak dipublikasikan atau dicatat dua kali.

## Rollback

Rollback hanya tersedia pada versi `archived`. Sistem membuat versi publikasi baru dari snapshot arsip; versi lama tidak diubah. Pola ini mempertahankan urutan audit dan rentang masa berlaku.

Aset lama tidak dihapus selama masih direferensikan oleh versi aktif atau arsip. Penghapusan file hanya dilakukan untuk aset pada prefix `branding/` yang sudah tidak dipakai versi mana pun.

## Jejak audit

Aksi berikut dicatat pada `history_logs`:

- pembuatan dan pembaruan draft;
- penjadwalan;
- publikasi;
- pembuatan dan publikasi rollback;
- penghapusan draft/versi terjadwal.

Detail mencakup ID versi, label, key yang berubah, versi sebelumnya, user ID, nama aktor, IP, request ID bila tersedia, dan user-agent. Payload HTML penuh atau binary gambar tidak dimasukkan ke audit log.

## Pemeriksaan setelah deploy

```bash
php artisan migrate:status
php artisan storage:link
php artisan route:list --path=settings
php artisan schedule:list
curl -f https://domain.example/api/settings
```

Periksa juga:

- `public/hot` tidak ada;
- `public/storage` dapat dibaca web server;
- logo tampil pada login, navbar, dan halaman Blade;
- favicon dan judul tab berubah;
- ekspor Excel memakai nama aplikasi aktif;
- rollback dari satu versi arsip berhasil.

## Cache dan storage

- Branding aktif di-cache lintas-request selama enam jam dengan key `site_branding.active.v1` dan selalu dihapus saat publikasi/rollback.
- Kegagalan cache tidak membuat halaman branding gagal; service kembali membaca database.
- Disk aset diatur melalui `SITE_BRANDING_DISK` (default `public`). Database menyimpan path relatif, bukan URL domain.
- Untuk disk `public`, jalankan `php artisan storage:link` agar URL `/storage/...` dapat diakses.

## Integritas frontend

Sesudah perubahan React/TypeScript, jalankan:

```bash
npm ci
npm run typecheck
npm run lint
npm run build
npm run verify:build
```

Build menyimpan hash source dan hash artefak pada `public/build/build-meta.json`. Paket rilis dengan `--include-build` ditolak bila bundle tidak berasal dari source yang sama.
