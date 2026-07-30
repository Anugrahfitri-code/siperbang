# Changelog

## Unreleased

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
