<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qms_capas', function (Blueprint $table) {
            $table->id();
            $table->string('capa_number')->unique()->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('root_cause')->nullable();
            $table->string('linked_product')->nullable();
            $table->foreignId('linked_sop_id')->nullable()->constrained('qms_sops')->onDelete('set null');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['open', 'in_progress', 'closed'])->default('open');
            $table->date('deadline')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('closed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qms_capas');
    }
};
