<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipt_items', function (Blueprint $table) {
            $table->string('unit', 30)
                ->nullable()
                ->after('qty');

            $table->string('inventory_code', 20)
                ->nullable()
                ->after('unit');

            $table->index(
                'inventory_code',
                'receipt_items_inventory_code_index'
            );

            $table->foreign('inventory_code')
                ->references('kode')
                ->on('kode_persediaan')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('receipt_items', function (Blueprint $table) {
            $table->dropForeign([
                'inventory_code',
            ]);

            $table->dropIndex(
                'receipt_items_inventory_code_index'
            );

            $table->dropColumn([
                'unit',
                'inventory_code',
            ]);
        });
    }
};
