<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qms_sops', function (Blueprint $table) {
            $table->id();
            $table->string('sop_number')->unique()->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('department')->nullable();
            $table->string('linked_product')->nullable();
            $table->string('version')->default('1.0');
            $table->enum('status', ['draft', 'review', 'approved', 'archived'])->default('draft');
            $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('qms_sop_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sop_id')->constrained('qms_sops')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('media_path')->nullable();
            $table->boolean('has_checklist')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qms_sop_steps');
        Schema::dropIfExists('qms_sops');
    }
};
