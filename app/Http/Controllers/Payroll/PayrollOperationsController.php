<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\CompensationReview;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeBankChangeRequest;
use App\Models\EmployeePayStructure;
use App\Models\PayItem;
use App\Models\PayRun;
use App\Models\PayrollCostAllocation;
use App\Models\PayrollFinalSettlement;
use App\Models\PayrollScenario;
use App\Models\PayrollVarianceComment;
use App\Models\Transaction;
use App\Services\Payroll\PayrollFinalSettlementService;
use App\Services\Payroll\PayrollPreflightService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PayrollOperationsController extends Controller
{
    public function __construct(
        private PayrollFinalSettlementService $settlements,
        private PayrollPreflightService $preflight
    ) {
    }

    public function index(Request $request)
    {
        $companyId = $this->companyId();
        $tab = $request->input('tab', 'settlements');
        $employees = Employee::withoutGlobalScopes()->where('company_id', $companyId)->orderBy('full_name')->get();
        $accounts = ChartOfAccount::forCompany($companyId)->orderBy('account_number')->get();
        $departments = Department::withoutGlobalScopes()->where('company_id', $companyId)->orderBy('department')->get();
        $finalSettlements = PayrollFinalSettlement::withoutGlobalScopes()->where('company_id', $companyId)->with('employee')->latest()->limit(50)->get();
        $allocations = PayrollCostAllocation::withoutGlobalScopes()->where('company_id', $companyId)->with(['employee', 'department'])->orderByDesc('effective_from')->get();
        $scenarios = PayrollScenario::withoutGlobalScopes()->where('company_id', $companyId)->latest()->get();
        $reviews = CompensationReview::withoutGlobalScopes()->where('company_id', $companyId)->with('employee')->latest()->get();
        $bankRequests = EmployeeBankChangeRequest::withoutGlobalScopes()->where('company_id', $companyId)->with('employee')->latest()->get();
        $payRuns = PayRun::withoutGlobalScopes()->where('company_id', $companyId)->whereIn('status', ['posted', 'paid'])->latest('period_end')->limit(24)->get();
        $variance = $request->filled('pay_run_id') ? $this->varianceFor((int) $request->pay_run_id) : collect();
        $allocationReport = $tab === 'allocations' && $request->filled('pay_run_id')
            ? $this->allocationReportFor((int) $request->pay_run_id) : collect();

        return view('pages.payroll.operations.index', compact(
            'tab', 'employees', 'accounts', 'departments', 'finalSettlements', 'allocations',
            'scenarios', 'reviews', 'bankRequests', 'payRuns', 'variance', 'allocationReport'
        ));
    }

    public function storeSettlement(Request $request)
    {
        $companyId = $this->companyId();
        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'settlement_date' => ['required', 'date'],
            'last_working_date' => ['required', 'date', 'before_or_equal:settlement_date'],
            'description' => ['required', 'array', 'min:1'],
            'description.*' => ['required', 'string', 'max:255'],
            'type' => ['required', 'array'],
            'type.*' => ['required', Rule::in(['earning', 'deduction'])],
            'account_id' => ['required', 'array'],
            'account_id.*' => ['required', Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId)],
            'amount' => ['required', 'array'],
            'amount.*' => ['required', 'numeric', 'min:0.01'],
        ]);

        $lines = collect($data['description'])->map(fn ($description, $index) => [
            'description' => $description,
            'type' => $data['type'][$index],
            'account_id' => (int) $data['account_id'][$index],
            'amount' => round((float) $data['amount'][$index], 2),
        ])->values();
        $lineAccounts = ChartOfAccount::forCompany($companyId)
            ->whereIn('id', $lines->pluck('account_id'))->get()->keyBy('id');
        foreach ($lines as $index => $line) {
            $account = $lineAccounts->get($line['account_id']);
            $allowedCategories = $line['type'] === 'earning' ? ['Expenses', 'COGS'] : ['Liabilities', 'Assets'];
            if (!$account || !in_array($account->category, $allowedCategories, true)) {
                throw ValidationException::withMessages([
                    "account_id.{$index}" => ucfirst($line['type']) . ' lines must use ' . implode(' or ', $allowedCategories) . ' accounts.',
                ]);
            }
        }
        $gross = $lines->where('type', 'earning')->sum('amount');
        $deductions = $lines->where('type', 'deduction')->sum('amount');

        PayrollFinalSettlement::create([
            'company_id' => $companyId,
            'employee_id' => $data['employee_id'],
            'settlement_date' => $data['settlement_date'],
            'last_working_date' => $data['last_working_date'],
            'lines' => $lines->all(),
            'gross_amount' => $gross,
            'deduction_amount' => $deductions,
            'net_amount' => $gross - $deductions,
            'status' => 'draft',
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Final settlement draft created.');
    }

    public function approveSettlement(Request $request, PayrollFinalSettlement $settlement)
    {
        $settlement = $this->companyModel($settlement);
        if ((int) $settlement->created_by === (int) Auth::id()) {
            return back()->with('error', 'The settlement creator cannot approve it.');
        }

        $stage = $request->validate(['stage' => ['required', Rule::in(['hr', 'payroll', 'finance'])]])['stage'];
        if ($stage === 'hr') {
            abort_unless($settlement->status === 'draft', 422, 'HR review must be first.');
            $settlement->update(['status' => 'hr_reviewed', 'hr_approved_by' => Auth::id(), 'hr_approved_at' => now()]);
        } elseif ($stage === 'payroll') {
            abort_unless($settlement->status === 'hr_reviewed' && (int) $settlement->hr_approved_by !== (int) Auth::id(), 422, 'Payroll review requires prior HR approval by another user.');
            $settlement->update(['status' => 'payroll_reviewed', 'payroll_approved_by' => Auth::id(), 'payroll_approved_at' => now()]);
        } else {
            abort_unless($settlement->status === 'payroll_reviewed' && !in_array((int) Auth::id(), [(int) $settlement->hr_approved_by, (int) $settlement->payroll_approved_by], true), 422, 'Finance approval requires prior HR and payroll reviews by different users.');
            $settlement->update(['status' => 'approved', 'approved_by' => Auth::id(), 'approved_at' => now()]);
        }
        return back()->with('success', ucfirst($stage) . ' settlement approval recorded.');
    }

    public function postSettlement(PayrollFinalSettlement $settlement)
    {
        $settlement = $this->companyModel($settlement);
        try {
            $this->settlements->post($settlement);
            return back()->with('success', 'Final settlement posted to accounting.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function storeAllocations(Request $request)
    {
        $companyId = $this->companyId();
        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'department_id' => ['required', 'array', 'min:1'],
            'department_id.*' => ['required', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'percentage' => ['required', 'array'],
            'percentage.*' => ['required', 'numeric', 'gt:0', 'lte:100'],
            'cost_center' => ['nullable', 'array'],
            'cost_center.*' => ['nullable', 'string', 'max:100'],
            'branch' => ['nullable', 'array'], 'branch.*' => ['nullable', 'string', 'max:100'],
            'project_code' => ['nullable', 'array'], 'project_code.*' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'array'], 'location.*' => ['nullable', 'string', 'max:100'],
            'funding_source' => ['nullable', 'array'], 'funding_source.*' => ['nullable', 'string', 'max:100'],
        ]);
        abort_if(abs(array_sum($data['percentage']) - 100) > 0.01, 422, 'Cost allocations must total exactly 100%.');

        DB::transaction(function () use ($data, $companyId) {
            foreach ($data['department_id'] as $index => $departmentId) {
                PayrollCostAllocation::create([
                    'company_id' => $companyId,
                    'employee_id' => $data['employee_id'],
                    'department_id' => $departmentId,
                    'cost_center' => $data['cost_center'][$index] ?? null,
                    'branch' => $data['branch'][$index] ?? null,
                    'project_code' => $data['project_code'][$index] ?? null,
                    'location' => $data['location'][$index] ?? null,
                    'funding_source' => $data['funding_source'][$index] ?? null,
                    'percentage' => $data['percentage'][$index],
                    'effective_from' => $data['effective_from'],
                    'effective_to' => $data['effective_to'] ?? null,
                ]);
            }
        });
        return back()->with('success', 'Cost allocation saved at 100%.');
    }

    public function storeScenario(Request $request)
    {
        $companyId = $this->companyId();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'salary_increase_percentage' => ['required', 'numeric', 'min:-100', 'max:500'],
            'new_hires' => ['required', 'integer', 'min:0', 'max:10000'],
            'average_new_hire_salary' => ['required', 'numeric', 'min:0'],
            'bonus_pool' => ['required', 'numeric', 'min:0'],
            'turnover_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'overtime_increase_percentage' => ['required', 'numeric', 'min:-100', 'max:500'],
            'allowance_change_percentage' => ['required', 'numeric', 'min:-100', 'max:500'],
            'tax_change_percentage' => ['required', 'numeric', 'min:-100', 'max:500'],
            'employer_cost_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'annual_budget' => ['required', 'numeric', 'min:0'],
        ]);
        $employees = Employee::withoutGlobalScopes()->where('company_id', $companyId)->where('status', 'active')->get();
        $baselineMonthly = (float) $employees->sum('salary');
        $retained = 1 - ((float) $data['turnover_percentage'] / 100);
        $latestRun = PayRun::withoutGlobalScopes()->where('company_id', $companyId)->whereIn('status', ['posted', 'paid'])->latest('period_end')->with('payslips.items.payItem')->first();
        $baselineTax = $latestRun
            ? (float) $latestRun->payslips->flatMap->items->filter(fn ($item) => $item->payItem?->type === 'deduction' && str_contains(strtolower((string) $item->payItem?->name), 'income tax'))->sum('amount')
            : 0;
        $baseAfterTurnover = $baselineMonthly * $retained;
        $projectedMonthly = ($baseAfterTurnover * (1 + ((float) $data['salary_increase_percentage'] / 100)))
            + ($baseAfterTurnover * ((float) $data['overtime_increase_percentage'] / 100))
            + ($baseAfterTurnover * ((float) $data['allowance_change_percentage'] / 100))
            + ((int) $data['new_hires'] * (float) $data['average_new_hire_salary']);
        $projectedMonthlyTax = $baselineMonthly > 0
            ? $baselineTax * ($projectedMonthly / $baselineMonthly) * (1 + ((float) $data['tax_change_percentage'] / 100))
            : 0;
        $annualGross = ($projectedMonthly * 12) + (float) $data['bonus_pool'];
        $employerCost = $annualGross * ((float) $data['employer_cost_percentage'] / 100);
        $results = [
            'baseline_monthly' => round($baselineMonthly, 2),
            'projected_monthly' => round($projectedMonthly, 2),
            'baseline_annual' => round($baselineMonthly * 12, 2),
            'projected_annual' => round($annualGross, 2),
            'annual_difference' => round((($projectedMonthly - $baselineMonthly) * 12) + (float) $data['bonus_pool'], 2),
            'projected_headcount' => max(0, round($employees->count() * $retained) + (int) $data['new_hires']),
            'projected_annual_tax' => round($projectedMonthlyTax * 12, 2),
            'projected_annual_net' => round($annualGross - ($projectedMonthlyTax * 12), 2),
            'employer_cost' => round($employerCost, 2),
            'total_employer_budget' => round($annualGross + $employerCost, 2),
            'budget_variance' => round((float) $data['annual_budget'] - ($annualGross + $employerCost), 2),
        ];
        PayrollScenario::create([
            'company_id' => $companyId, 'name' => $data['name'], 'description' => $data['description'] ?? null,
            'assumptions' => collect($data)->except(['name', 'description'])->all(), 'results' => $results, 'created_by' => Auth::id(),
        ]);
        return back()->with('success', 'Payroll scenario calculated and saved.');
    }

    public function storeReview(Request $request)
    {
        $companyId = $this->companyId();
        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'proposed_salary' => ['required', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
            'market_benchmark' => ['nullable', 'numeric', 'min:0'],
            'manager_recommendation' => ['required', 'string', 'max:2000'],
            'hr_recommendation' => ['required', 'string', 'max:2000'],
        ]);
        $employee = Employee::withoutGlobalScopes()->where('company_id', $companyId)->findOrFail($data['employee_id']);
        CompensationReview::create($data + [
            'company_id' => $companyId, 'current_salary' => (float) $employee->salary,
            'annual_budget_impact' => ((float) $data['proposed_salary'] - (float) $employee->salary) * 12,
            'status' => 'draft', 'created_by' => Auth::id(),
        ]);
        return back()->with('success', 'Compensation review created.');
    }

    public function approveReview(Request $request, CompensationReview $review)
    {
        $review = $this->companyModel($review);
        if ((int) $review->created_by === (int) Auth::id()) {
            return back()->with('error', 'The review creator cannot approve it.');
        }

        $data = $request->validate(['approved_salary' => ['required', 'numeric', 'min:0']]);
        $review->update(['approved_salary' => $data['approved_salary'], 'status' => 'approved', 'approved_by' => Auth::id(), 'approved_at' => now()]);
        return back()->with('success', 'Compensation review approved.');
    }

    public function applyReview(CompensationReview $review)
    {
        $review = $this->companyModel($review);
        abort_unless($review->status === 'approved' && $review->effective_date->lte(today()), 422, 'The review must be approved and effective before applying.');
        DB::transaction(function () use ($review) {
            $review->employee()->update(['salary' => $review->approved_salary]);
            $basicPayItem = PayItem::withoutGlobalScopes()->where('company_id', $review->company_id)->where('type', 'earning')->whereRaw('LOWER(name) LIKE ?', ['%basic%'])->first();
            if ($basicPayItem) {
                EmployeePayStructure::withoutGlobalScopes()->where('company_id', $review->company_id)
                    ->where('employee_id', $review->employee_id)->where('pay_item_id', $basicPayItem->id)
                    ->update(['amount' => $review->approved_salary]);
            }
            $review->update(['status' => 'applied', 'applied_at' => now()]);
        });
        return back()->with('success', 'Approved compensation applied to employee pay.');
    }

    public function incrementLetter(CompensationReview $review)
    {
        $review = $this->companyModel($review);
        abort_unless(in_array($review->status, ['approved', 'applied'], true), 422, 'Approve the compensation review first.');
        $review->load('employee.company');
        return Pdf::loadView('pages.payroll.operations.increment-letter', compact('review'))
            ->setPaper('a4', 'portrait')->download('Increment_Letter_' . $review->employee_id . '_' . $review->effective_date->format('Ymd') . '.pdf');
    }

    public function storeVarianceComment(Request $request, PayRun $payRun)
    {
        $payRun = $this->companyModel($payRun);
        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $this->companyId())],
            'comment' => ['required', 'string', 'min:5', 'max:2000'],
        ]);
        PayrollVarianceComment::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $payRun->company_id, 'pay_run_id' => $payRun->id, 'employee_id' => $data['employee_id']],
            ['comment' => $data['comment'], 'created_by' => Auth::id()]
        );
        return back()->with('success', 'Variance explanation saved.');
    }

    public function reviewBankRequest(Request $request, EmployeeBankChangeRequest $bankRequest)
    {
        $bankRequest = $this->companyModel($bankRequest);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])], 'review_notes' => ['nullable', 'string', 'max:1000']]);
        DB::transaction(function () use ($bankRequest, $data) {
            if ($data['status'] === 'approved') {
                $bankRequest->employee()->update([
                    'bank_name' => $bankRequest->bank_name,
                    'bank_account_number' => $bankRequest->bank_account_number,
                    'bank_iban' => $bankRequest->bank_iban,
                ]);
            }
            $bankRequest->update($data + ['reviewed_by' => Auth::id(), 'reviewed_at' => now()]);
        });
        return back()->with('success', 'Bank change request reviewed.');
    }

    public function auditPack(PayRun $payRun)
    {
        $payRun = $this->companyModel($payRun);
        $payRun->load(['company', 'createdBy', 'approvedBy', 'payslips.employee', 'payslips.items.payItem']);
        $preflight = $this->preflight->run($payRun);
        $comments = PayrollVarianceComment::withoutGlobalScopes()->where('pay_run_id', $payRun->id)->with('employee')->get();
        $transactions = Transaction::withoutGlobalScopes()->where('company_id', $payRun->company_id)->where('reference_id', $payRun->id)->with('account')->get();
        $liabilities = $payRun->payslips->flatMap->items
            ->filter(fn ($item) => $item->payItem?->type === 'deduction')
            ->groupBy(fn ($item) => $item->payItem?->name ?: 'Unknown')
            ->map(fn ($items, $name) => ['name' => $name, 'amount' => (float) $items->sum('amount'), 'paid' => (float) $items->whereNotNull('paid_at')->sum('amount')]);
        $advances = \App\Models\EmployeeSalaryAdvance::where('company_id', $payRun->company_id)
            ->whereIn('employee_id', $payRun->payslips->pluck('employee_id'))->where('remaining_amount', '>', 0)->with('employee')->get();
        return Pdf::loadView('pages.payroll.operations.audit-pack', compact('payRun', 'preflight', 'comments', 'transactions', 'liabilities', 'advances'))
            ->setPaper('a4', 'portrait')->download('Payroll_Audit_Pack_' . ($payRun->unique_no ?: $payRun->id) . '.pdf');
    }

    private function varianceFor(int $payRunId)
    {
        $current = PayRun::withoutGlobalScopes()->where('company_id', $this->companyId())->with(['payslips.employee', 'payslips.items.payItem'])->findOrFail($payRunId);
        $previous = PayRun::withoutGlobalScopes()->where('company_id', $this->companyId())->whereIn('status', ['posted', 'paid'])
            ->where('period_end', '<', $current->period_start)->latest('period_end')->with('payslips.items.payItem')->first();
        $previousByEmployee = $previous?->payslips->keyBy('employee_id') ?? collect();
        $comments = PayrollVarianceComment::withoutGlobalScopes()->where('pay_run_id', $current->id)->get()->keyBy('employee_id');
        return $current->payslips->map(function ($slip) use ($previousByEmployee, $comments) {
            $old = $previousByEmployee->get($slip->employee_id);
            $currentItems = $slip->items->groupBy('pay_item_id')->map(fn ($items) => ['name' => $items->first()->payItem?->name ?: 'Unknown', 'type' => $items->first()->payItem?->type, 'amount' => (float) $items->sum('amount')]);
            $previousItems = $old?->items?->groupBy('pay_item_id')->map(fn ($items) => ['name' => $items->first()->payItem?->name ?: 'Unknown', 'type' => $items->first()->payItem?->type, 'amount' => (float) $items->sum('amount')]) ?? collect();
            $reasons = $currentItems->keys()->merge($previousItems->keys())->unique()->map(function ($itemId) use ($currentItems, $previousItems) {
                $currentItem = $currentItems->get($itemId);
                $previousItem = $previousItems->get($itemId);
                $difference = (float) ($currentItem['amount'] ?? 0) - (float) ($previousItem['amount'] ?? 0);
                return ['name' => $currentItem['name'] ?? $previousItem['name'], 'type' => $currentItem['type'] ?? $previousItem['type'], 'difference' => $difference];
            })->filter(fn ($reason) => abs($reason['difference']) > 0.01)->values();
            return [
                'employee' => $slip->employee, 'gross' => (float) $slip->gross_earnings, 'net' => (float) $slip->net_pay,
                'previous_gross' => (float) ($old?->gross_earnings ?? 0), 'previous_net' => (float) ($old?->net_pay ?? 0),
                'gross_variance' => (float) $slip->gross_earnings - (float) ($old?->gross_earnings ?? 0),
                'net_variance' => (float) $slip->net_pay - (float) ($old?->net_pay ?? 0),
                'comment' => $comments->get($slip->employee_id)?->comment,
                'reasons' => $reasons,
            ];
        });
    }

    private function allocationReportFor(int $payRunId)
    {
        $payRun = PayRun::withoutGlobalScopes()->where('company_id', $this->companyId())->with('payslips.employee')->findOrFail($payRunId);
        $allocations = PayrollCostAllocation::withoutGlobalScopes()->where('company_id', $this->companyId())
            ->whereDate('effective_from', '<=', $payRun->period_end)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $payRun->period_end))
            ->with('department')->get()->groupBy('employee_id');
        $rows = collect();
        foreach ($payRun->payslips as $payslip) {
            $employeeAllocations = $allocations->get($payslip->employee_id, collect());
            foreach ($employeeAllocations as $allocation) {
                $key = $allocation->department_id . '|' . ($allocation->cost_center ?: 'Unassigned');
                $existing = $rows->get($key, ['department' => $allocation->department?->department ?: 'Unassigned', 'cost_center' => $allocation->cost_center ?: '-', 'gross' => 0.0, 'net' => 0.0]);
                $factor = (float) $allocation->percentage / 100;
                $existing['gross'] += (float) $payslip->gross_earnings * $factor;
                $existing['net'] += (float) $payslip->net_pay * $factor;
                $rows->put($key, $existing);
            }
        }
        return $rows->values();
    }

    private function companyId(): int { return (int) Auth::user()->company_id; }

    private function companyModel($model)
    {
        abort_unless((int) $model->company_id === $this->companyId(), 404);
        return $model;
    }
}
