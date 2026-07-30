# Legacy Laravel Files

Folder ini menyimpan kode Laravel lama yang tidak lagi terhubung ke route, controller aktif, atau alur build saat ini.

Isi utama:

- controller alur stok lama yang sudah digantikan oleh `StokUploadController` dan stepper terpadu;
- request validasi yang hanya dipakai controller lama;
- view Blade lama untuk preview, perbaikan, dan verifikasi terpisah;
- layout starter yang tidak lagi dipakai;
- test contoh bawaan framework.

File di folder ini tidak dimuat oleh autoload produksi dan tidak dijalankan oleh test suite aktif. Simpan sebagai referensi historis. Jangan mengembalikannya ke source aktif tanpa menambah route, memperbaiki dependensi, dan menjalankan regression test.
