<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qms_documents', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('doc_no')->unique();
            $blueprint->string('title');
            $blueprint->text('summary')->nullable();
            $blueprint->string('type')->default('Policy'); // SOP, Policy, etc.
            $blueprint->string('status')->default('draft'); // draft, under_review, active, archived
            $blueprint->string('revision_cycle')->nullable();
            $blueprint->string('version')->default('1.0');
            $blueprint->string('purpose')->nullable();
            $blueprint->string('retention')->nullable();
            
            $blueprint->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $blueprint->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');
            $blueprint->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            $blueprint->timestamp('last_reviewed_at')->nullable();
            $blueprint->timestamp('approved_at')->nullable();
            $blueprint->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            
            $blueprint->json('controlled_distribution')->nullable();
            $blueprint->boolean('is_confidential')->default(false);
            $blueprint->boolean('require_acknowledgment')->default(true);
            
            $blueprint->timestamps();
            $blueprint->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qms_documents');
    }
};
