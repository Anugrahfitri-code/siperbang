<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('code')->unique();
            $table->string('name');
            $table->integer('qty')->default(0);
            $table->string('unit');
            $table->date('last_updated')->nullable();
            $table->timestamps();
        });

        Schema::create('item_requests', function (Blueprint $table) {
            $table->id();
            $table->string('bon_no')->unique();
            $table->string('section');
            $table->string('item_name');
            $table->integer('qty_requested');
            $table->integer('qty_available')->default(0);
            $table->integer('qty_fulfilled')->default(0);
            $table->string('unit');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->date('date');
            $table->string('requester');
            $table->date('last_updated')->nullable();
            $table->timestamps();
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->string('store_name');
            $table->date('date');
            $table->boolean('is_taxed')->default(false);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->boolean('is_verified')->default(false);
            $table->string('status');
            $table->string('method')->nullable();
            $table->string('bast_name')->nullable();
            $table->date('bast_date')->nullable();
            $table->timestamps();
        });

        Schema::create('receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('qty');
            $table->decimal('price', 15, 2);
            $table->decimal('subtotal', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('history_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor');
            $table->string('action');
            $table->text('details');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history_logs');
        Schema::dropIfExists('receipt_items');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('item_requests');
        Schema::dropIfExists('stock_items');
    }
};
