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
        Schema::create('compliance_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('statutory_body_id')->constrained()->cascadeOnDelete();
            $table->string('event_type'); // filing, payment, registration, etc.
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->date('period_start')->nullable(); // For periodic filings
            $table->date('period_end')->nullable();
            $table->decimal('amount_due', 15, 2)->nullable();
            $table->string('status')->default('pending'); // pending, completed, overdue, waived
            $table->date('completed_at')->nullable();
            $table->decimal('penalty_amount', 15, 2)->default(0);
            $table->json('metadata')->nullable(); // Additional event-specific data
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable(); // monthly, quarterly, annually
            $table->timestamps();
            
            $table->index(['company_id', 'due_date']);
            $table->index(['statutory_body_id', 'status']);
            $table->index(['status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_events');
    }
};
