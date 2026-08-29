<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qms_external_documents', function (Blueprint $table) {
            $table->id();
            $table->string('ref_no')->unique();
            $table->string('title');
            $table->string('source')->nullable();
            $table->string('status')->default('current');
            $table->date('expiry_date')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qms_external_documents');
    }
};
