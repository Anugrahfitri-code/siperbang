# Struktur Proyek SIPERBANG

Dokumen ini menjelaskan struktur aktif setelah audit folder menyeluruh. Struktur mengikuti batas tanggung jawab aplikasi, bukan sekadar jenis ekstensi file.

## Root proyek

Root hanya memuat entry point dan konfigurasi tingkat proyek.

| Lokasi | Fungsi |
|---|---|
| `artisan` | CLI Laravel |
| `composer.json`, `composer.lock` | Dependensi PHP |
| `package.json`, `package-lock.json` | Dependensi frontend |
| `vite.config.js` | Build React, Tailwind, dan aset Laravel |
| `docker-compose.yml` | Orkestrasi service lokal |
| `setup.sh`, `setup.bat` | Setup awal lintas platform |
| `README.md`, `CHANGELOG.md` | Informasi utama proyek |

File cache, output build, ZIP, log, hasil OCR, dan data percobaan tidak boleh disimpan di root.

## Backend Laravel

```text
app/
├── Enums/
│   └── Receipt/              enum domain kuitansi
├── Exceptions/
│   ├── Inventory/            exception impor persediaan
│   └── Ocr/                  exception komunikasi OCR
├── Http/
│   ├── Controllers/
│   │   ├── Api/              controller respons JSON
│   │   ├── Web/              controller Blade dan download web
│   │   └── Controller.php    base controller Laravel
│   ├── Middleware/           middleware aplikasi
│   └── Requests/
│       └── Inventory/        Form Request modul persediaan
├── Jobs/
│   └── Receipt/              queue job domain kuitansi
├── Models/                   model Eloquent
├── Providers/                service provider Laravel
├── Services/
│   ├── Inventory/            impor, kode, finalisasi, pembatalan stok
│   ├── Ocr/                  klien layanan OCR
│   └── Receipt/              ekspor dan sinkronisasi kuitansi
└── Support/
    └── Inventory/            katalog dan helper domain persediaan
```

`app/Models/` tetap datar. Struktur tersebut mengikuti konvensi Laravel dan menjaga relasi model mudah ditemukan. Model tidak dipindahkan ke subfolder karena tidak ada file non-model di dalamnya.

## Route

| Lokasi | Fungsi |
|---|---|
| `routes/web.php` | Halaman React, halaman Blade, download, dan fallback SPA |
| `routes/api.php` | Login berbasis sesi dan seluruh endpoint `/api/*` |
| `routes/console.php` | Perintah console |

`routes/api.php` dimuat dari `routes/web.php`. Cara ini mempertahankan middleware `web` karena autentikasi aplikasi memakai session cookie.

## Frontend React

```text
resources/js/
├── App.tsx
├── main.tsx
├── features/
│   ├── audit/components/
│   ├── auth/components/
│   ├── dashboard/components/
│   ├── inventory/components/
│   ├── inventory-upload/components/
│   ├── receipts/components/
│   ├── reports/components/
│   ├── requests/components/
│   └── users/components/
└── shared/
    ├── api.ts
    ├── types.ts
    └── components/
        ├── branding/
        ├── feedback/
        └── layout/
```

Aturan frontend:

1. Komponen khusus modul masuk ke `features/<fitur>/components/`.
2. Komponen yang dipakai lintas fitur masuk ke `shared/components/`.
3. Klien HTTP dan tipe lintas fitur masuk ke `shared/`.
4. Jangan membuat kembali folder `resources/js/components/` yang datar.
5. Jangan memindahkan komponen hanya berdasarkan ukuran file. Gunakan tanggung jawab fitur.

## Blade dan CSS

```text
resources/
├── css/app.css
└── views/
    ├── components/
    │   ├── feedback/
    │   └── navigation/
    ├── layouts/inventory.blade.php
    ├── master-barang/
    ├── stok-upload/
    ├── vendor/pagination/
    └── welcome.blade.php
```

`resources/views/vendor/pagination/` tetap berada pada lokasi bawaan Laravel. File tersebut merupakan override view framework, bukan file yang salah tempat.

## Database dan test Laravel

```text
database/
├── factories/
├── migrations/
└── seeders/
    ├── DatabaseSeeder.php
    └── Inventory/

tests/
├── Feature/
│   ├── Inventory/
│   └── Receipt/
├── Unit/
│   └── Inventory/
└── TestCase.php
```

Migrasi tetap datar karena Laravel mengurutkan migrasi berdasarkan nama timestamp. Seeder dan test dikelompokkan berdasarkan domain.

## Layanan OCR

```text
ocr-service/
├── app/                      source FastAPI dan OCR
├── tests/
│   ├── fixtures/             dokumen contoh test
│   ├── integration/          test endpoint FastAPI
│   └── unit/                 test parser murni
├── scripts/                  script menjalankan service dan test
├── tools/diagnostics/        alat diagnosis manual
└── debug-output/             output lokal yang diabaikan Git
```

Source `ocr-service/app/` tetap datar karena service hanya memiliki beberapa modul dengan tanggung jawab yang jelas. Pemecahan tambahan belum memberi manfaat yang sebanding dengan perubahan import.

## Aset publik

```text
public/
├── images/
│   ├── brand/
│   ├── landing/
│   └── team/
├── templates/
├── favicon.ico
└── index.php
```

Hanya aset yang mempunyai referensi runtime yang boleh berada di `public/`. Aset tanpa referensi ditempatkan pada `archive/assets/unused/`.

## Dokumentasi

```text
docs/
├── architecture/
├── development/
├── maintenance/
├── operations/
├── planning/
└── reference/
```

## Tooling dan arsip

| Lokasi | Fungsi |
|---|---|
| `scripts/` | Script pengembangan aplikasi utama |
| `tools/diagnostics/` | Pemeriksaan runtime, file, dan queue |
| `tools/manual-tests/` | Pengujian integrasi manual |
| `tools/legacy/` | Script satu kali yang tidak dipanggil runtime |
| `archive/prototypes/` | Prototipe terpisah |
| `archive/frontend/unused/` | Source frontend tanpa referensi aktif |
| `archive/assets/unused/` | Aset tanpa referensi runtime |
| `archive/legacy/laravel/` | Controller, view, request, dan test lama |

## Aturan penempatan file baru

- Tempatkan kode produksi pada domain dan layer yang sesuai.
- Pisahkan controller JSON dan controller web.
- Tempatkan service berdasarkan domain, bukan dalam folder datar.
- Tempatkan komponen React berdasarkan fitur.
- Tempatkan komponen lintas fitur pada `shared`.
- Tempatkan test sesuai jenis dan domain.
- Tempatkan data contoh pada folder `fixtures` milik test terkait.
- Jangan menaruh script diagnosis, dump, atau patch pada source runtime.
- Jangan menyimpan cache, log, ZIP, hasil build, atau output OCR dalam arsip proyek.
