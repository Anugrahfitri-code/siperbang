# Rencana Refaktor File Monolitik (REFACTORING_PLAN.md)

Audit menunjukkan beberapa file memiliki ukuran yang sangat besar dan monolitik. File-file ini perlu dipecah secara bertahap untuk memudahkan *maintenance* dan kolaborasi tim. 

Berikut adalah panduan arsitektur untuk memecah file-file tersebut:

## 1. `resources/js/App.tsx` (Frontend Router & State)
**Status Saat Ini:** Menangani routing, autentikasi, layout, state management global, dan pemanggilan API.
**Langkah Refaktor:**
- **Extract API Calls:** Pindahkan fungsi fetch seperti `loadData` ke *custom hooks* (contoh: `useAppData.ts`).
- **Extract Routing:** Buat komponen `AppRouter.tsx` khusus untuk menangani pergantian tab/halaman berdasarkan *role*.
- **State Management:** Pisahkan state global (seperti `currentUser`, `currentRole`) menggunakan React Context atau Zustand ke `AuthContext.tsx`.

## 2. `resources/js/features/receipts/components/ReceiptOCRProcessor.tsx`
**Status Saat Ini:** Menangani upload UI, proses canvas/gambar OCR, form verifikasi nota, penyamaan nama barang, dan penyimpanan data.
**Langkah Refaktor:**
- **UI Components:** Pecah menjadi `<OCRImagePreview />`, `<OCRDataForm />`, `<OCRStatusBadge />`.
- **Custom Hooks:** Buat `useOCRProcessor.ts` untuk menangani logika upload dan status loading/error.
- **Form Logic:** Ekstrak logika *matching* nama barang dengan database stok ke `utils/inventoryMatcher.ts`.

## 3. `ocr-service/app/receipt_parser.py` (FastAPI)
**Status Saat Ini:** Berisi logika routing, ekstraksi teks PaddleOCR, pembersihan string, regex parsing, dan response mapping dalam satu file.
**Langkah Refaktor:**
- `routers/receipt.py`: Hanya berisi endpoint API FastAPI.
- `services/ocr_engine.py`: Logika pemanggilan library PaddleOCR.
- `utils/regex_parser.py`: Kumpulan regex untuk nomor nota, tanggal, toko, dan harga.
- `models/receipt.py`: Pydantic models untuk validasi output.

## 4. `app/Http/Controllers/ReceiptDocumentController.php` & `RequestController.php` (Laravel)
**Status Saat Ini:** Controller melakukan terlalu banyak bisnis logic, manipulasi array, dan validasi manual.
**Langkah Refaktor:**
- **Form Requests:** Pindahkan validasi ke `app/Http/Requests/...`
- **Service Pattern:** Pindahkan logika bisnis (seperti pembaruan stok dari bon) ke `app/Services/RequestService.php` dan `ReceiptService.php`.
- **Controller:** Hanya bertugas memanggil Service layer dan mengembalikan Response (Maks 100 baris per file).

Lakukan refaktor ini satu demi satu (satu *pull request* untuk satu file) dan pastikan pengujian berjalan sukses setelah setiap perubahan.
