<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add storage_location to stock_items and stok_upload_details
        Schema::table('stock_items', function (Blueprint $table) {
            $table->string('storage_location')->nullable()->after('unit');
        });

        Schema::table('stok_upload_details', function (Blueprint $table) {
            $table->string('storage_location')->nullable()->after('unit');
        });

        // 2. Create bon_headers table
        Schema::create('bon_headers', function (Blueprint $table) {
            $table->id();
            $table->string('bon_no')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('section');
            $table->string('requester');
            $table->date('date');
            $table->string('status');
            $table->text('keperluan')->nullable();
            $table->text('catatan')->nullable();
            $table->date('last_updated')->nullable();
            $table->timestamps();
        });

        // 3. Update item_requests table
        Schema::table('item_requests', function (Blueprint $table) {
            $table->text('verifier_notes')->nullable()->after('notes');
            $table->unsignedBigInteger('bon_header_id')->nullable()->after('user_id');

            // Drop unique constraint on bon_no (so multiple items can share a BON)
            $table->dropUnique('item_requests_bon_no_unique');
        });

        // 4. Set FK from item_requests to bon_headers
        Schema::table('item_requests', function (Blueprint $table) {
            $table->foreign('bon_header_id')->references('id')->on('bon_headers')->nullOnDelete();
        });

        // 5. Create bon_status_histories table
        Schema::create('bon_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bon_header_id')->constrained('bon_headers')->cascadeOnDelete();
            $table->string('status_before')->nullable();
            $table->string('status_after');
            $table->string('changed_by');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 6. Seed some default storage locations for testing
        DB::table('stock_items')->where('code', '1010301001')->update(['storage_location' => 'Rak A-1']);
        DB::table('stock_items')->where('code', '1010301003')->update(['storage_location' => 'Rak A-2']);
        DB::table('stock_items')->where('code', '1010301005')->update(['storage_location' => 'Rak B-1']);
        DB::table('stock_items')->where('code', '1010301006')->update(['storage_location' => 'Rak B-2']);
        DB::table('stock_items')->where('code', '1010301008')->update(['storage_location' => 'Rak C-1']);
        DB::table('stock_items')->where('code', '1010301010')->update(['storage_location' => 'Rak C-2']);
        DB::table('stock_items')->where('code', '1010302001')->update(['storage_location' => 'Rak D-1']);
        DB::table('stock_items')->where('code', '1010305002')->update(['storage_location' => 'Rak E-1']);
        DB::table('stock_items')->where('code', '1010305004')->update(['storage_location' => 'Rak E-2']);
        DB::table('stock_items')->where('code', '1010305008')->update(['storage_location' => 'Rak F-1']);
        DB::table('stock_items')->where('code', '1010305009')->update(['storage_location' => 'Rak F-2']);
        DB::table('stock_items')->where('code', '1010304006')->update(['storage_location' => 'Rak G-1']);
        DB::table('stock_items')->where('code', '1010304010')->update(['storage_location' => 'Rak G-2']);
        DB::table('stock_items')->where('code', '1010306010')->update(['storage_location' => 'Rak G-3']);
    }

    public function down(): void
    {
        Schema::dropIfExists('bon_status_histories');

        Schema::table('item_requests', function (Blueprint $table) {
            $table->dropForeign(['bon_header_id']);
            $table->dropColumn(['verifier_notes', 'bon_header_id']);
            $table->unique('bon_no');
        });

        Schema::dropIfExists('bon_headers');

        Schema::table('stok_upload_details', function (Blueprint $table) {
            $table->dropColumn('storage_location');
        });

        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropColumn('storage_location');
        });
    }
};
