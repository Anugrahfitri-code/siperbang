# SIPERBANG

**SIPERBANG** adalah aplikasi pengelolaan persediaan barang yang mendukung proses administrasi persediaan, pengajuan kebutuhan barang, distribusi, pengelolaan BON/kuitansi, OCR dokumen, sinkronisasi stok, pelaporan operasional, dan manajemen pengguna berbasis role.

Aplikasi dibangun menggunakan **Laravel**, **React + TypeScript**, **PostgreSQL**, dan layanan OCR terpisah berbasis **FastAPI / PaddleOCR**.

Repository ini dipersiapkan untuk **company deployment handoff**. Source code, mekanisme deployment, automated testing, dan dependency-security checks telah disiapkan di repository. Konfigurasi production seperti credential, secret, TLS, jaringan, backup, dan infrastruktur tetap menjadi tanggung jawab environment perusahaan pada saat deployment.

---

## Daftar Isi

- [Fitur Utama](#fitur-utama)
- [Teknologi](#teknologi)
- [Arsitektur](#arsitektur)
- [Role dan Manajemen Akun](#role-dan-manajemen-akun)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Environment Configuration](#environment-configuration)
- [Secret Management](#secret-management)
- [Deployment Preflight](#deployment-preflight)
- [Fresh Deployment](#fresh-deployment)
- [Migration dan Seeder](#migration-dan-seeder)
- [Laravel Production Optimization](#laravel-production-optimization)
- [Queue Worker dan Scheduler](#queue-worker-dan-scheduler)
- [OCR Service](#ocr-service)
- [Health Check](#health-check)
- [Security](#security)
- [Automated Testing](#automated-testing)
- [PostgreSQL Deployment Acceptance](#postgresql-deployment-acceptance)
- [Dependency Security](#dependency-security)
- [Production Checklist](#production-checklist)
- [Backup dan Restore](#backup-dan-restore)
- [Dokumentasi](#dokumentasi)
- [Status Repository](#status-repository)
- [Batas Tanggung Jawab Handoff](#batas-tanggung-jawab-handoff)
- [Handoff](#handoff)

---

## Fitur Utama

SIPERBANG menyediakan fungsi utama berikut:

- pengelolaan data persediaan barang;
- pencatatan dan sinkronisasi stok;
- pengajuan kebutuhan barang;
- proses distribusi barang;
- pengelolaan BON dan kuitansi;
- OCR dokumen kuitansi melalui layanan OCR terpisah;
- impor data persediaan melalui Excel;
- pengelolaan laporan operasional;
- manajemen pengguna berbasis role;
- pembatasan akses sesuai kewenangan pengguna;
- pengelolaan status pengguna Aktif / Nonaktif;
- proteksi integritas stok dengan transaksi database dan locking pada alur yang membutuhkan konsistensi;
- automated testing untuk backend, frontend, OCR, dan PostgreSQL;
- dependency-security audit;
- deployment preflight sebelum migration.

---

## Teknologi

### Backend

- PHP 8.4+
- Laravel
- Composer 2
- PostgreSQL 17.11
- Laravel Session-based Authentication
- Database-backed session / cache / queue sesuai konfigurasi production

### Frontend

- React
- TypeScript
- Vite
- Node.js:

```text
^20.19.0 || >=22.12.0
```

Node.js diperlukan pada host yang melakukan proses frontend build. Jika static asset dibangun pada CI/build host terpercaya dan hasil build dipindahkan ke application host, Node.js tidak harus menjadi bagian dari runtime aplikasi setelah deployment.

### OCR Service

- Python
- FastAPI
- PaddleOCR / PaddlePaddle
- Docker
- Runtime non-root (`ocruser`)
- Current accepted architecture: **x86_64 / AMD64**

ARM64 belum termasuk baseline OCR yang telah diterima dan harus divalidasi secara terpisah apabila akan digunakan.

---

## Arsitektur

Gambaran sederhana arsitektur SIPERBANG:

```text
Browser
   |
   v
Laravel + React
   |
   +------ PostgreSQL
   |
   +------ Queue Worker
   |
   +------ OCR Service
               |
               +------ PaddleOCR / PaddlePaddle
```

Laravel merupakan aplikasi utama.

PostgreSQL digunakan sebagai database utama.

OCR berjalan sebagai service terpisah dan diakses aplikasi melalui internal service URL serta `OCR_SERVICE_TOKEN`.

OCR service tidak perlu diekspos langsung ke internet.

---

## Role dan Manajemen Akun

SIPERBANG menggunakan tiga role operasional utama:

| Role | Fungsi Utama |
|---|---|
| **Superadmin** | Administrasi sistem dan pengelolaan akun pengguna |
| **Petugas Persediaan** | Pengelolaan persediaan, stok, pengadaan, distribusi, kuitansi/OCR, dan operasi barang |
| **Ketua Tim** | Pengajuan dan pengelolaan data sesuai lingkup unit/seksi yang dimiliki |

Hak akses dikendalikan di sisi backend. Frontend bukan sumber kebenaran untuk otorisasi.

### Superadmin

Superadmin digunakan untuk administrasi sistem, termasuk manajemen akun pengguna.

Pada fresh deployment, repository **tidak menyediakan username atau password Superadmin bawaan**.

Setelah aplikasi berhasil di-deploy dan database telah dimigrasikan, administrator perusahaan membuat Superadmin pertama menggunakan command:

```bash
php artisan app:provision-superadmin
```

Credential dibuat langsung oleh administrator perusahaan dan tidak boleh disimpan di repository.

Setelah Superadmin pertama tersedia, pengelolaan akun lain dilakukan melalui fitur manajemen pengguna sesuai kebijakan perusahaan dan otorisasi backend.

### Petugas Persediaan

Petugas Persediaan berfokus pada kegiatan operasional persediaan, seperti:

- pengelolaan stok;
- pengadaan/penerimaan barang;
- distribusi;
- kuitansi dan dokumen OCR;
- proses operasional barang lainnya sesuai izin aplikasi.

Petugas Persediaan tidak ditujukan untuk fungsi administrasi akun.

### Ketua Tim

Ketua Tim menggunakan SIPERBANG untuk proses pengajuan dan pengelolaan data dalam lingkup unit/seksi yang menjadi kewenangannya.

Scope data Ketua Tim harus ditentukan dengan benar pada akun agar pembatasan akses dapat diterapkan sesuai kebutuhan organisasi.

Ketua Tim tidak ditujukan untuk fungsi administrasi akun.

### Status Pengguna

Status pengguna yang digunakan aplikasi:

```text
Aktif
Nonaktif
```

Akun dengan status `Nonaktif` tidak boleh memperoleh akses normal ke aplikasi.

---

## Persyaratan Sistem

### Application Host

Baseline:

```text
PHP 8.4+
Composer 2
PostgreSQL 17.11
```

PHP extension yang diperlukan antara lain:

```text
dom
curl
libxml
pdo
pdo_pgsql
mbstring
fileinfo
zip
gd
opcache
```

Pastikan `pdo_pgsql` tersedia karena database production menggunakan PostgreSQL.

### Frontend Build Host

Node.js:

```text
^20.19.0 || >=22.12.0
```

Install dependency menggunakan lockfile:

```bash
npm ci
```

Pada CI/CD deployment, gunakan `npm ci` agar dependency graph mengikuti `package-lock.json`.

### OCR Host

Current accepted OCR baseline:

```text
Architecture: x86_64 / AMD64
Docker: required
```

OCR container berjalan sebagai:

```text
ocruser
```

Persistent Paddle/PaddleX cache:

```text
/home/ocruser/.paddlex
```

Fresh OCR deployment dapat membutuhkan outbound network jika model OCR belum tersedia di persistent cache.

---

## Environment Configuration

Salin template environment:

```bash
cp .env.example .env
```

Kemudian isi konfigurasi sesuai environment perusahaan.

Production harus menggunakan:

```env
APP_ENV=production
APP_DEBUG=false
```

Production `APP_URL` harus menggunakan HTTPS, misalnya:

```env
APP_URL=https://siperbang.example.internal
```

Konfigurasi database:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

Konfigurasi OCR:

```env
OCR_SERVICE_URL=http://127.0.0.1:8001
OCR_SERVICE_TOKEN=...
```

Generate `APP_KEY` pada environment deployment:

```bash
php artisan key:generate
```

> Jangan menggunakan nilai contoh pada dokumentasi sebagai credential production.

---

## Secret Management

Secret berikut **tidak boleh disimpan di repository**:

- `APP_KEY`;
- database password;
- `OCR_SERVICE_TOKEN`;
- API key;
- private key;
- credential production lainnya.

Production `.env` juga tidak boleh di-commit.

Variabel frontend `VITE_*` ikut dibundle ke browser sehingga **tidak boleh** dipakai untuk menyimpan secret.

Secret sebaiknya disimpan menggunakan secret-management mechanism yang disediakan perusahaan atau deployment environment.

---

## Deployment Preflight

Sebelum migration, jalankan repository-owned deployment preflight:

```bash
bash scripts/deployment/preflight.sh
```

Jika host juga menjalankan OCR:

```bash
bash scripts/deployment/preflight.sh --with-ocr
```

Preflight memeriksa antara lain:

- ketersediaan PHP;
- versi PHP;
- PHP extension yang diperlukan;
- `pdo_pgsql`;
- Composer;
- Composer platform requirements;
- Node/npm sesuai requirement build;
- keberadaan konfigurasi production;
- `APP_ENV=production`;
- `APP_DEBUG=false`;
- konfigurasi PostgreSQL;
- keberadaan `APP_KEY`;
- konfigurasi OCR;
- keberadaan OCR token;
- Docker pada mode OCR;
- architecture OCR yang didukung;
- sinkronisasi OCR token bila diperlukan.

Preflight **tidak menjalankan migration** dan tidak melakukan perubahan database.

Jika preflight gagal, perbaiki environment terlebih dahulu sebelum melanjutkan deployment.

---

## Fresh Deployment

Panduan lengkap terdapat pada:

```text
docs/operations/DEPLOYMENT.md
```

Urutan umum fresh deployment:

1. clone exact release / commit;
2. install PHP dependencies;
3. install frontend dependencies;
4. build frontend;
5. konfigurasi `.env`;
6. konfigurasi OCR environment;
7. jalankan deployment preflight;
8. verifikasi target PostgreSQL;
9. jalankan migration normal;
10. jalankan deployment seeder;
11. build/cache Laravel;
12. konfigurasi web server;
13. konfigurasi queue worker;
14. konfigurasi scheduler;
15. jalankan OCR service;
16. lakukan health check;
17. provision Superadmin pertama;
18. lakukan security dan functional smoke test.

### Install Dependency PHP

```bash
composer install   --optimize-autoloader   --no-dev   --no-interaction   --prefer-dist
```

Verifikasi platform:

```bash
composer check-platform-reqs --no-dev
```

### Install dan Build Frontend

```bash
npm ci
npm run typecheck
npm run lint
npm run build
npm run verify:build
```

Development Vite server tidak digunakan sebagai web server production.

---

## Migration dan Seeder

### Migration

Setelah preflight dan PostgreSQL target diverifikasi:

```bash
php artisan migrate --force
```

Jangan menggunakan command destruktif berikut pada database production:

```text
migrate:fresh
db:wipe
```

Migration dilakukan menggunakan mekanisme normal Laravel.

### Deployment Seeder

Jalankan seeder deployment yang diperlukan:

```bash
php artisan db:seed   --class="Database\Seeders\Inventory\OfficeActivityInventoryCodeSeeder"   --force
```

Repository tidak mengandalkan default Superadmin credential.

### Storage Link

Jika diperlukan:

```bash
php artisan storage:link
```

Dokumen operasional sensitif tetap harus berada pada private storage dan tidak boleh dipindahkan ke public webroot hanya untuk mempermudah akses.

---

## Laravel Production Optimization

Production deployment dapat menjalankan:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

Setelah cache dibuat, verifikasi aplikasi tetap dapat boot:

```bash
php artisan about
php artisan route:list
```

---

## Queue Worker dan Scheduler

### Queue Worker

SIPERBANG menggunakan queue untuk proses tertentu termasuk OCR.

Contoh queue OCR:

```text
connection: database
queue: ocr
```

Monitor queue depth:

```bash
php artisan queue:monitor database:ocr --max=100
```

`queue:monitor` tidak membuktikan bahwa worker process sedang hidup.

Status worker harus diverifikasi menggunakan process manager perusahaan, misalnya Supervisor:

```bash
supervisorctl status
```

### Scheduler

Laravel scheduler harus dijalankan menggunakan mekanisme operating system atau process manager yang sesuai.

Contoh cron:

```cron
* * * * * cd /path/to/siperbang && php artisan schedule:run >> /dev/null 2>&1
```

Sesuaikan path dengan lokasi deployment perusahaan.

---

## OCR Service

OCR service disediakan sebagai container terpisah.

OCR tidak boleh menggunakan token bawaan repository.

Gunakan token khusus untuk environment perusahaan.

Current deployment pattern dapat menggunakan:

```text
http://127.0.0.1:8001
```

atau internal container/network endpoint yang setara.

OCR service sebaiknya hanya dapat diakses melalui internal network atau loopback dan tidak diekspos langsung ke internet.

Persistent Paddle/PaddleX cache:

```text
/home/ocruser/.paddlex
```

Fresh deployment dapat membutuhkan koneksi outbound untuk mengambil model jika cache masih kosong.

Health endpoint saja belum membuktikan actual OCR inference. Setelah deployment, lakukan authenticated synthetic OCR inference untuk memastikan PaddleOCR, model cache, CPU environment, dan integrasi aplikasi/OCR benar-benar bekerja pada host perusahaan.

---

## Health Check

### Laravel

Laravel menyediakan health endpoint:

```text
GET /up
```

Expected:

```text
HTTP 200
```

Contoh:

```bash
curl -fsS http://127.0.0.1/up >/dev/null
```

### Authentication Boundary

Endpoint identity:

```text
GET /api/user
```

Tanpa session authentication, expected:

```text
HTTP 401
```

Contoh:

```bash
status="$(curl -sS -o /dev/null -w '%{http_code}' http://127.0.0.1/api/user)"
test "$status" = "401"
```

HTTP `401` pada pemeriksaan ini adalah expected behavior, bukan service failure.

### OCR

OCR health endpoint:

```text
GET http://127.0.0.1:8001/health
```

Service harus merespons sukses sebelum digunakan oleh aplikasi.

---

## Security

SIPERBANG menerapkan prinsip secure coding yang mencakup:

- tidak menyimpan credential di source;
- server-side input validation;
- whitelist input;
- Eloquent / parameter binding untuk query;
- proteksi SQL Injection;
- proteksi XSS;
- secure file upload;
- private document storage;
- Laravel Session-based Authentication;
- password hashing melalui Laravel Hash;
- login rate limiting;
- Role-Based Access Control;
- inactive-user enforcement;
- server-authoritative requester identity;
- bounded/generic error response;
- dependency-security audit;
- container vulnerability scanning;
- non-root OCR runtime;
- deployment preflight;
- PostgreSQL deployment acceptance CI.

Dokumentasi keamanan:

```text
docs/operations/SECURITY.md
```

### Upload Security

Upload dokumen/file harus menerapkan kontrol seperti:

- whitelist MIME/type;
- batas ukuran file;
- content-based validation;
- server-derived extension;
- random/hashed/UUID filename;
- sensitive file disimpan di private storage.

File scriptable/executable berikut tidak diterima sebagai dokumen upload biasa:

```text
.php
.phar
.phtml
.php3
.html
.svg
.xml
.sh
.py
.js
.exe
```

### SQL dan Integritas Data

Query menggunakan Laravel Eloquent / Query Builder atau parameter binding.

User-controlled values tidak boleh digunakan langsung sebagai SQL identifier atau raw SQL fragment.

Proses stok yang membutuhkan konsistensi menggunakan database transaction dan locking sesuai kebutuhan untuk mencegah race condition dan menjaga integritas ledger/stok.

---

## Automated Testing

### Backend

```bash
vendor/bin/phpunit   --configuration=phpunit.xml   --display-all-issues
```

### Code Style

```bash
vendor/bin/pint --test
```

### Frontend

```bash
npm run typecheck
npm run lint
npm run build
npm run verify:build
```

OCR memiliki test suite sendiri yang dijalankan melalui workflow repository.

---

## PostgreSQL Deployment Acceptance

Repository menyediakan GitHub Actions workflow khusus untuk membuktikan deployment pada:

```text
PostgreSQL 17.11
```

Acceptance meliputi:

- fresh PostgreSQL database;
- deployment preflight;
- normal migration;
- migration completeness;
- deployment seeder;
- frontend production build;
- Laravel production cache;
- second migration / idempotency verification;
- PostgreSQL connectivity;
- full Laravel regression suite terhadap PostgreSQL nyata.

Dengan demikian kompatibilitas PostgreSQL tidak hanya diasumsikan dari SQLite.

---

## Dependency Security

Repository menjalankan automated dependency-security checks untuk beberapa ecosystem.

### PHP

```bash
composer audit
```

### Node.js

```bash
npm audit
```

Repository dapat menerapkan policy checker terhadap accepted-risk advisory yang telah direview.

### Python

```bash
pip-audit
```

### Container

OCR image diperiksa menggunakan container vulnerability scanner / Trivy sesuai policy repository.

Temporary dependency/security exception harus direview sebelum masa berlakunya berakhir dan tidak boleh diperpanjang otomatis tanpa penilaian ulang.

---

## CI/CD Verification

Automated verification mencakup:

- backend tests;
- frontend typecheck;
- frontend lint;
- production frontend build;
- OCR tests;
- Composer audit;
- npm dependency audit/policy;
- Python dependency audit;
- container vulnerability scan;
- PostgreSQL 17.11 fresh-deployment acceptance;
- PostgreSQL backend regression.

Keberhasilan CI merupakan **repository-level verification**.

CI tidak menggantikan acceptance terhadap production infrastructure milik perusahaan.

---

## Production Checklist

Sebelum go-live, perusahaan harus memastikan:

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] HTTPS/TLS aktif
- [ ] production `.env` tidak masuk repository
- [ ] `APP_KEY` dibuat khusus production
- [ ] database credential dibuat khusus production
- [ ] `OCR_SERVICE_TOKEN` dibuat khusus production
- [ ] PostgreSQL access dibatasi sesuai network policy
- [ ] OCR tidak diekspos langsung ke internet
- [ ] firewall/network rules diterapkan
- [ ] OS/container runtime telah dipatch
- [ ] backup tersedia
- [ ] restore procedure diuji
- [ ] queue worker aktif
- [ ] scheduler aktif
- [ ] application health check PASS
- [ ] authentication smoke test PASS
- [ ] RBAC smoke test PASS
- [ ] upload smoke test PASS
- [ ] PostgreSQL smoke test PASS
- [ ] authenticated OCR inference PASS

---

## Backup dan Restore

Backup dan restore merupakan bagian dari operational responsibility perusahaan.

Minimal harus tersedia:

- PostgreSQL backup;
- backup file/dokumen operasional yang diperlukan;
- recovery procedure;
- penanggung jawab backup;
- restore verification.

Backup belum dianggap cukup hanya karena file backup berhasil dibuat.

Restore harus dapat diuji pada environment yang aman.

---

## Repository Security Rules

Jangan commit:

```text
.env
.env.production
credential
APP_KEY
database password
OCR_SERVICE_TOKEN
private key
database dump berisi data nyata
temporary production configuration
```

Sebelum commit:

```bash
git status
git diff
```

Sebelum deployment:

```bash
bash scripts/deployment/preflight.sh --with-ocr
```

---

## Dokumentasi

Dokumentasi utama:

```text
README.md
docs/operations/DEPLOYMENT.md
docs/operations/SECURITY.md
docs/development/SETUP_DEV.md
```

Gunakan:

- `README.md` untuk gambaran umum;
- `docs/operations/DEPLOYMENT.md` sebagai panduan deployment;
- `docs/operations/SECURITY.md` untuk kebijakan dan baseline keamanan;
- `docs/development/SETUP_DEV.md` untuk development setup.

---

## Status Repository

Repository SIPERBANG telah dipersiapkan untuk repository-side verification terhadap:

- source security;
- authentication dan authorization;
- upload security;
- SQL safety;
- XSS safety;
- error handling;
- dependency security;
- stock concurrency/integrity;
- frontend build;
- backend regression;
- PostgreSQL 17.11 fresh deployment;
- PostgreSQL backend regression;
- OCR container security;
- deployment prerequisite validation.

Status handoff repository:

```text
READY FOR COMPANY DEPLOYMENT HANDOFF
```

Status tersebut berarti repository telah dipersiapkan untuk diserahkan kepada perusahaan untuk proses deployment.

Status tersebut **tidak berarti production go-live otomatis disetujui**.

---

## Batas Tanggung Jawab Handoff

Hal berikut tetap menjadi tanggung jawab perusahaan saat deployment:

- production server;
- production PostgreSQL;
- production credential;
- `APP_KEY`;
- `OCR_SERVICE_TOKEN`;
- secret management;
- TLS/HTTPS;
- reverse proxy;
- network/firewall;
- OS patching;
- Docker/container runtime;
- worker/process manager;
- scheduler;
- backup;
- restore drill;
- monitoring;
- logging infrastructure;
- authenticated OCR inference pada production host;
- production smoke test;
- final go-live approval.

---

## Handoff

Repository resmi:

```text
https://github.com/Anugrahfitri-code/siperbang
```

Branch deployment:

```text
main
```

Perusahaan disarankan mencatat exact commit/release yang digunakan pada setiap deployment agar versi production dapat direproduksi dan diaudit.

Sebelum migration:

```bash
bash scripts/deployment/preflight.sh --with-ocr
```

Kemudian ikuti panduan:

```text
docs/operations/DEPLOYMENT.md
```

---

## Catatan untuk Maintainer

Setiap perubahan setelah handoff harus tetap mengikuti secure-coding standard repository dan melewati CI yang relevan.

Jangan mengubah atau memperpanjang dependency-security exception secara otomatis tanpa review ulang.

Setiap perubahan pada authentication, role, upload, stock transaction, OCR integration, deployment workflow, atau dependency harus diperlakukan sebagai perubahan yang memerlukan regression/security review.

---

**SIPERBANG — Sistem Informasi Pengelolaan Persediaan Barang**
