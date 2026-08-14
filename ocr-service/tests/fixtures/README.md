# Fixture OCR

Dokumen PDF di folder ini dipakai untuk pengujian integrasi OCR secara manual.
Dokumen tidak dibaca oleh aplikasi Laravel saat runtime.

> **PERHATIAN:** Periksa kembali isi dokumen PDF sebelum repository dipublikasikan karena fixture PDF (misal `receipt-new-agung.pdf`, dll.) dapat memuat data transaksi nyata.

## Synthetic Fixture

File `synthetic-smoke-receipt.png` dibuat khusus dari data fiktif untuk smoke test OCR dan tidak menggunakan data transaksi nyata (fully synthetic). Fixture ini aman digunakan untuk verifikasi deployment di production tanpa membocorkan data rahasia.
