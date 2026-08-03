<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('history_logs', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('details');
            $table->json('metadata')->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('history_logs', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'metadata']);
        });
    }
};
