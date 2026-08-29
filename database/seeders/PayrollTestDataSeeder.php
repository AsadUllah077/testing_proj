<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\ChartOfAccount;
use App\Models\PayItem;
use App\Models\EmployeePayStructure;
use App\Models\User;
use App\Models\AccountingPeriod;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Faker\Factory as Faker;

class PayrollTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $user = Auth::user();

        if (!$user) {

            // 1️⃣ Try specific user first
            $user = User::where('username', 'shayan.ahmad')->first();

            // 2️⃣ Fallback to any user with company
            if (!$user) {
                $user = User::whereNotNull('company_id')->first();
            }

            // 3️⃣ If still no user → abort
            if (!$user) {
                $this->command->error('No user with company found. Seeder aborted.');
                return;
            }

            // 4️⃣ LOGIN ONCE (important)
            Auth::login($user);
        }

        $companyId = $user->company_id;

        // 0. Ensure Accounting Periods exist
        $fy = $this->pakistanFiscalYearRange(now());
        $this->ensureAccountingPeriod($companyId, $fy['start'], $fy['end']);

        // 1. Ensure suitable accounts exist
        $salaryExpense = ChartOfAccount::updateOrCreate(
            ['account_number' => '661'],
            ['name' => 'Salaries and Wages', 'category' => 'Expenses', 'type' => 'Account', 'is_system' => true]
        );
        $wagesPayable = ChartOfAccount::updateOrCreate(
            ['account_number' => '233'],
            ['name' => 'Accrued Salaries', 'category' => 'Liabilities', 'type' => 'Account', 'is_system' => true]
        );
        $taxPayable = ChartOfAccount::updateOrCreate(
            ['account_number' => '242'],
            ['name' => 'Income Tax Payable', 'category' => 'Liabilities', 'type' => 'Account', 'is_system' => true]
        );
        $pensionPayable = ChartOfAccount::updateOrCreate(
            ['account_number' => '244'],
            ['name' => 'Pension Fund Payable', 'category' => 'Liabilities', 'type' => 'Account', 'is_system' => true]
        );

        // 2. Create Pay Items
        $basicSalary = PayItem::updateOrCreate(
            ['name' => 'Basic Salary', 'company_id' => $companyId],
            [
                'type' => 'earning',
                'account_id' => $salaryExpense->id,
                'calculation_type' => 'fixed',
            ]
        );

        $housing = PayItem::updateOrCreate(
            ['name' => 'Housing Allowance', 'company_id' => $companyId],
            [
                'type' => 'earning',
                'account_id' => $salaryExpense->id,
                'calculation_type' => 'fixed',
            ]
        );

        $tax = PayItem::updateOrCreate(
            ['name' => 'Income Tax', 'company_id' => $companyId],
            [
                'type' => 'deduction',
                'account_id' => $taxPayable->id,
                'calculation_type' => 'percentage',
                'percentage_value' => 10,
                'base_item_id' => $basicSalary->id,
            ]
        );

        $pension = PayItem::updateOrCreate(
            ['name' => 'Pension Contribution', 'company_id' => $companyId],
            [
                'type' => 'deduction',
                'account_id' => $pensionPayable->id,
                'calculation_type' => 'percentage',
                'percentage_value' => 5,
                'base_item_id' => $basicSalary->id,
            ]
        );

        // 3. Create Employees and Structure
        $employeesData = [
            ['name' => 'John Doe', 'salary' => 5000, 'housing' => 1000],
            ['name' => 'Jane Smith', 'salary' => 4500, 'housing' => 900],
            ['name' => 'Alice Johnson', 'salary' => 6000, 'housing' => 1200],
        ];

        foreach ($employeesData as $data) {
            $employee = Employee::updateOrCreate(
                ['full_name' => $data['name'], 'company_id' => $companyId],
                [
                    'email' => strtolower(str_replace(' ', '.', $data['name'])) . '@example.com',
                    'status' => 'active',
                    'designation' => $faker->jobTitle,
                    'date_of_joining' => now()->subYear(),
                ]
            );

            // Clear existing structure to avoid duplicates in test
            EmployeePayStructure::where('company_id', $companyId)->where('employee_id', $employee->id)->delete();

            // Structure
            EmployeePayStructure::create([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'pay_item_id' => $basicSalary->id,
                'amount' => $data['salary'],
                'sort_order' => 1,
            ]);

            EmployeePayStructure::create([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'pay_item_id' => $housing->id,
                'amount' => $data['housing'],
                'sort_order' => 2,
            ]);

            EmployeePayStructure::create([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'pay_item_id' => $tax->id,
                'amount' => 0, // calculated
                'sort_order' => 3,
            ]);

            EmployeePayStructure::create([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'pay_item_id' => $pension->id,
                'amount' => 0, // calculated
                'sort_order' => 4,
            ]);
        }

        // 4. Generate Pay Runs using PayrollService
        $payrollService = new PayrollService();
        $employeeIds = Employee::where('company_id', $companyId)->pluck('id')->toArray();

        // Run 1: Last Month (Posted)
        $lastMonthStart = now()->subMonth()->startOfMonth()->format('Y-m-d');
        $lastMonthEnd = now()->subMonth()->endOfMonth()->format('Y-m-d');
        $lastMonthPaymentDate = now()->subMonth()->endOfMonth()->format('Y-m-d');

        $payRun1 = $payrollService->generatePayRun(
            $companyId,
            $lastMonthStart,
            $lastMonthEnd,
            $lastMonthPaymentDate,
            $employeeIds,
            'monthly'
        );

        // Approve and post the first run to test accounting integration
        $payRun1->update(['approval_status' => 'approved']);
        $payrollService->postToAccounting($payRun1->id, $companyId);

        // Run 2: This Month (Draft)
        $thisMonthStart = now()->startOfMonth()->format('Y-m-d');
        $thisMonthEnd = now()->endOfMonth()->format('Y-m-d');
        $thisMonthPaymentDate = now()->endOfMonth()->format('Y-m-d');

        $payrollService->generatePayRun(
            $companyId,
            $thisMonthStart,
            $thisMonthEnd,
            $thisMonthPaymentDate,
            $employeeIds,
            'monthly'
        );

        $this->command->info('✅ Payroll Test Data & Pay Runs Seeded Successfully!');
    }

    private function ensureAccountingPeriod(int $companyId, Carbon $start, Carbon $end): AccountingPeriod
    {
        // 1️⃣ Check for active & unlocked period that covers the required date range
        $activePeriod = AccountingPeriod::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_locked', false)
            ->where('start_date', '<=', $start->format('Y-m-d'))
            ->where('end_date', '>=', $end->format('Y-m-d'))
            ->first();

        if ($activePeriod) {
            return $activePeriod; // ✅ Do nothing
        }

        // 2️⃣ Check if same period already exists (locked or not)
        $existing = AccountingPeriod::where('company_id', $companyId)
            ->whereDate('start_date', $start->format('Y-m-d'))
            ->whereDate('end_date', $end->format('Y-m-d'))
            ->first();

        if ($existing) {
            // Unlock and activate it if needed
            $existing->update(['is_active' => true, 'is_locked' => false]);
            return $existing;
        }

        // 3️⃣ Create new period (previous is locked or missing)
        return AccountingPeriod::create([
            'company_id' => $companyId,
            'name' => "FY {$start->year}-{$end->year}",
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'is_active' => true,
            'is_locked' => false,
            'created_by' => Auth::id(),
        ]);
    }


    private function pakistanFiscalYearRange(?Carbon $date = null): array
    {
        $date = ($date ?? now())->copy();

        // FY starts on July 1
        $fyStartYear = $date->month >= 7 ? $date->year : $date->year - 1;

        $start = Carbon::create($fyStartYear, 7, 1, 0, 0, 0, $date->timezone)->startOfDay();
        $end   = $start->copy()->addYear()->subDay()->endOfDay(); // June 30

        return compact('start', 'end');
    }
}
