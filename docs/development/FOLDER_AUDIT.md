# Audit Kesesuaian Folder

Tanggal audit: 30 Juli 2026

Audit ini mencakup seluruh proyek aktif, arsip, dokumentasi, alat bantu, test, aset, dan layanan OCR. Audit tidak hanya memeriksa folder komponen React.

## Kesimpulan

ZIP versi 2 belum menyelesaikan seluruh masalah penempatan file. Folder React masih terlalu datar. Beberapa controller, service, route, test, view lama, dan dokumen juga belum mempunyai batas tanggung jawab yang jelas.

Versi 3 memperbaiki temuan tersebut. Tidak ditemukan file aktif yang masih berada pada folder yang bertentangan dengan fungsi utamanya. Beberapa folder sengaja tetap datar karena mengikuti konvensi Laravel atau karena ukurannya masih kecil. Alasannya dicatat pada bagian berikut.

## Audit komponen React

Folder aktif `resources/js/components/` sudah tidak digunakan. Komponen kini dikelompokkan berdasarkan fitur dan pemakaian bersama.

| Komponen | Lokasi final | Alasan |
|---|---|---|
| `HistoryLog.tsx` | `features/audit/components/` | Menampilkan aktivitas dan riwayat audit |
| `LoginScreen.tsx` | `features/auth/components/` | Menangani tampilan dan alur login |
| `DashboardStats.tsx` | `features/dashboard/components/` | Menampilkan statistik dashboard |
| `KetuaTimDashboard.tsx` | `features/dashboard/components/` | Dashboard khusus Ketua Tim |
| `DistributionProcurement.tsx` | `features/inventory/components/` | Menangani distribusi dan pengadaan persediaan |
| `RequesterStockList.tsx` | `features/inventory/components/` | Menampilkan katalog stok kepada pemohon |
| `StockChecking.tsx` | `features/inventory/components/` | Menangani pemeriksaan ketersediaan stok |
| `StepperReact.tsx` | `features/inventory-upload/components/` | Stepper proses unggah persediaan |
| `StockManagement.tsx` | `features/inventory-upload/components/` | Modul utama unggah dan pengelolaan stok |
| `UploadHistoryReact.tsx` | `features/inventory-upload/components/` | Riwayat unggah persediaan |
| `ReceiptOCRProcessor.tsx` | `features/receipts/components/` | OCR dan verifikasi kuitansi |
| `ReportExport.tsx` | `features/reports/components/` | Ekspor laporan |
| `BonDigitalForm.tsx` | `features/requests/components/` | Pembuatan bon permintaan |
| `BonMonitoringList.tsx` | `features/requests/components/` | Pemantauan bon permintaan |
| `UserManagement.tsx` | `features/users/components/` | Pengelolaan akun pengguna |
| `Logos.tsx` | `shared/components/branding/` | Identitas visual yang dipakai lintas fitur |
| `AlertDialog.tsx` | `shared/components/feedback/` | Dialog informasi yang dipakai lintas fitur |
| `ConfirmDialog.tsx` | `shared/components/feedback/` | Dialog konfirmasi yang dipakai lintas fitur |
| `Navbar.tsx` | `shared/components/layout/` | Bagian layout global |
| `Sidebar.tsx` | `shared/components/layout/` | Bagian layout global |
| `api.ts` | `shared/` | Klien HTTP bersama, bukan komponen |
| `types.ts` | `shared/` | Tipe data bersama, bukan komponen |

Seluruh 24 file TypeScript dan TSX aktif dapat ditelusuri dari `main.tsx`. Tidak ada import relatif yang hilang. File `data.ts`, `index.css`, dan hook yang tidak aktif tidak lagi berada dalam source runtime.

## Audit seluruh area proyek

| Area | Temuan versi 2 | Tindakan versi 3 | Status |
|---|---|---|---|
| React | Komponen lintas fitur dan komponen modul bercampur | Dipisahkan ke `features/` dan `shared/` | Sesuai |
| Blade components | Navigasi dan modal berada pada level yang sama | Dipisahkan ke `navigation/` dan `feedback/` | Sesuai |
| Layout Blade | Nama `main` terlalu umum | Diganti menjadi `layouts/inventory.blade.php` | Sesuai |
| Controller | Controller web, API, base controller, dan controller lama bercampur | Controller aktif dipisahkan ke `Api/` dan `Web/`; file lama diarsipkan | Sesuai |
| Route | Endpoint halaman dan JSON berada dalam satu file | Dipisahkan menjadi `routes/web.php` dan `routes/api.php` | Sesuai |
| Services | Service beberapa domain berada dalam folder datar | Dipisahkan ke `Inventory/`, `Receipt/`, dan `Ocr/` | Sesuai |
| Exception | Exception lintas domain bercampur | Dipisahkan ke `Inventory/` dan `Ocr/` | Sesuai |
| Enum | Enum kuitansi berada pada folder umum | Dipindahkan ke `Enums/Receipt/` | Sesuai |
| Job | Job OCR kuitansi berada pada folder umum | Dipindahkan ke `Jobs/Receipt/` | Sesuai |
| Form Request | Request unggah berada pada folder umum | Dipindahkan ke `Requests/Inventory/` | Sesuai |
| Support class | Katalog persediaan berada pada folder umum | Dipindahkan ke `Support/Inventory/` | Sesuai |
| Seeder | Seeder persediaan bercampur dengan entry point | Dipindahkan ke `seeders/Inventory/`; `DatabaseSeeder` tetap di root | Sesuai |
| Test Laravel | Test semua domain bercampur | Dipisahkan berdasarkan domain pada `Feature/` dan `Unit/` | Sesuai |
| Test OCR | Test endpoint, parser, dan fixture bercampur | Dipisahkan ke `integration/`, `unit/`, dan `fixtures/` | Sesuai |
| Script OCR | Script menjalankan service bercampur dengan source | Dipindahkan ke `ocr-service/scripts/` | Sesuai |
| Diagnosis OCR | Alat diagnosis bercampur dengan source | Dipindahkan ke `ocr-service/tools/diagnostics/` | Sesuai |
| Aset publik | Aset aktif dan tidak aktif bercampur | Aset aktif dikelompokkan ke `brand/`, `landing/`, dan `team/`; aset lain diarsipkan | Sesuai |
| Tooling | Diagnosis, test manual, dan patch lama bercampur | Dipisahkan ke `tools/diagnostics/`, `manual-tests/`, dan `legacy/` | Sesuai |
| Dokumentasi | Semua dokumen berada pada satu level | Dipisahkan ke `architecture/`, `development/`, `operations/`, `reference/`, `planning/`, dan `maintenance/` | Sesuai |
| Source lama | Controller, view, layout, dan test contoh masih aktif | Dipindahkan ke `archive/legacy/laravel/` | Sesuai |
| Prototipe | Prototipe bercampur dengan aplikasi utama | Dipindahkan ke `archive/prototypes/` sebagai proyek mandiri | Sesuai |
| Cache dan output | Cache Python dapat terbentuk saat test | Dihapus sebelum pembuatan ZIP dan sudah tercakup dalam `.gitignore` | Sesuai |

## Folder yang sengaja dipertahankan

Folder berikut tidak dipindahkan karena sudah mengikuti konvensi atau mempunyai alasan runtime yang kuat:

- `app/Models/` tetap datar karena seluruh isinya model Eloquent. Tidak ada controller, service, atau helper di dalamnya.
- `database/migrations/` tetap datar karena Laravel mengurutkan migrasi berdasarkan timestamp pada nama file.
- `config/` tetap datar karena seluruh isinya konfigurasi Laravel.
- `resources/views/vendor/pagination/` tetap pada lokasi vendor override yang ditentukan Laravel.
- `storage/` dan `bootstrap/cache/` tetap memakai struktur runtime Laravel.
- `ocr-service/app/` tetap datar karena hanya berisi modul source FastAPI dan OCR yang saling terkait. Jumlahnya masih kecil.
- `public/templates/` tetap menjadi lokasi template Excel yang dibaca dan diunduh saat runtime.
- `setup.sh`, `setup.bat`, `docker-compose.yml`, `composer.json`, dan `package.json` tetap berada di root karena merupakan entry point dan konfigurasi tingkat proyek.
- Struktur internal `archive/prototypes/desain-temp/` dipertahankan sebagai proyek mandiri. Folder tersebut tidak dimuat oleh aplikasi utama.

## File yang dikeluarkan dari source aktif

File berikut tidak mempunyai referensi aktif atau sudah digantikan oleh alur baru:

- controller `PerbaikiDataController`, `VerifikasiKodePersediaanController`, dan `StokPengadaanController`;
- request validasi yang hanya dipakai controller lama;
- view `preview`, `perbaiki`, dan `verifikasi` lama;
- layout starter yang tidak digunakan;
- test contoh bawaan Laravel;
- source frontend tidak aktif;
- aset visual tanpa referensi runtime.

File bersejarah tetap tersedia di bawah `archive/`. File tersebut tidak masuk autoload, route, import frontend, atau view aktif.

## Temuan route lama

Versi sebelumnya mempunyai route POST `/stok-upload/{id}/perbaiki` yang menunjuk ke method `saveFixes`. Method tersebut tidak tersedia pada controller aktif. Route rusak itu dihapus. Alias GET lama tetap tersedia sebagai redirect menuju stepper terpadu.

## Perlindungan terhadap perubahan perilaku

Perapian tidak mengubah logika komponen React yang dipindahkan. Audit membandingkan 22 file frontend setelah menormalkan import. Isi komponen sama.

Audit juga membandingkan 22 file PHP yang dipindahkan setelah menormalkan namespace dan import. Isi bisnis sama. Seluruh method pada controller stok upload lama dibandingkan dengan controller `Web` dan `Api` hasil pemisahan. Tidak ditemukan perubahan isi method.

## Batas audit

Audit ini berfokus pada penempatan file, namespace, import, route, view, aset, dan pemisahan source aktif dari arsip. Audit tidak memecah komponen besar, model Eloquent, atau service besar menjadi class baru karena tindakan tersebut mengubah desain aplikasi dan membutuhkan regression test yang lebih luas.
