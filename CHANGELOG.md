# Changelog

## Unreleased

### Added — Kelola Identitas Situs

- Versioning branding dengan status draft, scheduled, published, dan archived.
- Preview, publikasi langsung/terjadwal, riwayat, audit before/after, serta rollback satu aksi.
- Upload logo aplikasi, logo instansi, dan favicon yang divalidasi, di-resize, dan disimpan sebagai path relatif.
- Sanitasi HTML server-side, template footer bertoken aman, cache branding, dan source-of-truth backend untuk React, Blade, serta ekspor.
- Test authorization, whitelist API, sanitasi, upload, atomic cleanup, cache invalidation, jadwal publikasi, dan rollback.
- Verifikasi integritas build frontend dan script paket rilis yang mengecualikan secret/data runtime.

### Fixed — Kelola Identitas Situs

- Mendaftarkan endpoint simpan khusus Superadmin.
- Menghapus ketergantungan pada URL logo absolut, tahun footer statis, metadata/filename laporan hardcoded, dan editor `document.execCommand()`.
- Mencegah form menyimpan default ketika settings gagal dimuat, mengulang upload file lama, serta membocorkan object URL preview.
- Menambahkan `storage:link`, validasi build, dan penolakan `public/hot` pada deployment/release.

### Changed

- Merapikan struktur file non-runtime tanpa mengubah logika bisnis.
- Memindahkan prototipe lama ke `archive/prototypes/`.
- Memindahkan source frontend yang tidak dipakai ke `archive/frontend/unused/`.
- Memindahkan aset tanpa referensi runtime ke `archive/assets/unused/`.
- Mengelompokkan aset aktif ke `public/images/brand/`, `public/images/landing/`, dan `public/images/team/`.
- Memindahkan fixture OCR ke `ocr-service/tests/fixtures/`.
- Memindahkan script OCR ke `ocr-service/scripts/`.
- Memindahkan alat diagnostik OCR ke `ocr-service/tools/diagnostics/`.
- Memindahkan alat diagnostik dan script satu kali Laravel ke `tools/`.
- Memindahkan catatan perbaikan lama ke `docs/maintenance/`.
- Membuat favicon valid dari logo aplikasi.
- Memperbarui dokumentasi struktur proyek dan panduan alat bantu.

### Removed

- Arsip ZIP duplikat di dalam proyek.
- Output debug dan hasil OCR yang dapat dibuat ulang.
- Cache Python dan bytecode.
- File JSON yang menyamar sebagai gambar PNG.
- File JavaScript kosong yang tidak menjadi input build.
- Fixture PDF duplikat dengan isi identik.
