<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qms_document_attachments', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('document_id')->constrained('qms_documents')->onDelete('cascade');
            $blueprint->string('file_name');
            $blueprint->string('file_path');
            $blueprint->string('file_size');
            $blueprint->string('mime_type');
            $blueprint->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $blueprint->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $blueprint->timestamps();
            $blueprint->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qms_document_attachments');
    }
};
