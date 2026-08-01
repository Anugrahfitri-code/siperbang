# Security Notice for Source Distribution

Jangan mendistribusikan arsip proyek yang memuat `.env`, token OCR, kredensial database, `APP_KEY`, database lokal, log, session, cache, dokumen kuitansi, atau file lain pada `storage/app/private`.

Arsip sumber awal yang menjadi dasar perbaikan ini mengandung artefak runtime dan credential. Apabila arsip tersebut pernah dibagikan di luar lingkungan tepercaya:

1. Rotasi password database.
2. Rotasi `OCR_SERVICE_TOKEN` pada Laravel dan layanan OCR.
3. Tinjau akses terhadap database dan dokumen kuitansi.
4. Pertimbangkan rotasi `APP_KEY` secara terencana; perubahan tersebut memutus session dan dapat memengaruhi data terenkripsi lama.
5. Pastikan production memakai `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, dan secret manager atau permission file yang ketat.

Gunakan `php scripts/package-release.php <output.zip>` untuk membuat paket source yang telah mengecualikan data sensitif.
