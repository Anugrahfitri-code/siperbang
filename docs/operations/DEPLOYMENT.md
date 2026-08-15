# DEPLOYMENT.md — Panduan Deploy SIPERBANG

## Environment

| Environment | Tujuan |
|---|---|
| `local` | Development di mesin lokal |
| `staging` | Testing sebelum production, data dummy |
| `production` | Live, data nyata |

---

## Prasyarat Server (Production)

- PHP 8.4+ dengan ekstensi: dom, curl, libxml, pdo, pdo_mysql, mbstring, fileinfo, zip, gd, opcache
- Composer 2.x
- Node.js 22.x (hanya untuk build, tidak perlu di server production)
- MySQL 8+ atau PostgreSQL 15+
- Nginx atau Apache
- Supervisor (untuk menjalankan queue worker sebagai daemon)

---

## Langkah Deploy (Fresh Install)

### 1. Upload Kode

```bash
git clone <repo-url> /var/www/siperbang
cd /var/www/siperbang
```

### 2. Install Dependencies

```bash
composer install --optimize-autoloader --no-dev
npm ci
npm run typecheck
npm run lint
npm run build
npm run verify:build
rm -f public/hot
```

`public/hot` tidak boleh ada di production. Keberadaannya membuat Laravel mencoba mengambil aset dari Vite development server.

### 3. Konfigurasi Environment

Pertama, buat file environment untuk Laravel dan OCR dari template yang tersedia:

```bash
cp .env.example .env
cp ocr-service/.env.example ocr-service/.env
```

Lakukan pre-flight check sederhana untuk memastikan kedua file environment telah terbuat dengan sukses sebelum OCR dijalankan (misal dengan docker compose up):

```bash
test -f .env && echo "Laravel .env exists"
test -f ocr-service/.env && echo "OCR .env exists"
```

Untuk keamanan OCR, buat **satu rahasia/token acak yang kuat**. Anda dapat men-generate token dengan perintah berikut (contoh pada Linux):

```bash
openssl rand -hex 32
```

> **PENTING:** Simpan hasil dari perintah di atas. Jangan commit hasilnya ke Git.

Edit `.env` (utama Laravel) untuk production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://siperbang.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=siperbang_prod
DB_USERNAME=siperbang_user
DB_PASSWORD=<strong-password>

QUEUE_CONNECTION=database

OCR_SERVICE_URL=http://127.0.0.1:8001
OCR_SERVICE_TOKEN=<GENERATE_RANDOM_SECRET>

SESSION_DRIVER=database
CACHE_STORE=database
```

> **Catatan `OCR_SERVICE_URL`**: `http://127.0.0.1:8001` digunakan karena konfigurasi ini berlaku ketika Laravel berjalan langsung pada host/server dan container OCR mempublikasikan port 8001 pada host yang sama. Port OCR sengaja dibatasi hanya pada localhost (`127.0.0.1:8001`) agar tidak dapat diakses langsung dari jaringan publik/luar, karena hanya Laravel yang perlu mengaksesnya. Konfigurasi localhost ini berlaku untuk arsitektur saat ini: Laravel di host + OCR di Docker pada server yang sama. Jika Laravel nantinya juga dijalankan di dalam Docker, `127.0.0.1` dari container Laravel tidak menunjuk ke container OCR. Pada arsitektur tersebut Docker networking/service name harus digunakan.

Kemudian edit `ocr-service/.env`:

```env
OCR_SERVICE_TOKEN=<GENERATE_RANDOM_SECRET>
```

> **PERINGATAN:** Nilai `OCR_SERVICE_TOKEN` pada file `.env` Laravel dan `ocr-service/.env` HARUS SAMA / IDENTIK. Token ini diperlukan agar Laravel dan FastAPI dapat berkomunikasi dengan aman.

Generate application key untuk Laravel:

```bash
php artisan key:generate
```

### 4. Migrasi Database

```bash
php artisan migrate --force
php artisan storage:link
php artisan db:seed --class="Database\\Seeders\\Inventory\\OfficeActivityInventoryCodeSeeder"
```

Untuk instalasi baru (fresh install), buat akun Superadmin pertama dengan perintah interaktif:

```bash
php artisan app:provision-superadmin
```

### 5. Optimasi Cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 6. Set Permission

```bash
chown -R www-data:www-data /var/www/siperbang/storage
chown -R www-data:www-data /var/www/siperbang/bootstrap/cache
chmod -R 775 /var/www/siperbang/storage
chmod -R 775 /var/www/siperbang/bootstrap/cache
```

### 7. Konfigurasi Nginx

```nginx
server {
    listen 443 ssl;
    server_name siperbang.example.com;
    root /var/www/siperbang/public;

    index index.php;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 8. Setup Queue Worker dengan Supervisor

Buat file `/etc/supervisor/conf.d/siperbang-ocr-worker.conf`:

```ini
[program:siperbang-ocr-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/siperbang/artisan queue:work database --queue=ocr --sleep=3 --tries=1 --timeout=140
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/siperbang/storage/logs/worker.log
stopwaitsecs=120
```

> **Catatan Concurrency:** OCR service saat ini mempunyai satu PaddleOCR inference slot. Queue OCR production sengaja menggunakan `numprocs=1` (satu worker) agar request OCR diserialisasi dari sisi Laravel dan tidak saling bertabrakan dengan inference lock 5 detik. Dengan satu worker, dokumen OCR diproses secara serial dan dokumen tambahan tetap aman menunggu di queue. Ini adalah pengaturan kapasitas default yang disengaja (intentional capacity setting) untuk arsitektur saat ini.

```bash
supervisorctl reread
supervisorctl update
supervisorctl start siperbang-ocr-worker:*
```

### 9. Laravel Scheduler

Branding yang dijadwalkan dipublikasikan oleh command `branding:publish-due`. Tambahkan satu entri cron untuk user aplikasi:

```cron
* * * * * cd /var/www/siperbang && php artisan schedule:run >> /dev/null 2>&1
```

Uji konfigurasi:

```bash
php artisan schedule:list
php artisan branding:publish-due
```

Tanpa cron tersebut, versi terjadwal tetap tersimpan tetapi tidak akan aktif otomatis pada waktunya.

### 10. Deploy OCR Service

> **Catatan Migration:** OCR production pada panduan ini dijalankan menggunakan Docker Compose. Jangan menjalankan instance systemd OCR lama secara bersamaan pada port yang sama. Pastikan tidak ada service OCR lama yang masih menggunakan port 8001 sebelum mengaktifkan container Docker OCR.

#### OCR First-Boot Network Requirement

> **PERHATIAN:** Current OCR deployment is NOT designed as a fully air-gapped installation when starting from a fresh server with no pre-existing dependencies/model cache.

Pastikan server memiliki outbound network yang diperlukan untuk proses Docker build/dependency retrieval dan initial PaddleOCR model retrieval.

Saat `siperbang_ocr_models` masih kosong pada fresh deployment, PaddleOCR belum memiliki model pretrained lokal. Saat OCR engine pertama kali diinisialisasi:
- PaddleOCR memeriksa model/cache lokal
- model belum tersedia
- mengambil model dari configured remote source
- menyimpan cache ke `/root/.paddlex`

Variabel `PADDLE_PDX_MODEL_SOURCE=BOS` menentukan remote model source untuk Paddle/PaddleX. Ini BUKAN offline mode. Model source is remote and requires outbound network when required model files are not yet cached. Tim infrastruktur harus memverifikasi endpoint outbound aktual yang digunakan versi Paddle/PaddleX yang dipasang.

Jalankan container OCR di latar belakang dan build image menggunakan source code terbaru:

```bash
docker compose up -d --build ocr
```

> **Perhatian Volume:** Persistent cache untuk model Paddle/PaddleX OCR disimpan dalam named volume `siperbang_ocr_models` (mounted ke `/root/.paddlex`). JANGAN gunakan `docker compose down -v` untuk normal deployment/redeploy karena `-v` dapat menghapus named volume, termasuk cache model OCR.

#### Verifikasi dan Troubleshooting

Container dapat terlihat running sebelum OCR engine benar-benar ready. Deployment OCR belum boleh dianggap ready hanya karena container berstatus running. Operator harus memastikan health endpoint berhasil.

Lakukan pemeriksaan container setelah start:

```bash
docker compose ps ocr
```

Lakukan verifikasi healthcheck OCR:

```bash
curl -f http://127.0.0.1:8001/health
```

> **Peringatan Healthcheck:** Healthcheck memastikan service/model OCR siap menurut endpoint `/health`. Healthcheck ini belum membuktikan autentikasi token dan proses OCR end-to-end berhasil.

#### OCR Smoke Test

Setelah service OCR berjalan dan `/health` menyatakan ready, jalankan smoke test resmi untuk memverifikasi konektivitas localhost dari host ke OCR Docker container, autentikasi `X-Service-Token`, multipart upload, dan pemrosesan endpoint OCR secara end-to-end.

**Prasyarat HOST untuk Smoke Test:**
- `bash`, `curl`, dan `php` CLI (sudah tercakup oleh requirement host Laravel).
- Script ini dijalankan pada **HOST LINUX**, BUKAN di dalam OCR Docker container.
- Fixture resmi yang digunakan adalah `ocr-service/tests/fixtures/synthetic-smoke-receipt.png` (sepenuhnya sintetis, tidak menggunakan data transaksi nyata perusahaan).

Jalankan perintah berikut di direktori root aplikasi (misalnya `/var/www/siperbang`). Skrip ini akan secara otomatis memuat `OCR_SERVICE_TOKEN` dari file Laravel `.env` menggunakan PHP CLI. Token tidak ditampilkan ke layar, tidak menetap di parent shell, dan berjalan secara aman di dalam scoped subshell.

```bash
cd /var/www/siperbang
(
    export OCR_SERVICE_TOKEN="$(php -r '
        $lines = file(".env", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), "OCR_SERVICE_TOKEN=") === 0) {
                echo trim(explode("=", $line, 2)[1], " \"'\t\n\r\0\x0B");
                exit(0);
            }
        }
        exit(1);
    ')"

    if [ -z "${OCR_SERVICE_TOKEN:-}" ]; then
        echo "ERROR: OCR_SERVICE_TOKEN is not configured in Laravel .env." >&2
        exit 1
    fi

    bash ./scripts/ocr-smoke-test.sh
)
```

**Expected Output (PASS):**
```text
Checking OCR health...
PASS: OCR health endpoint is ready.

Running authenticated OCR smoke test...
PASS: OCR authenticated smoke test succeeded.
```

**Panduan Kegagalan (Troubleshooting):**
- **401**: periksa kesamaan `OCR_SERVICE_TOKEN` Laravel dan OCR service.
- **422**: synthetic fixture mencapai service tetapi input/result ditolak.
- **500**: periksa OCR container logs.
- **503**: periksa docker compose ps/logs dan readiness/model initialization.
- **transport**: periksa container running dan localhost binding.

Jika `/health` atau smoke test gagal, JANGAN langsung menjalankan perintah destruktif seperti `docker volume rm siperbang_ocr_models` atau `docker compose down -v`. Lakukan observasi log terlebih dahulu:

```bash
docker compose logs --tail=100 ocr
```

Beberapa kemungkinan gagal:
- model masih dalam proses initialization;
- remote model download gagal;
- outbound network/DNS bermasalah;
- OCR engine gagal load.

#### Migrasi Server

Git repository tidak menyimpan Docker named volume. Melakukan `git clone` pada server baru tidak membawa cache `siperbang_ocr_models` dari server lama. Pada server baru, model perlu diperoleh kembali melalui normal first-boot download atau prosedur perpindahan cache manual.

---

## Update / Redeploy

```bash
cd /var/www/siperbang

# 1. Aktifkan maintenance mode
php artisan down

# 2. Pull kode terbaru
git pull origin main

# 3. Install dependencies baru (jika ada)
composer install --optimize-autoloader --no-dev
npm ci
npm run typecheck
npm run lint
npm run build
npm run verify:build

# 4. Jalankan migrasi dan pastikan storage link tersedia
php artisan migrate --force
php artisan storage:link

# 5. Clear & rebuild cache
php artisan optimize:clear
php artisan optimize

# 6. Rebuild OCR Image & Recreate Container
# Catatan Cache: model sudah berada di named volume -> image OCR direbuild ->
# container direcreate -> named volume tetap dipasang -> cached model dapat digunakan kembali.
# Model tidak seharusnya perlu diunduh ulang selama cache yang diperlukan
# masih tersedia dan kompatibel pada named volume.
docker compose up -d --build ocr

# 7. OCR Verification
docker compose ps ocr
curl -f http://127.0.0.1:8001/health

# 8. Authenticated OCR Smoke Test
# Wajib dipastikan sukses sebelum merestart queue worker OCR
(
    export OCR_SERVICE_TOKEN="$(php -r '
        $lines = file(".env", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), "OCR_SERVICE_TOKEN=") === 0) {
                echo trim(explode("=", $line, 2)[1], " \"'\t\n\r\0\x0B");
                exit(0);
            }
        }
        exit(1);
    ')"

    if [ -z "${OCR_SERVICE_TOKEN:-}" ]; then
        echo "ERROR: OCR_SERVICE_TOKEN is not configured in Laravel .env." >&2
        exit 1
    fi

    bash ./scripts/ocr-smoke-test.sh
)

# 9. Restart queue worker Laravel
supervisorctl restart siperbang-ocr-worker:*

# 10. Validasi route, scheduler, dan health endpoint Laravel
php artisan route:list --path=settings
php artisan schedule:list
php artisan branding:publish-due
curl -f http://127.0.0.1/up

# 11. Nonaktifkan maintenance mode
php artisan up
```

> **Catatan Environment OCR:** Jika Anda mengubah `ocr-service/.env`, container OCR harus direcreate agar konfigurasi environment baru digunakan. Command `docker compose up -d --build ocr` pada urutan redeploy di atas sudah memfasilitasi hal ini.

---

## Rollback Plan

### Rollback Kode

```bash
# Jika menggunakan git tag
git checkout v1.2.3

# Atau rollback ke commit sebelumnya
git checkout <previous-commit-hash>

composer install --optimize-autoloader --no-dev
php artisan optimize:clear
php artisan optimize
supervisorctl restart siperbang-ocr-worker:*
```

### Rollback Migrasi

```bash
# Rollback satu migrasi terakhir
php artisan migrate:rollback

# Rollback N migrasi terakhir
php artisan migrate:rollback --step=3
```

> **PERINGATAN:** Rollback migrasi yang melibatkan perubahan schema atau drop kolom bisa menyebabkan kehilangan data. Selalu backup database sebelum deploy ke production.

---

## Backup Strategy

### Database

```bash
# MySQL backup harian (gunakan konfigurasi credential file ~/.my.cnf, jangan pass -p langsung)
mysqldump --defaults-extra-file=~/.my.cnf siperbang_prod > backup_$(date +%Y%m%d).sql

# Simpan ke storage jangka panjang (S3, GCS, dll.)
```

### File Storage

```bash
# Backup folder uploads dan OCR documents
tar -czf storage_backup_$(date +%Y%m%d).tar.gz /var/www/siperbang/storage/app/private
```

**Jadwal yang direkomendasikan:**
- Database: backup harian, simpan 30 hari
- Storage files: backup mingguan, simpan 3 bulan

---

## Restore Strategy & Rehearsal

Backup keberadaannya belum cukup jika tidak diuji. Diperlukan simulasi restore secara berkala.

### Prasyarat Restore Test
- Jangan mengeksekusi restore destruktif pada database production atau development aktif.
- Lakukan pada disposable STAGING/TEST database server.

### Langkah Restore MySQL (Contoh)
```bash
# 1. Pastikan database target (siperbang_staging) kosong atau dapat ditimpa
mysql --defaults-extra-file=~/.my.cnf -e "DROP DATABASE IF EXISTS siperbang_staging; CREATE DATABASE siperbang_staging;"

# 2. Lakukan import dari file SQL
mysql --defaults-extra-file=~/.my.cnf siperbang_staging < backup_YYYYMMDD.sql
```

### Langkah Restore File
```bash
# Ekstrak backup tarball ke direktori staging storage
tar -xzf storage_backup_YYYYMMDD.tar.gz -C /var/www/staging_siperbang/storage/app/private --strip-components=5
```

### Smoke Test Pasc-Restore
- Jalankan login.
- Lakukan upload dokumen receipt ke OCR (memastikan permission folder private valid).
- Uji download file excel atau PDF export.

---

## Health Check

Cek status aplikasi:

```bash
# Cek Laravel
curl -f http://localhost/api/user  # harus return 401 (bukan 500)

# Cek OCR service
curl -f http://127.0.0.1:8001/health

# Cek queue worker dan scheduler
php artisan queue:monitor
php artisan schedule:list
php artisan branding:publish-due

# Cek symlink dan endpoint identitas
readlink -f public/storage
curl -f http://localhost/api/settings

# Cek supervisor
supervisorctl status
```
