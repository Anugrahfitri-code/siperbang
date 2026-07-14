<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refactor stok_uploads for:
 *   - SoftDeletes (30-day trash)
 *   - 4-step stepper tracking
 *   - Batalkan Transaksi (reversal)
 *   - Simplified status set
 *   - Granular per-column error on details
 *   - Reversal flag on stok_histories
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── stok_uploads ────────────────────────────────────────────
        Schema::table('stok_uploads', function (Blueprint $table) {

            // Soft delete support — 30-day trash
            $table->softDeletes()->after('status');

            // Stepper position tracker  1=Upload 2=Pemeriksaan 3=Verifikasi 4=Review
            $table->unsignedTinyInteger('current_step')->default(1)->after('status');

            // Cancellation / reversal metadata
            $table->timestamp('cancelled_at')->nullable()->after('current_step');
            $table->string('cancelled_by')->nullable()->after('cancelled_at');
            $table->text('cancellation_reason')->nullable()->after('cancelled_by');
        });

        // ── stok_upload_details ─────────────────────────────────────
        // Add granular error column name for improved per-column error display
        Schema::table('stok_upload_details', function (Blueprint $table) {
            $table->string('error_column')->nullable()
                  ->after('notes_error')
                  ->comment('Column letter/name where the error occurred, e.g. B, C, D, F');
        });

        // ── stok_histories ──────────────────────────────────────────
        // Flag for reversal entries created by Batalkan Transaksi
        Schema::table('stok_histories', function (Blueprint $table) {
            $table->boolean('is_reversal')->default(false)->after('notes');
            $table->unsignedBigInteger('reversal_of_id')->nullable()->after('is_reversal')
                  ->comment('Points to the original stok_histories.id that this row reverses');
        });
    }

    public function down(): void
    {
        Schema::table('stok_histories', function (Blueprint $table) {
            $table->dropColumn(['is_reversal', 'reversal_of_id']);
        });

        Schema::table('stok_upload_details', function (Blueprint $table) {
            $table->dropColumn('error_column');
        });

        Schema::table('stok_uploads', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['current_step', 'cancelled_at', 'cancelled_by', 'cancellation_reason']);
        });
    }
};
