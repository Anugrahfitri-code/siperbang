#!/bin/bash

# ─── Warna ──────────────────────────────────────
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo ""
echo "  ============================================"
echo "   SIPERBANG - Setup Otomatis"
echo "  ============================================"
echo ""

# ─── Cek PHP ────────────────────────────────────
if ! command -v php &> /dev/null; then
    echo -e "${RED}[ERROR]${NC} PHP tidak ditemukan. Install PHP 8.3+ terlebih dahulu."
    exit 1
fi

# ─── Cek Composer ───────────────────────────────
if ! command -v composer &> /dev/null; then
    echo -e "${RED}[ERROR]${NC} Composer tidak ditemukan. Download di https://getcomposer.org"
    exit 1
fi

# ─── Cek psql ───────────────────────────────────
if ! command -v psql &> /dev/null; then
    echo -e "${RED}[ERROR]${NC} PostgreSQL tidak ditemukan. Install dengan:"
    echo "  Ubuntu/Debian : sudo apt install postgresql"
    echo "  macOS         : brew install postgresql"
    exit 1
fi

echo -e "${GREEN}[OK]${NC} PHP, Composer, dan PostgreSQL ditemukan."
echo ""

# ─── Tanya input database ───────────────────────
echo -e "${CYAN}Masukkan konfigurasi database PostgreSQL Anda:${NC}"
echo "(Kosongkan dan tekan Enter untuk pakai nilai default)"
echo ""

read -p "Host database [127.0.0.1]: " DB_HOST
DB_HOST=${DB_HOST:-127.0.0.1}

read -p "Port database [5432]: " DB_PORT
DB_PORT=${DB_PORT:-5432}

read -p "Nama database [siperbang]: " DB_DATABASE
DB_DATABASE=${DB_DATABASE:-siperbang}

read -p "Username PostgreSQL [postgres]: " DB_USERNAME
DB_USERNAME=${DB_USERNAME:-postgres}

read -s -p "Password PostgreSQL: " DB_PASSWORD
echo ""

# ─── Buat database ──────────────────────────────
echo ""
echo -e "${CYAN}[INFO]${NC} Membuat database '${DB_DATABASE}' jika belum ada..."
DB_EXISTS=$(PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -tAc "SELECT 1 FROM pg_database WHERE datname='$DB_DATABASE'" 2>/dev/null)
if [ "$DB_EXISTS" != "1" ]; then
    PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -c "CREATE DATABASE $DB_DATABASE;" > /dev/null 2>&1
    if [ $? -ne 0 ]; then
        echo -e "${RED}[ERROR]${NC} Gagal membuat database. Cek username/password PostgreSQL Anda."
        exit 1
    fi
    echo -e "${GREEN}[OK]${NC} Database '${DB_DATABASE}' berhasil dibuat."
else
    echo -e "${GREEN}[OK]${NC} Database '${DB_DATABASE}' sudah ada, dilewati."
fi

# ─── Salin .env ─────────────────────────────────
echo ""
echo -e "${CYAN}[INFO]${NC} Menyiapkan file .env..."
if [ ! -f ".env" ]; then
    cp .env.example .env
    echo -e "${GREEN}[OK]${NC} File .env dibuat dari .env.example."
else
    echo -e "${GREEN}[OK]${NC} File .env sudah ada, dilewati."
fi

# ─── Tulis konfigurasi DB ke .env ───────────────
echo -e "${CYAN}[INFO]${NC} Menulis konfigurasi database ke .env..."
php -r "
\$env = file_get_contents('.env');
\$env = preg_replace('/^DB_CONNECTION=.*/m', 'DB_CONNECTION=pgsql', \$env);
\$env = preg_replace('/^#?\s*DB_HOST=.*/m', 'DB_HOST=${DB_HOST}', \$env);
\$env = preg_replace('/^#?\s*DB_PORT=.*/m', 'DB_PORT=${DB_PORT}', \$env);
\$env = preg_replace('/^#?\s*DB_DATABASE=.*/m', 'DB_DATABASE=${DB_DATABASE}', \$env);
\$env = preg_replace('/^#?\s*DB_USERNAME=.*/m', 'DB_USERNAME=${DB_USERNAME}', \$env);
\$env = preg_replace('/^#?\s*DB_PASSWORD=.*/m', 'DB_PASSWORD=\"${DB_PASSWORD}\"', \$env);
file_put_contents('.env', \$env);
"
echo -e "${GREEN}[OK]${NC} Konfigurasi database berhasil ditulis ke .env."

# ─── Composer install ───────────────────────────
echo ""
echo -e "${CYAN}[INFO]${NC} Menginstall dependensi PHP (composer install)..."
composer install --no-interaction --prefer-dist --optimize-autoloader
if [ $? -ne 0 ]; then
    echo -e "${RED}[ERROR]${NC} composer install gagal."
    exit 1
fi
echo -e "${GREEN}[OK]${NC} Dependensi PHP berhasil diinstall."

# ─── Generate APP_KEY ───────────────────────────
echo ""
echo -e "${CYAN}[INFO]${NC} Generate APP_KEY..."
php artisan key:generate --force
if [ $? -ne 0 ]; then
    echo -e "${RED}[ERROR]${NC} Gagal generate APP_KEY."
    exit 1
fi

# ─── Migrasi database ───────────────────────────
echo ""
echo -e "${CYAN}[INFO]${NC} Menjalankan migrasi database..."
php artisan migrate --force
if [ $? -ne 0 ]; then
    echo -e "${RED}[ERROR]${NC} Migrasi gagal. Cek koneksi database Anda."
    exit 1
fi
echo -e "${GREEN}[OK]${NC} Migrasi berhasil."

# ─── Seeder ─────────────────────────────────────
echo ""
read -p "Jalankan database seeder? (data awal/contoh) [y/N]: " RUN_SEED
if [[ "$RUN_SEED" =~ ^[Yy]$ ]]; then
    php artisan db:seed --force
    if [ $? -ne 0 ]; then
        echo -e "${YELLOW}[PERINGATAN]${NC} Seeder gagal, tapi setup tetap dilanjutkan."
    else
        echo -e "${GREEN}[OK]${NC} Seeder berhasil dijalankan."
    fi
fi

# ─── NPM install ────────────────────────────────
echo ""
echo -e "${CYAN}[INFO]${NC} Menginstall dependensi Node.js (npm install)..."
npm install --ignore-scripts
if [ $? -ne 0 ]; then
    echo -e "${YELLOW}[PERINGATAN]${NC} npm install gagal, tapi setup tetap dilanjutkan."
else
    echo -e "${GREEN}[OK]${NC} Dependensi Node.js berhasil diinstall."
fi

# ─── Build assets ───────────────────────────────
echo ""
echo -e "${CYAN}[INFO]${NC} Build asset frontend (npm run build)..."
npm run build
if [ $? -ne 0 ]; then
    echo -e "${YELLOW}[PERINGATAN]${NC} npm run build gagal. Jalankan manual: npm run dev"
else
    echo -e "${GREEN}[OK]${NC} Asset frontend berhasil di-build."
fi

# ─── Selesai ────────────────────────────────────
echo ""
echo "  ============================================"
echo -e "  ${GREEN} SETUP SELESAI!${NC}"
echo "  ============================================"
echo ""
echo "  Jalankan aplikasi dengan perintah:"
echo "    php artisan serve"
echo ""
echo "  Atau jalankan mode development lengkap:"
echo "    composer dev"
echo ""
