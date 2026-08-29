<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qms_policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_number')->unique()->nullable();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->enum('status', ['draft', 'active', 'under_review', 'archived'])->default('draft');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->string('department')->nullable();
            $table->string('classification')->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qms_policies');
    }
};
