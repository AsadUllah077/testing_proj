<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_salary_advances', function (Blueprint $table) {
            $table->string('recovery_frequency', 20)->default('each_pay_run')->after('recovery_method');
        });
        Schema::table('payroll_final_settlements', function (Blueprint $table) {
            $table->foreignId('hr_approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('hr_approved_at')->nullable()->after('hr_approved_by');
            $table->foreignId('payroll_approved_by')->nullable()->after('hr_approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('payroll_approved_at')->nullable()->after('payroll_approved_by');
        });
        Schema::table('payroll_cost_allocations', function (Blueprint $table) {
            $table->string('branch')->nullable()->after('cost_center');
            $table->string('project_code')->nullable()->after('branch');
            $table->string('location')->nullable()->after('project_code');
            $table->string('funding_source')->nullable()->after('location');
        });
        Schema::table('compensation_reviews', function (Blueprint $table) {
            $table->decimal('market_benchmark', 18, 4)->nullable()->after('proposed_salary');
            $table->text('manager_recommendation')->nullable()->after('reason');
            $table->text('hr_recommendation')->nullable()->after('manager_recommendation');
            $table->decimal('annual_budget_impact', 18, 4)->default(0)->after('hr_recommendation');
        });
    }

    public function down(): void
    {
        Schema::table('compensation_reviews', fn (Blueprint $table) => $table->dropColumn(['market_benchmark', 'manager_recommendation', 'hr_recommendation', 'annual_budget_impact']));
        Schema::table('payroll_cost_allocations', fn (Blueprint $table) => $table->dropColumn(['branch', 'project_code', 'location', 'funding_source']));
        Schema::table('payroll_final_settlements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hr_approved_by');
            $table->dropConstrainedForeignId('payroll_approved_by');
            $table->dropColumn(['hr_approved_at', 'payroll_approved_at']);
        });
        Schema::table('employee_salary_advances', fn (Blueprint $table) => $table->dropColumn('recovery_frequency'));
    }
};
