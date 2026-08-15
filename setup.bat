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

REM ─── Cek Node.js & npm ────────────────────────
node -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Node.js tidak ditemukan. Install Node.js versi 22+ di https://nodejs.org
    pause & exit /b 1
)

npm -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] npm tidak ditemukan. Install Node.js di https://nodejs.org
    pause & exit /b 1
)

echo [OK] PHP, Composer, Node.js, dan npm ditemukan.
echo.

REM ─── Salin .env ─────────────────────────────
echo.
echo [INFO] Menyiapkan file .env...
if not exist ".env" (
    copy ".env.example" ".env" >nul
    echo [OK] File .env dibuat dari .env.example.
) else (
    echo [OK] File .env sudah ada, dilewati.
)

REM ─── Setup SQLite ───────────────────────────
echo.
echo [INFO] Menyiapkan database SQLite...
if not exist "database" mkdir database
if not exist "database\database.sqlite" type nul > "database\database.sqlite"
php -r "
\$env = file_get_contents('.env');
\$env = preg_replace('/^DB_CONNECTION=.*/m', 'DB_CONNECTION=sqlite', \$env);
file_put_contents('.env', \$env);
echo 'done';
"
echo [OK] Database SQLite disiapkan dan konfigurasi .env diperbarui.

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

REM ─── Public storage link ─────────────────────
echo.
echo [INFO] Menyiapkan akses publik untuk logo dan favicon...
php artisan storage:link
if %errorlevel% neq 0 (
    echo [PERINGATAN] storage:link gagal. Jalankan terminal sebagai Administrator atau buat public\storage secara manual.
) else (
    echo [OK] Tautan public\storage siap.
)

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
echo [INFO] Menginstall dependensi Node.js secara reproducible (npm ci)...
npm ci
if %errorlevel% neq 0 (
    echo [ERROR] npm ci gagal.
    pause & exit /b 1
) else (
    echo [OK] Dependensi Node.js berhasil diinstall.
)

REM ─── Validasi dan build assets ───────────────
echo.
echo [INFO] Memeriksa type dan lint frontend...
call npm run typecheck
if %errorlevel% neq 0 (
    echo [ERROR] Typecheck frontend gagal.
    pause & exit /b 1
)
call npm run lint
if %errorlevel% neq 0 (
    echo [ERROR] Lint frontend gagal.
    pause & exit /b 1
)

echo.
echo [INFO] Build asset frontend (npm run build)...
npm run build
if %errorlevel% neq 0 (
    echo [ERROR] npm run build gagal.
    pause & exit /b 1
) else (
    echo [OK] Asset frontend berhasil di-build.
)

call npm run verify:build
if %errorlevel% neq 0 (
    echo [ERROR] Integritas build frontend gagal diverifikasi.
    pause & exit /b 1
)

REM ─── Selesai ────────────────────────────────
echo.
echo  ============================================
echo   SETUP SELESAI!
echo  ============================================
echo.
echo  Untuk menjalankan aplikasi untuk pertama kali, buat akun Superadmin:
echo    php artisan app:provision-superadmin
echo.
echo  Lalu jalankan aplikasi dengan perintah:
echo    php artisan serve
echo.
echo  Atau jalankan mode development lengkap:
echo    composer dev
echo.
pause
