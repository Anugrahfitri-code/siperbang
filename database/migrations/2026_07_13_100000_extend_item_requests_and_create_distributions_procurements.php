<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Extend item_requests ──────────────────────────────────────────
        Schema::table('item_requests', function (Blueprint $table) {
            // FK to stock_items (nullable — item may not exist in stock yet)
            $table->foreignId('stock_item_id')
                  ->nullable()
                  ->constrained('stock_items')
                  ->nullOnDelete()
                  ->after('requester');

            // Qty that still needs procurement after partial stock fulfillment
            $table->integer('qty_to_procure')->default(0)->after('qty_fulfilled');

            // Whether stock deduction has already been applied (prevents double-deduct)
            $table->boolean('stock_allocated')->default(false)->after('qty_to_procure');

            // Procurement method chosen by Petugas Persediaan
            $table->string('procurement_method')->nullable()->after('stock_allocated');

            // Vendor name (only for Pengadaan Vendor)
            $table->string('vendor_name')->nullable()->after('procurement_method');
        });

        // ── 2. distributions ────────────────────────────────────────────────
        // Records each time stock is physically distributed for a request.
        // One request can have at most one distribution record.
        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_request_id')
                  ->constrained('item_requests')
                  ->cascadeOnDelete();
            $table->foreignId('stock_item_id')
                  ->constrained('stock_items')
                  ->restrictOnDelete();
            $table->integer('qty_distributed');
            $table->string('distributed_by');   // username / name of petugas
            $table->date('distributed_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── 3. procurements ─────────────────────────────────────────────────
        // Records procurement action for items that are partially / fully unfulfilled.
        Schema::create('procurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_request_id')
                  ->constrained('item_requests')
                  ->cascadeOnDelete();

            // 'Pengadaan Vendor' | 'Pengadaan Sendiri (Toko)'
            $table->string('method');

            $table->string('vendor_name')->nullable();
            $table->string('store_name')->nullable();

            $table->integer('qty_procured');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->boolean('is_taxed')->default(false);
            $table->decimal('tax_rate', 5, 2)->default(0);

            // Status: Diproses | Diterima | Dibatalkan
            $table->string('status')->default('Diproses');

            $table->string('invoice_no')->nullable();
            $table->string('bast_name')->nullable();
            $table->date('bast_date')->nullable();
            $table->string('contract_no')->nullable();   // for vendor

            $table->string('processed_by');
            $table->date('procurement_date');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurements');
        Schema::dropIfExists('distributions');

        Schema::table('item_requests', function (Blueprint $table) {
            $table->dropForeign(['stock_item_id']);
            $table->dropColumn([
                'stock_item_id',
                'qty_to_procure',
                'stock_allocated',
                'procurement_method',
                'vendor_name',
            ]);
        });
    }
};
