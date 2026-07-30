# Tools

Folder ini berisi alat bantu yang tidak dijalankan oleh aplikasi saat runtime.

## Folder

- `diagnostics/`: pemeriksaan file, konfigurasi PHP, dan failed job.
- `manual-tests/`: pengujian endpoint atau integrasi secara manual.
- `legacy/`: script satu kali dari pekerjaan perbaikan lama.
- `bootstrap.php`: bootstrap Laravel untuk alat CLI.

## Aturan penggunaan

Jalankan alat dari root proyek. Pasang dependensi Composer terlebih dahulu.

Contoh:

```bash
php tools/diagnostics/php-upload-limits.php
php tools/diagnostics/mime-type.php path/to/file.pdf
php tools/manual-tests/ocr-client.php ocr-service/tests/fixtures/receipt-new-agung.pdf
```

Baca kode sebelum menjalankan file pada `legacy/`. Sebagian script mengubah data secara langsung dan dibuat untuk kasus tertentu.
