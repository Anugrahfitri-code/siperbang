<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipt_items', function (Blueprint $table) {
            $table->foreignId('stock_item_id')
                ->nullable()
                ->after('inventory_code')
                ->constrained('stock_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('receipt_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_item_id');
        });
    }
};
