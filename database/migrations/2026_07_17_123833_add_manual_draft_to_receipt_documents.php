<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'receipt_documents',
            function (Blueprint $table) {
                $table->json('manual_draft')
                    ->nullable()
                    ->after('parsed_result');

                $table->foreignId('draft_saved_by')
                    ->nullable()
                    ->after('manual_draft')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('draft_saved_at')
                    ->nullable()
                    ->after('draft_saved_by');
            }
        );

        /*
         * Nomor invoice tidak boleh unik secara global.
         * Toko berbeda dapat memiliki nomor invoice yang sama.
         */
        Schema::table(
            'receipts',
            function (Blueprint $table) {
                $table->dropUnique(
                    'receipts_invoice_no_unique'
                );

                $table->unique(
                    [
                        'store_name',
                        'invoice_no',
                    ],
                    'receipts_store_invoice_unique',
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'receipts',
            function (Blueprint $table) {
                $table->dropUnique(
                    'receipts_store_invoice_unique'
                );

                $table->unique(
                    'invoice_no'
                );
            }
        );

        Schema::table(
            'receipt_documents',
            function (Blueprint $table) {
                $table->dropConstrainedForeignId(
                    'draft_saved_by'
                );

                $table->dropColumn([
                    'manual_draft',
                    'draft_saved_at',
                ]);
            }
        );
    }
};
