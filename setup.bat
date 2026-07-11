@echo off
chcp 65001 >nul
title SIPERBANG - Setup Otomatis

echo.
echo  ============================================
echo   SIPERBANG - Setup Otomatis
echo  ============================================
echo.

REM ─── Cek PHP ────────────────────────────────
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] PHP tidak ditemukan. Pastikan PHP sudah terinstall dan ada di PATH.
    pause & exit /b 1
)

REM ─── Cek Composer ───────────────────────────
composer -V >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Composer tidak ditemukan. Download di https://getcomposer.org
    pause & exit /b 1
)

REM ─── Cek psql (PostgreSQL) ──────────────────
psql --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] PostgreSQL tidak ditemukan. Pastikan PostgreSQL sudah terinstall dan psql ada di PATH.
    echo         Download di https://www.postgresql.org/download/
    pause & exit /b 1
)

echo [OK] PHP, Composer, dan PostgreSQL ditemukan.
echo.

REM ─── Tanya input database ───────────────────
echo Masukkan konfigurasi database PostgreSQL Anda:
echo (Kosongkan dan tekan Enter untuk pakai nilai default)
echo.

set /p DB_HOST="Host database [127.0.0.1]: "
if "%DB_HOST%"=="" set DB_HOST=127.0.0.1

set /p DB_PORT="Port database [5432]: "
if "%DB_PORT%"=="" set DB_PORT=5432

set /p DB_DATABASE="Nama database [siperbang]: "
if "%DB_DATABASE%"=="" set DB_DATABASE=siperbang

set /p DB_USERNAME="Username PostgreSQL [postgres]: "
if "%DB_USERNAME%"=="" set DB_USERNAME=postgres

set /p DB_PASSWORD="Password PostgreSQL: "
if "%DB_PASSWORD%"=="" (
    echo [PERINGATAN] Password kosong, lanjut tanpa password.
)

echo.
echo [INFO] Membuat database '%DB_DATABASE%' jika belum ada...
psql -h %DB_HOST% -p %DB_PORT% -U %DB_USERNAME% -c "SELECT 1 FROM pg_database WHERE datname='%DB_DATABASE%'" | findstr /c:"1 row" >nul 2>&1
if %errorlevel% neq 0 (
    psql -h %DB_HOST% -p %DB_PORT% -U %DB_USERNAME% -c "CREATE DATABASE %DB_DATABASE%;" >nul 2>&1
    if %errorlevel% neq 0 (
        echo [ERROR] Gagal membuat database. Cek username/password PostgreSQL Anda.
        pause & exit /b 1
    )
    echo [OK] Database '%DB_DATABASE%' berhasil dibuat.
) else (
    echo [OK] Database '%DB_DATABASE%' sudah ada, dilewati.
)

REM ─── Salin .env ─────────────────────────────
echo.
echo [INFO] Menyiapkan file .env...
if not exist ".env" (
    copy ".env.example" ".env" >nul
    echo [OK] File .env dibuat dari .env.example.
) else (
    echo [OK] File .env sudah ada, dilewati.
)

REM ─── Tulis konfigurasi DB ke .env ───────────
echo [INFO] Menulis konfigurasi database ke .env...
php -r "
$env = file_get_contents('.env');
$env = preg_replace('/^DB_CONNECTION=.*/m', 'DB_CONNECTION=pgsql', $env);
$env = preg_replace('/^#?\s*DB_HOST=.*/m', 'DB_HOST=%DB_HOST%', $env);
$env = preg_replace('/^#?\s*DB_PORT=.*/m', 'DB_PORT=%DB_PORT%', $env);
$env = preg_replace('/^#?\s*DB_DATABASE=.*/m', 'DB_DATABASE=%DB_DATABASE%', $env);
$env = preg_replace('/^#?\s*DB_USERNAME=.*/m', 'DB_USERNAME=%DB_USERNAME%', $env);
$env = preg_replace('/^#?\s*DB_PASSWORD=.*/m', 'DB_PASSWORD=\"%DB_PASSWORD%\"', $env);
file_put_contents('.env', $env);
echo 'done';
"
echo [OK] Konfigurasi database berhasil ditulis ke .env.

REM ─── Composer install ───────────────────────
echo.
echo [INFO] Menginstall dependensi PHP (composer install)...
composer install --no-interaction --prefer-dist --optimize-autoloader
if %errorlevel% neq 0 (
    echo [ERROR] composer install gagal.
    pause & exit /b 1
)
echo [OK] Dependensi PHP berhasil diinstall.

REM ─── Generate APP_KEY ───────────────────────
echo.
echo [INFO] Generate APP_KEY...
php artisan key:generate --force
if %errorlevel% neq 0 (
    echo [ERROR] Gagal generate APP_KEY.
    pause & exit /b 1
)

REM ─── Jalankan migrasi ───────────────────────
echo.
echo [INFO] Menjalankan migrasi database...
php artisan migrate --force
if %errorlevel% neq 0 (
    echo [ERROR] Migrasi gagal. Cek koneksi database Anda.
    pause & exit /b 1
)
echo [OK] Migrasi berhasil.

REM ─── Jalankan seeder (opsional) ─────────────
echo.
set /p RUN_SEED="Jalankan database seeder? (data awal/contoh) [y/N]: "
if /i "%RUN_SEED%"=="y" (
    php artisan db:seed --force
    if %errorlevel% neq 0 (
        echo [PERINGATAN] Seeder gagal, tapi setup tetap dilanjutkan.
    ) else (
        echo [OK] Seeder berhasil dijalankan.
    )
)

REM ─── NPM install ────────────────────────────
echo.
echo [INFO] Menginstall dependensi Node.js (npm install)...
npm install --ignore-scripts
if %errorlevel% neq 0 (
    echo [PERINGATAN] npm install gagal, tapi setup tetap dilanjutkan.
) else (
    echo [OK] Dependensi Node.js berhasil diinstall.
)

REM ─── Build assets ───────────────────────────
echo.
echo [INFO] Build asset frontend (npm run build)...
npm run build
if %errorlevel% neq 0 (
    echo [PERINGATAN] npm run build gagal. Jalankan manual: npm run dev
) else (
    echo [OK] Asset frontend berhasil di-build.
)

REM ─── Selesai ────────────────────────────────
echo.
echo  ============================================
echo   SETUP SELESAI!
echo  ============================================
echo.
echo  Jalankan aplikasi dengan perintah:
echo    php artisan serve
echo.
echo  Atau jalankan mode development lengkap:
echo    composer dev
echo.
pause
