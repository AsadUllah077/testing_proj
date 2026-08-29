<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pay_runs', function (Blueprint $table) {
            $table->string('run_type', 30)->default('regular')->after('frequency');
            $table->foreignId('original_pay_run_id')->nullable()->after('run_type')->constrained('pay_runs')->nullOnDelete();
            $table->string('preflight_status', 20)->default('pending')->after('approval_status');
            $table->timestamp('preflight_checked_at')->nullable()->after('preflight_status');
            $table->foreignId('preflight_checked_by')->nullable()->after('preflight_checked_at')->constrained('users')->nullOnDelete();
            $table->text('preflight_override_reason')->nullable()->after('preflight_checked_by');
            $table->timestamp('reversed_at')->nullable()->after('accounting_posted_by');
            $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->nullOnDelete();
            $table->string('reversal_unique_no')->nullable()->after('reversed_by');

            $table->index(['company_id', 'run_type', 'original_pay_run_id'], 'pay_runs_type_original_idx');
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->string('tax_fiscal_year', 20)->nullable()->after('ytd_net');
            $table->json('tax_calculation_snapshot')->nullable()->after('tax_fiscal_year');
        });

        Schema::table('employee_salary_advances', function (Blueprint $table) {
            $table->string('recovery_method', 20)->default('fixed')->after('remaining_amount');
            $table->decimal('installment_amount', 18, 4)->nullable()->after('recovery_method');
            $table->decimal('recovery_percentage', 8, 4)->nullable()->after('installment_amount');
            $table->decimal('max_net_deduction_percentage', 8, 4)->default(50)->after('recovery_percentage');
            $table->date('recovery_start_date')->nullable()->after('max_net_deduction_percentage');
            $table->unsignedInteger('installments_total')->nullable()->after('recovery_start_date');
            $table->unsignedInteger('installments_recovered')->default(0)->after('installments_total');
            $table->timestamp('recovery_paused_at')->nullable()->after('installments_recovered');
        });

        Schema::create('employee_salary_advance_recoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_salary_advance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pay_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payslip_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 18, 4);
            $table->timestamp('recovered_at');
            $table->timestamps();
            $table->unique(['employee_salary_advance_id', 'pay_run_id'], 'advance_recovery_run_unique');
        });

        Schema::create('payroll_preflight_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pay_run_id')->constrained()->cascadeOnDelete();
            $table->string('check_code', 80);
            $table->text('reason');
            $table->foreignId('overridden_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'pay_run_id']);
        });

        Schema::create('payroll_final_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->date('settlement_date');
            $table->date('last_working_date');
            $table->json('lines');
            $table->decimal('gross_amount', 18, 4)->default(0);
            $table->decimal('deduction_amount', 18, 4)->default(0);
            $table->decimal('net_amount', 18, 4)->default(0);
            $table->string('status', 20)->default('draft');
            $table->string('unique_no')->nullable()->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payroll_cost_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('cost_center')->nullable();
            $table->decimal('percentage', 8, 4);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'employee_id', 'effective_from'], 'payroll_cost_alloc_lookup');
        });

        Schema::create('payroll_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('assumptions');
            $table->json('results');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('compensation_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('current_salary', 18, 4);
            $table->decimal('proposed_salary', 18, 4);
            $table->decimal('approved_salary', 18, 4)->nullable();
            $table->date('effective_date');
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payroll_variance_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pay_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->text('comment');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['pay_run_id', 'employee_id'], 'payroll_variance_comment_unique');
        });

        Schema::create('employee_bank_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('bank_account_number');
            $table->string('bank_iban')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_bank_change_requests');
        Schema::dropIfExists('payroll_variance_comments');
        Schema::dropIfExists('compensation_reviews');
        Schema::dropIfExists('payroll_scenarios');
        Schema::dropIfExists('payroll_cost_allocations');
        Schema::dropIfExists('payroll_final_settlements');
        Schema::dropIfExists('payroll_preflight_overrides');
        Schema::dropIfExists('employee_salary_advance_recoveries');

        Schema::table('employee_salary_advances', function (Blueprint $table) {
            $table->dropColumn([
                'recovery_method', 'installment_amount', 'recovery_percentage',
                'max_net_deduction_percentage', 'recovery_start_date', 'installments_total',
                'installments_recovered', 'recovery_paused_at',
            ]);
        });
        Schema::table('payslips', fn (Blueprint $table) => $table->dropColumn(['tax_fiscal_year', 'tax_calculation_snapshot']));
        Schema::table('pay_runs', function (Blueprint $table) {
            $table->dropIndex('pay_runs_type_original_idx');
            $table->dropConstrainedForeignId('original_pay_run_id');
            $table->dropConstrainedForeignId('preflight_checked_by');
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn([
                'run_type', 'preflight_status', 'preflight_checked_at', 'preflight_override_reason',
                'reversed_at', 'reversal_unique_no',
            ]);
        });
    }
};
