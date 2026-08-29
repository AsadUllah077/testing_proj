<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('processed_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('document_type'); // 'invoice', 'receipt', 'bank_statement'
            $table->json('extracted_data')->nullable(); // The structured JSON from AI
            $table->string('status')->default('draft'); // draft, processed, rejected
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of the created bill/sale/etc
            $table->string('reference_type')->nullable(); // Model class of the created record
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processed_documents');
    }
};
