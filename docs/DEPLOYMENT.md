# DEPLOYMENT.md — Panduan Deploy SIPERBANG

## Environment

| Environment | Tujuan |
|---|---|
| `local` | Development di mesin lokal |
| `staging` | Testing sebelum production, data dummy |
| `production` | Live, data nyata |

---

## Prasyarat Server (Production)

- PHP 8.4+ dengan ekstensi: pdo, pdo_mysql, mbstring, fileinfo, zip, gd, curl, opcache
- Composer 2.x
- Node.js 20.x (hanya untuk build, tidak perlu di server production)
- MySQL 8+ atau PostgreSQL 15+
- Python 3.10+ (untuk OCR service)
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
npm install
npm run build
```

### 3. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` untuk production:

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
OCR_SERVICE_TOKEN=<strong-random-token>

SESSION_DRIVER=database
CACHE_STORE=database
```

### 4. Migrasi Database

```bash
php artisan migrate --force
php artisan db:seed --class=KategoriDanKodePersediaanSeeder
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
command=php /var/www/siperbang/artisan queue:work database --queue=ocr --sleep=3 --tries=1 --timeout=120
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/siperbang/storage/logs/worker.log
stopwaitsecs=120
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start siperbang-ocr-worker:*
```

### 9. Deploy OCR Service

```bash
cd /var/www/siperbang/ocr-service
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
```

Buat systemd service `/etc/systemd/system/siperbang-ocr.service`:

```ini
[Unit]
Description=SIPERBANG OCR Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/siperbang/ocr-service
Environment="PATH=/var/www/siperbang/ocr-service/.venv/bin"
ExecStart=/var/www/siperbang/ocr-service/.venv/bin/uvicorn app.main:app --host 127.0.0.1 --port 8001
Restart=on-failure

[Install]
WantedBy=multi-user.target
```

```bash
systemctl enable siperbang-ocr
systemctl start siperbang-ocr
```

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
npm install
npm run build

# 4. Jalankan migrasi baru (jika ada)
php artisan migrate --force

# 5. Clear & rebuild cache
php artisan optimize:clear
php artisan optimize

# 6. Restart queue worker
supervisorctl restart siperbang-ocr-worker:*

# 7. Nonaktifkan maintenance mode
php artisan up
```

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
# MySQL backup harian
mysqldump -u siperbang_user -p siperbang_prod > backup_$(date +%Y%m%d).sql

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

## Health Check

Cek status aplikasi:

```bash
# Cek Laravel
curl -f http://localhost/api/user  # harus return 401 (bukan 500)

# Cek OCR service
curl -f http://127.0.0.1:8001/health

# Cek queue worker
php artisan queue:monitor

# Cek supervisor
supervisorctl status
```
