<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->foreignId('accounting_period_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // Recurrence settings
            $table->enum('frequency', ['weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->unsignedTinyInteger('interval')->default(1); // every N frequencies
            $table->date('start_date');
            $table->date('end_date')->nullable();        // null = run indefinitely
            $table->date('next_run_date');
            $table->date('last_run_date')->nullable();

            // Status
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');

            // Bill defaults
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->unsignedTinyInteger('payment_terms_days')->default(30);
            $table->text('note')->nullable();

            // Counts
            $table->unsignedInteger('bills_generated')->default(0);
            $table->unsignedInteger('max_occurrences')->nullable(); // null = unlimited

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status', 'next_run_date']);
        });

        Schema::create('recurring_bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_bill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_bill_items');
        Schema::dropIfExists('recurring_bills');
    }
};
