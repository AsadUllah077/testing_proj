<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Employee;
use App\Models\EmployeeSalaryAdvance;
use App\Services\Payroll\SalaryAdvanceAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SalaryAdvanceController extends Controller
{
    public function __construct(private SalaryAdvanceAccountingService $accountingService)
    {
    }

    public function index(Request $request)
    {
        // DB::enableQueryLog();

        $searchLower = strtolower($request->search);
        $advances = EmployeeSalaryAdvance::with(['employee', 'approvedBy'])
            ->where('company_id', Auth::user()->company_id)
            ->when($request->search, function ($q) use ($searchLower) {
                $q->where(function ($searchQuery) use ($searchLower) {
                    $searchQuery->whereHas('employee', fn($e) => $e->whereRaw('LOWER(full_name) LIKE ?', ["%{$searchLower}%"]))
                        ->orWhereRaw('LOWER(reason) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(payment_reference) LIKE ?', ["%{$searchLower}%"]);
                });
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->sortable()
            ->paginate($request->per_page ?? 20);

        $employees = Employee::where('company_id', Auth::user()->company_id)
            ->where('status', 'active')
            ->get(['id', 'full_name', 'employee_id']);

        $bankAccounts = ChartOfAccount::forCompany(Auth::user()->company_id)
            ->where('type', 'Account')
            ->where('sub_type', 'BANK')
            ->orderBy('account_number')
            ->get(['id', 'account_number', 'name']);

        $data = [
            'advances' => $advances,
            'employees' => $employees,
            'bankAccounts' => $bankAccounts,
        ];

        // dd(DB::getQueryLog());

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.payroll.partials.salary-advance-table', $data)->render(),
                'pagination' => $advances->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.payroll.salary-advances.index', $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', Auth::user()->company_id),
            ],
            'amount' => 'required|numeric|min:0.01',
            'advance_date' => 'required|date',
            'reason' => 'nullable|string',
            'recovery_method' => 'required|in:fixed,percentage',
            'recovery_frequency' => 'required|in:each_pay_run,monthly',
            'installment_amount' => 'nullable|required_if:recovery_method,fixed|numeric|min:0.01|lte:amount',
            'recovery_percentage' => 'nullable|required_if:recovery_method,percentage|numeric|min:0.01|max:100',
            'max_net_deduction_percentage' => 'required|numeric|min:1|max:100',
            'recovery_start_date' => 'required|date|after_or_equal:advance_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->all()]);
        }

        EmployeeSalaryAdvance::create([
            'company_id' => Auth::user()->company_id,
            'employee_id' => $request->employee_id,
            'amount' => $request->amount,
            'remaining_amount' => $request->amount,
            'recovery_method' => $request->recovery_method,
            'recovery_frequency' => $request->recovery_frequency,
            'installment_amount' => $request->installment_amount,
            'recovery_percentage' => $request->recovery_percentage,
            'max_net_deduction_percentage' => $request->max_net_deduction_percentage,
            'recovery_start_date' => $request->recovery_start_date,
            'installments_total' => $request->recovery_method === 'fixed'
                ? (int) ceil($request->amount / $request->installment_amount)
                : null,
            'advance_date' => $request->advance_date,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return response()->json(['success' => true, 'message' => 'Salary advance created successfully']);
    }

    public function update(Request $request, $id)
    {
        $advance = EmployeeSalaryAdvance::where('company_id', Auth::user()->company_id)->findOrFail($id);

        if ($advance->payment_reference) {
            return response()->json(['success' => false, 'message' => 'Issued salary advances cannot be edited.']);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', Auth::user()->company_id),
            ],
            'amount' => 'required|numeric|min:0.01',
            'advance_date' => 'required|date',
            'reason' => 'nullable|string',
            'recovery_method' => 'required|in:fixed,percentage',
            'recovery_frequency' => 'required|in:each_pay_run,monthly',
            'installment_amount' => 'nullable|required_if:recovery_method,fixed|numeric|min:0.01|lte:amount',
            'recovery_percentage' => 'nullable|required_if:recovery_method,percentage|numeric|min:0.01|max:100',
            'max_net_deduction_percentage' => 'required|numeric|min:1|max:100',
            'recovery_start_date' => 'required|date|after_or_equal:advance_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->all()]);
        }

        $advance->update([
            'employee_id' => $request->employee_id,
            'amount' => $request->amount,
            'remaining_amount' => $request->amount,
            'recovery_method' => $request->recovery_method,
            'recovery_frequency' => $request->recovery_frequency,
            'installment_amount' => $request->installment_amount,
            'recovery_percentage' => $request->recovery_percentage,
            'max_net_deduction_percentage' => $request->max_net_deduction_percentage,
            'recovery_start_date' => $request->recovery_start_date,
            'installments_total' => $request->recovery_method === 'fixed'
                ? (int) ceil($request->amount / $request->installment_amount)
                : null,
            'advance_date' => $request->advance_date,
            'reason' => $request->reason,
        ]);

        return response()->json(['success' => true, 'message' => 'Salary advance updated successfully']);
    }

    public function approve($id)
    {
        $advance = EmployeeSalaryAdvance::where('company_id', Auth::user()->company_id)->findOrFail($id);
        $advance->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Salary advance approved successfully']);
    }

    public function issue(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'bank_account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')
                    ->where('company_id', Auth::user()->company_id)
                    ->where('type', 'Account')
                    ->where('sub_type', 'BANK'),
            ],
            'payment_date' => 'required|date',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->all()]);
        }

        try {
            $advance = EmployeeSalaryAdvance::with('employee')
                ->where('company_id', Auth::user()->company_id)
                ->findOrFail($id);

            $issuedAdvance = $this->accountingService->issueAdvance(
                $advance,
                (int) $request->bank_account_id,
                $request->payment_date,
                $request->description
            );

            return response()->json([
                'success' => true,
                'message' => "Salary advance issued successfully as {$issuedAdvance->payment_reference}.",
                'reference' => $issuedAdvance->payment_reference,
            ]);
        } catch (\Exception $e) {
            Log::error('Salary Advance Issue Error: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function reject($id)
    {
        $advance = EmployeeSalaryAdvance::where('company_id', Auth::user()->company_id)->findOrFail($id);

        if ($advance->payment_reference) {
            return response()->json(['success' => false, 'message' => 'Issued salary advances cannot be rejected.']);
        }

        $advance->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Salary advance rejected successfully']);
    }

    public function togglePause($id)
    {
        $advance = EmployeeSalaryAdvance::where('company_id', Auth::user()->company_id)->findOrFail($id);
        if (!$advance->payment_reference || (float) $advance->remaining_amount <= 0) {
            return response()->json(['success' => false, 'message' => 'Only an issued outstanding advance can be paused or resumed.'], 422);
        }
        $advance->update(['recovery_paused_at' => $advance->recovery_paused_at ? null : now()]);
        return response()->json(['success' => true, 'message' => $advance->recovery_paused_at ? 'Recovery paused.' : 'Recovery resumed.']);
    }

    public function reschedule(Request $request, $id)
    {
        $advance = EmployeeSalaryAdvance::where('company_id', Auth::user()->company_id)->findOrFail($id);
        $validator = Validator::make($request->all(), [
            'recovery_method' => 'required|in:fixed,percentage',
            'recovery_frequency' => 'required|in:each_pay_run,monthly',
            'installment_amount' => 'nullable|required_if:recovery_method,fixed|numeric|min:0.01|lte:remaining_amount',
            'recovery_percentage' => 'nullable|required_if:recovery_method,percentage|numeric|min:0.01|max:100',
            'max_net_deduction_percentage' => 'required|numeric|min:1|max:100',
            'recovery_start_date' => 'required|date',
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'message' => $validator->errors()->all()], 422);
        $advance->update([
            'recovery_method' => $request->recovery_method,
            'recovery_frequency' => $request->recovery_frequency,
            'installment_amount' => $request->installment_amount,
            'recovery_percentage' => $request->recovery_percentage,
            'max_net_deduction_percentage' => $request->max_net_deduction_percentage,
            'recovery_start_date' => $request->recovery_start_date,
            'installments_total' => $request->recovery_method === 'fixed'
                ? (int) $advance->installments_recovered + (int) ceil((float) $advance->remaining_amount / (float) $request->installment_amount)
                : null,
        ]);
        return response()->json(['success' => true, 'message' => 'Recovery schedule updated.']);
    }

    public function settleEarly(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'bank_account_id' => ['required', Rule::exists('chart_of_accounts', 'id')->where('company_id', Auth::user()->company_id)->where('type', 'Account')],
            'payment_date' => 'required|date',
            'description' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'message' => $validator->errors()->all()], 422);
        try {
            $advance = EmployeeSalaryAdvance::where('company_id', Auth::user()->company_id)->findOrFail($id);
            $this->accountingService->settleEarly($advance, (int) $request->bank_account_id, $request->payment_date, $request->description);
            return response()->json(['success' => true, 'message' => 'Advance settled and posted to accounting.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        $advance = EmployeeSalaryAdvance::where('company_id', Auth::user()->company_id)->findOrFail($id);

        if ($advance->payment_reference) {
            return response()->json(['success' => false, 'message' => 'Issued salary advances cannot be deleted.']);
        }

        $advance->delete();

        return response()->json(['success' => true, 'message' => 'Salary advance deleted successfully']);
    }
}
