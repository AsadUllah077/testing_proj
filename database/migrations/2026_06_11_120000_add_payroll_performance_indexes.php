<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_pay_structures', function (Blueprint $table) {
            $table->index(['employee_id', 'company_id', 'deleted_at'], 'eps_employee_company_deleted_idx');
        });

        Schema::table('pay_items', function (Blueprint $table) {
            $table->index(['company_id', 'type', 'deleted_at'], 'pay_items_company_type_deleted_idx');
        });

        Schema::table('pay_runs', function (Blueprint $table) {
            $table->index(['company_id', 'period_end', 'status', 'deleted_at'], 'pay_runs_company_period_status_idx');
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->index(['employee_id', 'status', 'pay_run_id', 'deleted_at'], 'payslips_employee_status_run_idx');
            $table->index(['pay_run_id', 'employee_id', 'status'], 'payslips_run_employee_status_idx');
        });

        Schema::table('payslip_items', function (Blueprint $table) {
            $table->index(['payslip_id', 'pay_item_id'], 'payslip_items_slip_item_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payslip_items', function (Blueprint $table) {
            $table->dropIndex('payslip_items_slip_item_idx');
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->dropIndex('payslips_run_employee_status_idx');
            $table->dropIndex('payslips_employee_status_run_idx');
        });

        Schema::table('pay_runs', function (Blueprint $table) {
            $table->dropIndex('pay_runs_company_period_status_idx');
        });

        Schema::table('pay_items', function (Blueprint $table) {
            $table->dropIndex('pay_items_company_type_deleted_idx');
        });

        Schema::table('employee_pay_structures', function (Blueprint $table) {
            $table->dropIndex('eps_employee_company_deleted_idx');
        });
    }
};
