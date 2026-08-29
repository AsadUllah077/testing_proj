<?php

use App\Models\ChartOfAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_salary_advances', function (Blueprint $table) {
            $table->string('payment_reference')->nullable()->after('status')->index();
            $table->foreignId('bank_account_id')->nullable()->after('payment_reference')->constrained('chart_of_accounts')->nullOnDelete();
            $table->timestamp('issued_at')->nullable()->after('bank_account_id');
            $table->foreignId('issued_by')->nullable()->after('issued_at')->constrained('users')->nullOnDelete();
            $table->foreignId('recovered_pay_run_id')->nullable()->after('issued_by')->constrained('pay_runs')->nullOnDelete();
            $table->timestamp('recovered_at')->nullable()->after('recovered_pay_run_id');
        });

        $now = now();
        $account = ChartOfAccount::withTrashed()
            ->where('system_account_type', 'EMPLOYEE_ADVANCES')
            ->orWhereRaw('LOWER(name) = ?', ['employee advances'])
            ->first();

        if ($account) {
            if (method_exists($account, 'restore') && $account->trashed()) {
                $account->restore();
            }

            $account->update([
                'name' => 'Employee Advances',
                'category' => 'Assets',
                'system_account_type' => 'EMPLOYEE_ADVANCES',
                'sub_type' => 'CURRENT_ASSET',
                'type' => 'Account',
                'tax_code' => $account->tax_code ?: 'Tax Exempt (0%)',
                'description' => $account->description ?: 'Salary advances issued to employees and recovered through payroll.',
                'is_system' => true,
            ]);

            return;
        }

        DB::table('chart_of_accounts')->insert([
            'account_number' => 625,
            'name' => 'Employee Advances',
            'parent_id' => null,
            'category' => 'Assets',
            'system_account_type' => 'EMPLOYEE_ADVANCES',
            'sub_type' => 'CURRENT_ASSET',
            'type' => 'Account',
            'tax_code' => 'Tax Exempt (0%)',
            'description' => 'Salary advances issued to employees and recovered through payroll.',
            'initial_balance' => 0,
            'opening_date' => $now,
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::table('employee_salary_advances', function (Blueprint $table) {
            $table->dropColumn('recovered_at');
            $table->dropConstrainedForeignId('recovered_pay_run_id');
            $table->dropConstrainedForeignId('issued_by');
            $table->dropColumn('issued_at');
            $table->dropConstrainedForeignId('bank_account_id');
            $table->dropColumn('payment_reference');
        });

        DB::table('chart_of_accounts')
            ->where('system_account_type', 'EMPLOYEE_ADVANCES')
            ->where('account_number', 625)
            ->where('name', 'Employee Advances')
            ->delete();
    }
};
