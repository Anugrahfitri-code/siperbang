# Implementasi Profesional Kelola Identitas Situs

## Ringkasan

Implementasi ini mengganti penyimpanan identitas tunggal menjadi versi branding yang dapat dibuat sebagai draft, dijadwalkan, dipublikasikan, diarsipkan, dan dipulihkan melalui rollback. Identitas aktif dipakai konsisten oleh React, Blade, favicon, metadata browser, footer, ekspor Excel, dan nama file laporan.

## Komponen utama

- `SiteBrandingVersion` menyimpan snapshot lengkap setiap versi.
- `SiteBrandingService` menangani publikasi transaksional, scheduler, rollback, audit, dan lifecycle aset.
- `SaveSiteBrandingRequest` dan `PublishSiteBrandingRequest` menegakkan otorisasi Superadmin serta validasi server.
- `HtmlSanitizer` membatasi HTML login pada tag dan style aman.
- `ImageOptimizer` memvalidasi, menulis ulang, mengecilkan, serta membuang metadata logo.
- `SettingsContext` menjadi sumber branding global frontend dan mengatur favicon/metadata dinamis.
- Halaman settings menyediakan draft, preview, jadwal publikasi, version history, dan rollback.

## Model status

| Status | Makna |
|---|---|
| `draft` | Belum aktif dan dapat diedit |
| `scheduled` | Menunggu `effective_from` |
| `published` | Versi aktif saat ini |
| `archived` | Versi historis yang dapat dijadikan sumber rollback |

Hanya satu versi yang boleh berstatus `published`. Publikasi menggunakan transaksi dan row lock. Versi aktif sebelumnya memperoleh `effective_until`, kemudian berubah menjadi `archived`.

## Keamanan

- Endpoint baca aktif tersedia publik hanya dengan key presentasi yang di-whitelist.
- Endpoint perubahan dilindungi session auth, role Superadmin, dan `FormRequest::authorize()`.
- Logo hanya PNG/JPEG/WebP, dibatasi dimensi/ukuran, serta di-reencode oleh GD.
- SVG ditolak.
- HTML login disanitasi di server dan kembali disanitasi saat render browser.
- Actor history selalu berasal dari user session server.
- Database menyimpan path aset relatif, bukan URL yang terikat domain.

## Operasional

Jalankan migrasi dan symlink storage:

```bash
php artisan migrate --force
php artisan storage:link
```

Aktifkan scheduler production:

```cron
* * * * * cd /var/www/siperbang && php artisan schedule:run >> /dev/null 2>&1
```

Validasi:

```bash
php artisan route:list --path=settings
php artisan schedule:list
php artisan branding:publish-due
```

## Pengujian yang disediakan

Feature tests mencakup public whitelist, autentikasi/role, sanitasi HTML, upload dan optimasi gambar, retensi aset untuk rollback, publikasi terjadwal, pencegahan audit ganda, metadata Blade, rollback, branding ekspor, dan perhitungan pajak.

Full test suite tetap harus dijalankan pada mesin/CI yang memiliki dependency Composer dan npm lengkap.
