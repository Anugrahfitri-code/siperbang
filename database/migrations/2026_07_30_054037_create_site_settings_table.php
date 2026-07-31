<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Insert Default Settings Data (Agar Web Tidak Kosong Saat Pertama Dipasang)
        $defaultSettings = [
            ['key' => 'app_name', 'value' => 'SIPERBANG'],
            ['key' => 'app_subtitle', 'value' => 'Sistem Informasi Persediaan Barang'],
            ['key' => 'instansi_name', 'value' => 'KOMDIGI'],
            ['key' => 'instansi_sub', 'value' => 'Kementerian Komunikasi dan Digital Republik Indonesia'],
            ['key' => 'login_heading', 'value' => 'Selamat Datang di Portal SIPERBANG.'],
            ['key' => 'login_description', 'value' => 'Pusat pengelolaan persediaan barang secara digital, dilengkapi fitur verifikasi nota otomatis menggunakan teknologi OCR AI, dan pemantauan stok real-time.'],
            ['key' => 'footer_copyright', 'value' => '© 2026 BBPSDM Komunikasi dan Digital Makassar. Seluruh hak cipta dilindungi.'],
            ['key' => 'app_logo_url', 'value' => '/images/brand/siperbang-symbol.png'],
            ['key' => 'instansi_logo_url', 'value' => '/images/brand/komdigi-logo.png'],
        ];

        foreach ($defaultSettings as $setting) {
            DB::table('site_settings')->insert([
                'key' => $setting['key'],
                'value' => $setting['value'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};