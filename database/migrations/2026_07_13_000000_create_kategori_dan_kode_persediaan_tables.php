<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_barang', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->timestamps();
        });

        Schema::create('kode_persediaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_barang_id')->nullable()->constrained('kategori_barang')->nullOnDelete();
            $table->string('kode')->unique();
            $table->string('nama_barang');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kode_persediaan');
        Schema::dropIfExists('kategori_barang');
    }
};
