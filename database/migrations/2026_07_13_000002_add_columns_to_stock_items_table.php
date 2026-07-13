<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->foreignId('last_upload_id')->nullable()->constrained('stok_uploads')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropForeign(['last_upload_id']);
            $table->dropColumn(['last_upload_id', 'is_active']);
        });
    }
};
