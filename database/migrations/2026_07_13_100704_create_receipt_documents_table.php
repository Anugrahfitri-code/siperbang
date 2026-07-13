<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('receipt_documents');
        Schema::create('receipt_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->nullable()->constrained('receipts')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('original_filename');
            $table->string('storage_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->string('sha256')->index();
            
            $table->string('status')->index();
            
            $table->string('ocr_engine')->nullable();
            $table->string('ocr_engine_version')->nullable();
            
            $table->longText('raw_text')->nullable();
            $table->json('raw_result')->nullable();
            $table->json('parsed_result')->nullable();
            $table->decimal('overall_confidence', 5, 4)->nullable();
            
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_documents');
    }
};
