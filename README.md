<div align="center">

# 🚀 SIPERBANG

**Sistem Informasi berbasis Laravel 13 + PostgreSQL**

[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16+-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)](https://postgresql.org)
[![Node.js](https://img.shields.io/badge/Node.js-18+-339933?style=for-the-badge&logo=nodedotjs&logoColor=white)](https://nodejs.org)

</div>

---

## 📋 Daftar Isi

- [Kebutuhan Sistem](#-kebutuhan-sistem)
- [Cara Clone Proyek](#-cara-clone-proyek)
- [Setup Otomatis ⚡ (Direkomendasikan)](#-setup-otomatis--direkomendasikan)
- [Setup Manual](#-setup-manual)
- [Menjalankan Aplikasi](#-menjalankan-aplikasi)
- [Struktur Proyek](#-struktur-proyek)
- [Perintah Berguna](#-perintah-berguna)
- [Troubleshooting](#-troubleshooting)

---

## 💻 Kebutuhan Sistem

Pastikan komputer kamu sudah terinstall software berikut sebelum memulai:

| Software | Versi Minimum | Link Download |
|---|---|---|
| **PHP** | 8.3+ | [php.net/downloads](https://php.net/downloads) |
| **Composer** | 2.x | [getcomposer.org](https://getcomposer.org) |
| **PostgreSQL** | 14+ | [postgresql.org/download](https://www.postgresql.org/download/) |
| **Node.js** | 18+ | [nodejs.org](https://nodejs.org) |
| **Git** | terbaru | [git-scm.com](https://git-scm.com) |

> **Tips:** Untuk Windows, disarankan menggunakan [Laragon](https://laragon.org) atau [XAMPP + PostgreSQL](https://www.postgresql.org/download/windows/) agar lebih mudah.

---

## 📥 Cara Clone Proyek

Buka terminal / command prompt, lalu jalankan:

```bash
git clone https://github.com/USERNAME/siperbang.git
cd siperbang
```

> Ganti `USERNAME` dengan username GitHub pemilik proyek.

---

## ⚡ Setup Otomatis (Direkomendasikan)

Ini cara **paling mudah**. Script akan mengurus semuanya secara otomatis!

### 🪟 Windows

1. Masuk ke folder proyek
2. **Double-click** file `setup.bat`
3. Ikuti petunjuk yang muncul di layar
4. Selesai! ✅

Atau jalankan lewat Command Prompt:
```cmd
setup.bat
```

### 🐧 Linux / macOS

```bash
# Beri izin eksekusi dulu
chmod +x setup.sh

# Jalankan script
./setup.sh
```

### Apa yang dilakukan script otomatis?

Script akan melakukan langkah-langkah berikut secara otomatis:

1. ✅ Mengecek PHP, Composer, PostgreSQL sudah terinstall
2. ✅ Menanyakan konfigurasi database (host, port, nama database, username, password)
3. ✅ **Membuat database PostgreSQL otomatis** (tidak perlu buat manual!)
4. ✅ Menyalin `.env.example` → `.env`
5. ✅ Menulis konfigurasi database ke `.env`
6. ✅ Menjalankan `composer install`
7. ✅ Generate `APP_KEY`
8. ✅ Menjalankan **migrasi database** (tabel terbuat otomatis)
9. ✅ Menanyakan apakah ingin menjalankan seeder (data awal)
10. ✅ Menjalankan `npm install` dan `npm run build`

---

## 🔧 Setup Manual

Jika ingin melakukan setup secara manual, ikuti langkah-langkah berikut:

### Langkah 1 — Install Dependensi PHP

```bash
composer install
```

### Langkah 2 — Salin File .env

```bash
# Windows
copy .env.example .env

# Linux / macOS
cp .env.example .env
```

### Langkah 3 — Buat Database PostgreSQL

Buka **pgAdmin** atau **psql**, lalu buat database baru:

```sql
CREATE DATABASE siperbang;
```

Atau via terminal:
```bash
psql -U postgres -c "CREATE DATABASE siperbang;"
```

### Langkah 4 — Konfigurasi .env

Buka file `.env` dengan teks editor, lalu ubah bagian database:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=siperbang
DB_USERNAME=postgres
DB_PASSWORD=password_kamu
```

> Sesuaikan `DB_USERNAME` dan `DB_PASSWORD` dengan kredensial PostgreSQL kamu.

### Langkah 5 — Generate APP_KEY

```bash
php artisan key:generate
```

### Langkah 6 — Jalankan Migrasi Database

```bash
php artisan migrate
```

Perintah ini akan **membuat semua tabel** di database secara otomatis.

### Langkah 7 — (Opsional) Jalankan Seeder

```bash
php artisan db:seed
```

Ini akan mengisi database dengan data awal/contoh.

### Langkah 8 — Install Dependensi Node.js

```bash
npm install
```

### Langkah 9 — Build Asset Frontend

```bash
npm run build
```

---

## ▶️ Menjalankan Aplikasi

Setelah setup selesai, jalankan aplikasi dengan salah satu perintah berikut:

### Mode Sederhana (hanya server)

```bash
php artisan serve
```

Kemudian buka browser dan akses: **http://127.0.0.1:8000**

### Mode Development Lengkap (server + queue + log + vite)

```bash
composer dev
```

Perintah ini menjalankan sekaligus:
- 🌐 PHP server (`php artisan serve`)
- 📬 Queue worker (`php artisan queue:listen`)
- 📋 Log viewer (`php artisan pail`)
- ⚡ Vite dev server (`npm run dev`)

---

## 📁 Struktur Proyek

```
siperbang/
├── app/
│   ├── Http/
│   │   └── Controllers/    # Controller aplikasi
│   ├── Models/             # Model Eloquent
│   └── Providers/          # Service Provider
├── database/
│   ├── migrations/         # File migrasi database
│   ├── seeders/            # Data seeder
│   └── factories/          # Factory untuk testing
├── resources/
│   ├── views/              # Template Blade
│   ├── css/                # File CSS
│   └── js/                 # File JavaScript
├── routes/
│   └── web.php             # Definisi route web
├── public/                 # File yang dapat diakses publik
├── .env.example            # Contoh konfigurasi environment
├── setup.bat               # Script setup otomatis (Windows)
├── setup.sh                # Script setup otomatis (Linux/Mac)
└── composer.json           # Dependensi PHP
```

---

## 🛠️ Perintah Berguna

| Perintah | Fungsi |
|---|---|
| `php artisan serve` | Menjalankan server development |
| `php artisan migrate` | Menjalankan migrasi database |
| `php artisan migrate:fresh` | Reset dan jalankan ulang semua migrasi |
| `php artisan migrate:fresh --seed` | Reset migrasi + isi data awal |
| `php artisan db:seed` | Menjalankan seeder |
| `php artisan cache:clear` | Membersihkan cache |
| `php artisan config:clear` | Membersihkan cache konfigurasi |
| `php artisan route:list` | Melihat semua route yang terdaftar |
| `npm run dev` | Menjalankan Vite development server |
| `npm run build` | Build asset untuk production |
| `composer dev` | Menjalankan semua service development |

---

## ❓ Troubleshooting

### ❌ "SQLSTATE: could not connect to server"

Pastikan PostgreSQL sudah berjalan:
- **Windows:** Buka `Services` → cari `postgresql` → Start
- **Linux:** `sudo service postgresql start`
- **macOS:** `brew services start postgresql`

Cek juga konfigurasi `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` di file `.env`.

---

### ❌ "php: command not found" atau "php tidak dikenali"

PHP belum ditambahkan ke PATH sistem. Beberapa solusi:
- **Windows:** Tambahkan path folder PHP ke Environment Variables → PATH
- **Laragon:** Pastikan sudah klik "Add Laragon to Path"

---

### ❌ "composer: command not found"

Download dan install Composer dari [getcomposer.org](https://getcomposer.org/download/).

---

### ❌ "npm: command not found"

Download dan install Node.js dari [nodejs.org](https://nodejs.org) (pilih versi LTS).

---

### ❌ Halaman kosong atau error setelah setup

Coba jalankan perintah berikut:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

### ❌ Error "No application encryption key has been specified"

```bash
php artisan key:generate
```

---

## 📞 Kontak

Jika ada pertanyaan atau masalah, hubungi tim pengembang proyek ini.

---

<div align="center">

Dibuat dengan ❤️ menggunakan [Laravel](https://laravel.com)

</div>
