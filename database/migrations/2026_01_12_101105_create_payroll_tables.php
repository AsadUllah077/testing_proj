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
        // Pay Items (Earnings/Deductions)
        Schema::create('pay_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['earning', 'deduction', 'reimbursement']);
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');
            $table->boolean('status')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // Employee Pay Structures (Recurring items for each employee)
        Schema::create('employee_pay_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('pay_item_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Pay Runs (Batch processing)
        Schema::create('pay_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('payment_date');
            $table->enum('status', ['draft', 'posted', 'paid', 'cancelled'])->default('draft');
            $table->string('unique_no')->nullable(); // For accounting reference
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        // Individual Payslips
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('pay_run_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->decimal('gross_earnings', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_pay', 15, 2)->default(0);
            $table->enum('status', ['draft', 'processed', 'paid'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });

        // Line items for each payslip
        Schema::create('payslip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->constrained()->onDelete('cascade');
            $table->foreignId('pay_item_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_items');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('pay_runs');
        Schema::dropIfExists('employee_pay_structures');
        Schema::dropIfExists('pay_items');
    }
};
