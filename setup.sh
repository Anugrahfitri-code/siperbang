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

# ─── Cek Node.js & npm ──────────────────────────
if ! command -v node &> /dev/null || ! command -v npm &> /dev/null; then
    echo -e "${RED}[ERROR]${NC} Node.js atau npm tidak ditemukan. Install versi 20+ di https://nodejs.org"
    exit 1
fi

echo -e "${GREEN}[OK]${NC} PHP, Composer, Node.js, dan npm ditemukan."
echo ""

# ─── Salin .env ─────────────────────────────────
echo -e "${CYAN}[INFO]${NC} Menyiapkan file .env..."
if [ ! -f ".env" ]; then
    cp .env.example .env
    echo -e "${GREEN}[OK]${NC} File .env dibuat dari .env.example."
else
    echo -e "${GREEN}[OK]${NC} File .env sudah ada, dilewati."
fi

# ─── Setup SQLite ───────────────────────────────
echo ""
echo -e "${CYAN}[INFO]${NC} Menyiapkan database SQLite..."
mkdir -p database
touch database/database.sqlite
php -r "
\$env = file_get_contents('.env');
\$env = preg_replace('/^DB_CONNECTION=.*/m', 'DB_CONNECTION=sqlite', \$env);
file_put_contents('.env', \$env);
"
echo -e "${GREEN}[OK]${NC} Database SQLite disiapkan dan konfigurasi .env diperbarui."

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
    echo -e "${RED}[ERROR]${NC} npm install gagal."
    exit 1
else
    echo -e "${GREEN}[OK]${NC} Dependensi Node.js berhasil diinstall."
fi

# ─── Build assets ───────────────────────────────
echo ""
echo -e "${CYAN}[INFO]${NC} Build asset frontend (npm run build)..."
npm run build
if [ $? -ne 0 ]; then
    echo -e "${RED}[ERROR]${NC} npm run build gagal."
    exit 1
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
