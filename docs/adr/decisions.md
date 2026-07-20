# ADR-001: Arsitektur Monolith Laravel + React SPA

**Tanggal:** 2026-07  
**Status:** Accepted  
**Pembuat Keputusan:** Tim SIPERBANG

## Konteks

SIPERBANG membutuhkan aplikasi web untuk manajemen persediaan internal. Tim relatif kecil, kebutuhan awal terfokus pada satu instansi/organisasi.

## Keputusan

Menggunakan **monolith Laravel** sebagai backend yang juga meng-host React SPA. Semua route (web dan API) ada dalam satu aplikasi Laravel. React dikompilasi oleh Vite dan di-serve melalui satu Blade template (`welcome.blade.php`).

## Alasan

- Tim kecil — microservices penuh akan menambah overhead operasional yang tidak perlu
- Laravel proven untuk aplikasi CRUD enterprise skala menengah
- SPA approach memungkinkan UX yang responsif tanpa perlu full-stack framework terpisah
- Monolith lebih mudah di-deploy, di-debug, dan di-maintain oleh tim kecil
- Jika skala bertambah, monolith modular bisa di-extract menjadi service secara inkremental

## Konsekuensi

- Semua API route ada di `web.php` (bukan `api.php`) — autentikasi via session, bukan token
- Harus hati-hati dengan CSRF karena semua request via session
- Scaling horizontal memerlukan sticky session atau session di shared store (Redis/database)

---

# ADR-002: OCR sebagai Microservice Python Terpisah

**Tanggal:** 2026-07  
**Status:** Accepted

## Konteks

Dibutuhkan kemampuan OCR untuk memproses kuitansi. PaddleOCR adalah library Python, bukan PHP.

## Keputusan

OCR diimplementasikan sebagai **microservice Python FastAPI** terpisah yang berkomunikasi dengan Laravel via HTTP internal dengan Bearer token.

## Alasan

- PaddleOCR tidak tersedia untuk PHP — integrasi native tidak mungkin
- Memisahkan resource-intensive OCR process dari web server Laravel
- FastAPI sangat ringan dan cocok untuk single-purpose service
- HTTP interface membuat OCR service bisa diganti engine-nya tanpa mengubah Laravel

## Konsekuensi

- Operasional lebih kompleks — dua service harus berjalan bersamaan
- Latency tambahan dari HTTP call antar service
- Perlu maintain dua environment (PHP dan Python)
- OCR dijalankan async via Laravel Queue untuk menghindari timeout HTTP

---

# ADR-003: Session-Based Auth (Bukan Token/JWT)

**Tanggal:** 2026-07  
**Status:** Accepted

## Konteks

Perlu memilih strategi autentikasi untuk SPA yang berkomunikasi dengan Laravel API.

## Keputusan

Menggunakan **Laravel session-based authentication**, bukan JWT atau Sanctum token.

## Alasan

- Aplikasi ini adalah SPA yang di-host di domain yang sama dengan backend — same-origin, tidak perlu CORS
- Session lebih aman dari XSS dibanding JWT di localStorage
- Laravel session sudah built-in, tidak perlu dependency tambahan
- Cocok untuk aplikasi internal yang tidak butuh mobile app atau third-party API consumer

## Konsekuensi

- Tidak cocok jika di masa depan perlu mobile app native atau API publik — perlu tambah Sanctum/Passport
- Horizontal scaling perlu shared session store (database atau Redis)
- Semua API route ada di `web.php`, bukan `api.php`

---

# ADR-004: Queue untuk Pemrosesan OCR

**Tanggal:** 2026-07  
**Status:** Accepted

## Konteks

Pemrosesan OCR via PaddleOCR membutuhkan waktu 30–120 detik per dokumen — terlalu lama untuk HTTP request synchronous.

## Keputusan

Menggunakan **Laravel Queue** dengan driver `database`, queue bernama `ocr`, untuk memproses OCR secara asynchronous via job `ProcessReceiptOcr`.

## Alasan

- Menghindari HTTP timeout pada upload dokumen
- User mendapat response cepat (202 Accepted) dan bisa cek status kemudian
- Database queue driver cukup untuk volume rendah-menengah
- Job menggunakan atomic claim untuk mencegah double-processing

## Konsekuensi

- Butuh queue worker berjalan sebagai daemon (Supervisor di production)
- Untuk volume tinggi (>100 dokumen/hari), pertimbangkan migrasi ke Redis queue
- Monitoring queue backlog perlu diperhatikan agar tidak menumpuk
