<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('file_name_original');
            $table->string('file_name_stored');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('upload_date');
            $table->integer('sheets_count')->default(0);
            $table->integer('rows_count')->default(0);
            $table->integer('valid_rows_count')->default(0);
            $table->integer('error_rows_count')->default(0);
            $table->integer('rejected_rows_count')->default(0);
            $table->string('status')->default('Draft'); // Draft, Menunggu Verifikasi, Sebagian Valid, Selesai, Perlu Perbaikan
            $table->timestamps();
        });

        Schema::create('stok_upload_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stok_upload_id')->constrained('stok_uploads')->cascadeOnDelete();
            $table->string('sheet_name');
            $table->string('supplier')->nullable();
            $table->integer('no_urut')->nullable();
            $table->string('kode_persediaan_excel')->nullable();
            $table->string('suggested_kode_persediaan')->nullable();
            $table->string('nama_barang');
            $table->integer('qty');
            $table->string('unit');
            $table->decimal('price_unit', 15, 2);
            $table->decimal('price_unit_taxed', 15, 2)->nullable();
            $table->decimal('total_excel', 15, 2);
            $table->decimal('total_calculated', 15, 2);
            $table->boolean('is_taxed')->default(false);
            $table->string('status_validation'); // Menunggu Verifikasi, Perlu Perbaikan
            $table->string('status_verification')->default('Pending'); // Pending, Setuju, Perbaiki, Tolak
            $table->string('verified_kode_persediaan')->nullable();
            $table->text('notes_error')->nullable();
            $table->boolean('is_duplicate')->default(false);
            $table->timestamps();
        });

        Schema::create('stok_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained('stock_items')->cascadeOnDelete();
            $table->foreignId('stok_upload_id')->nullable()->constrained('stok_uploads')->nullOnDelete();
            $table->integer('qty_change');
            $table->integer('qty_before');
            $table->integer('qty_after');
            $table->string('type'); // Upload Excel, BON Digital, Penyesuaian
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('description');
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('stok_histories');
        Schema::dropIfExists('stok_upload_details');
        Schema::dropIfExists('stok_uploads');
    }
};
