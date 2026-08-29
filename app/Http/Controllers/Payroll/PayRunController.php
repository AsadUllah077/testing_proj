<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Employee;
use App\Models\PayItem;
use App\Models\PayRun;
use App\Models\Transaction;
use App\Services\PayrollService;
use App\Services\Payroll\PayrollAnomalyDetector;
use App\Services\Payroll\PayrollPreflightService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PayRunController extends Controller
{
    protected $payrollService;
    protected $anomalyDetector;
    protected $preflight;

    public function __construct(PayrollService $payrollService, PayrollAnomalyDetector $anomalyDetector, PayrollPreflightService $preflight)
    {
        $this->payrollService = $payrollService;
        $this->anomalyDetector = $anomalyDetector;
        $this->preflight = $preflight;
    }

    public function index(Request $request)
    {
        $payRuns = PayRun::with(['company', 'createdBy'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->approval_status, fn($q) => $q->where('approval_status', $request->approval_status))
            ->when($request->date_from, fn($q) => $q->whereDate('payment_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('payment_date', '<=', $request->date_to))
            ->sortable()
            ->paginate($request->per_page ?? 20);

        if ($request->ajax()) {
            return response()->json([
                'html'       => view('pages.payroll.partials.pay-run-table', compact('payRuns'))->render(),
                'pagination' => $payRuns->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.payroll.pay-run.index', compact('payRuns'));
    }

    public function create()
    {
        $employees = Employee::where('status', 'active')->whereNotNull('salary')->get();
        return view('pages.payroll.pay-run.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after:period_start',
            'payment_date' => 'required|date',
            'frequency'    => 'required|in:weekly,fortnightly,monthly',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => [
                'integer',
                Rule::exists('employees', 'id')->where('company_id', Auth::user()->company_id),
            ],
        ]);

        $overlapExists = PayRun::whereNotIn('status', ['cancelled'])
            ->whereDate('period_start', '<=', $request->period_end)
            ->whereDate('period_end', '>=', $request->period_start)
            ->whereHas('payslips', fn ($query) => $query->whereIn('employee_id', $request->employee_ids))
            ->exists();

        if ($overlapExists) {
            return response()->json([
                'success' => false,
                'message' => 'An active pay run already covers one or more selected employees in this date range. Use a supplementary payroll for corrections.',
            ], 422);
        }

        try {
            $overtimeHours = $request->input('overtime_hours', []);

            $payRun = $this->payrollService->generatePayRun(
                Auth::user()->company_id,
                $request->period_start,
                $request->period_end,
                $request->payment_date,
                $request->employee_ids ?? [],
                $request->frequency,
                $overtimeHours
            );

            return response()->json([
                'success'      => true,
                'message'      => 'Pay Run generated successfully',
                'redirect_url' => route('payroll.pay-runs.show', $payRun->id)
            ]);
        } catch (\Exception $e) {
            Log::error('PayRun Store Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error generating pay run: ' . $e->getMessage()]);
        }
    }

    public function show(Request $request, $id)
    {
        $payRun = PayRun::with([
            'approvedBy',
            'payslips.employee',
            'payslips.items.payItem.baseItem',
        ])->findOrFail($id);

        // Run anomaly detection on draft pay runs
        $anomalies = ['summary' => ['critical' => 0, 'warning' => 0], 'warnings' => []];
        if ($payRun->status === 'draft') {
            try {
                $anomalies = $this->anomalyDetector->analyze($payRun);
            } catch (\Exception $e) {
                Log::warning('Payroll anomaly detection failed: ' . $e->getMessage());
            }
        }

        $allPayslips = $payRun->payslips;
        $payslipQuery = $payRun->payslips()
            ->with(['employee', 'items.payItem.baseItem'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->search);

                $query->whereHas('employee', function ($employeeQuery) use ($search) {
                    $employeeQuery->where(function ($subQuery) use ($search) {
                        $subQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('employee_id', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            })
            ->when($request->filled('gross_min'), fn($query) => $query->where('gross_earnings', '>=', $request->gross_min))
            ->when($request->filled('gross_max'), fn($query) => $query->where('gross_earnings', '<=', $request->gross_max));

        $payslips = $payslipQuery
            ->paginate(50, ['*'], 'payslip_page')
            ->appends($request->except('payslip_page'));

        $currencySymbol = currency()?->symbol ?? '';
        $summary = [
            'gross' => $allPayslips->sum('gross_earnings'),
            'deductions' => $allPayslips->sum('total_deductions'),
            'net' => $allPayslips->sum('net_pay'),
            'employee_count' => $allPayslips->count(),
        ];

        $warningCounts = collect($anomalies['warnings'] ?? [])
            ->groupBy('employee_id')
            ->map(fn($warnings) => [
                'critical' => $warnings->where('severity', 'critical')->count(),
                'warning' => $warnings->where('severity', 'warning')->count(),
            ]);

        $payItems = $payRun->status === 'draft'
            ? PayItem::where('company_id', $payRun->company_id)->orderBy('name')->get()
            : collect();

        $bankAccounts = ChartOfAccount::forCompany($payRun->company_id)
            ->where('category', 'Assets')
            ->where('type', 'Account')
            ->orderBy('name')
            ->get();

        $deductionItems = $allPayslips
            ->flatMap->items
            ->pluck('payItem')
            ->filter(fn($payItem) => $payItem && $payItem->type === 'deduction')
            ->unique('id')
            ->values();

        return view('pages.payroll.pay-run.show', compact(
            'payRun',
            'anomalies',
            'summary',
            'warningCounts',
            'currencySymbol',
            'payItems',
            'bankAccounts',
            'deductionItems',
            'payslips'
        ));
    }

    public function viewTransaction(Request $request, $uniqueNo)
    {
        $payRun = PayRun::with(['company', 'createdBy', 'approvedBy'])
            ->where('unique_no', $uniqueNo)
            ->firstOrFail();

        $query = Transaction::with(['account:id,name,account_number', 'payable'])
            ->where('unique_no', $uniqueNo)
            ->orderBy('payable_type')
            ->orderBy('payable_id')
            ->orderBy('id');

        $this->applyTransactionFilters($query, $request);

        $perPage = in_array((int) $request->input('per_page', 50), [50, 100, 250], true)
            ? (int) $request->input('per_page', 50)
            : 50;

        $transactions = $query->paginate($perPage)->appends($request->all());
        $employees = $this->payRunEmployees([$uniqueNo]);
        $summary = $this->payRunTransactionSummary([$uniqueNo]);

        return view('pages.payroll.pay-run.view-transaction', compact('transactions', 'payRun', 'employees', 'summary'));
    }

    public function allTransactions(Request $request)
    {
        $uniqueNos = $this->postedPaidPayRunUniqueNos();

        $payRunCount = count($uniqueNos);

        $query = $this->payRunTransactionQuery($uniqueNos);
        $this->applyTransactionFilters($query, $request);

        $perPage = in_array((int) $request->input('per_page', 50), [50, 100, 250], true)
            ? (int) $request->input('per_page', 50)
            : 50;

        $transactions = $query->paginate($perPage)->appends($request->all());
        $employees = $this->payRunEmployees($uniqueNos);
        $summary = $this->payRunTransactionSummary($uniqueNos);
        $payRun = null;

        return view('pages.payroll.pay-run.view-transaction', compact('transactions', 'payRun', 'employees', 'summary', 'payRunCount'));
    }

    private function postedPaidPayRunUniqueNos(): array
    {
        return PayRun::whereNotNull('unique_no')
            ->whereIn('status', ['posted', 'paid'])
            ->pluck('unique_no')
            ->filter()
            ->all();
    }

    /**
     * Export filtered transactions as PDF.
     */
    public function exportTransactionPdf(Request $request, $uniqueNo)
    {
        $payRun = PayRun::with(['company', 'createdBy', 'approvedBy'])
            ->where('unique_no', $uniqueNo)
            ->firstOrFail();

        $query = Transaction::with(['account:id,name,account_number', 'payable'])
            ->where('unique_no', $uniqueNo)
            ->orderBy('payable_type')
            ->orderBy('payable_id')
            ->orderBy('id');

        $this->applyTransactionFilters($query, $request);

        $transactions = $query->get();
        $currencySymbol = currency()?->symbol;
        $summary = $this->payRunTransactionSummary([$uniqueNo]);

        $pdf = Pdf::loadView('pages.payroll.pay-run.transaction-pdf', compact('transactions', 'payRun', 'currencySymbol', 'summary'))
            ->setPaper('a4', 'landscape');

        $fileName = 'Transactions_' . $payRun->unique_no . '_' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($fileName);
    }

    public function exportAllTransactionsPdf(Request $request)
    {
        $uniqueNos = $this->postedPaidPayRunUniqueNos();
        $payRunCount = count($uniqueNos);

        $query = $this->payRunTransactionQuery($uniqueNos);
        $this->applyTransactionFilters($query, $request);

        $transactions = $query->get();
        $currencySymbol = currency()?->symbol;
        $summary = $this->payRunTransactionSummary($uniqueNos);
        $payRun = null;

        $pdf = Pdf::loadView('pages.payroll.pay-run.transaction-pdf', compact('transactions', 'payRun', 'payRunCount', 'currencySymbol', 'summary'))
            ->setPaper('a4', 'landscape');

        $fileName = 'Payroll_Transactions_All_' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($fileName);
    }

    /**
     * Export filtered transactions as CSV (Excel-compatible).
     */
    public function exportTransactionCsv(Request $request, $uniqueNo)
    {
        $payRun = PayRun::where('unique_no', $uniqueNo)->firstOrFail();

        $query = Transaction::with(['account:id,name,account_number', 'payable'])
            ->where('unique_no', $uniqueNo)
            ->orderBy('payable_type')
            ->orderBy('payable_id')
            ->orderBy('id');

        $this->applyTransactionFilters($query, $request);

        $transactions = $query->get();
        $currencySymbol = currency()?->symbol ?? '';

        $fileName = 'Transactions_' . $payRun->unique_no . '_' . now()->format('Y-m-d') . '.csv';

        $callback = function () use ($transactions, $currencySymbol) {
            $handle = fopen('php://output', 'w');
            // BOM for Excel UTF-8 support
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Date', 'Account', 'Account #', 'Description', 'Counterparty', 'Debit', 'Credit']);

            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($transactions as $txn) {
                $totalDebit += $txn->debit;
                $totalCredit += $txn->credit;
                fputcsv($handle, [
                    $txn->date->format('Y-m-d'),
                    $txn->account->name ?? 'Unknown',
                    $txn->account->account_number ?? '',
                    $txn->description ?: 'Payroll Journal Entry',
                    $txn->counterparty ?? '-',
                    $txn->debit > 0 ? number_format($txn->debit, 2, '.', '') : '',
                    $txn->credit > 0 ? number_format($txn->credit, 2, '.', '') : '',
                ]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['', '', '', 'TOTALS', '', number_format($totalDebit, 2, '.', ''), number_format($totalCredit, 2, '.', '')]);
            fclose($handle);
        };

        return response()->streamDownload($callback, $fileName, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function exportAllTransactionsCsv(Request $request)
    {
        $uniqueNos = $this->postedPaidPayRunUniqueNos();

        $query = $this->payRunTransactionQuery($uniqueNos);
        $this->applyTransactionFilters($query, $request);

        $transactions = $query->get();
        $fileName = 'Payroll_Transactions_All_' . now()->format('Y-m-d') . '.csv';

        $callback = function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Pay Run', 'Date', 'Account', 'Account #', 'Description', 'Counterparty', 'Debit', 'Credit']);

            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($transactions as $txn) {
                $totalDebit += $txn->debit;
                $totalCredit += $txn->credit;
                fputcsv($handle, [
                    $txn->unique_no,
                    $txn->date->format('Y-m-d'),
                    $txn->account->name ?? 'Unknown',
                    $txn->account->account_number ?? '',
                    $txn->description ?: 'Payroll Journal Entry',
                    $txn->counterparty ?? '-',
                    $txn->debit > 0 ? number_format($txn->debit, 2, '.', '') : '',
                    $txn->credit > 0 ? number_format($txn->credit, 2, '.', '') : '',
                ]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['', '', '', '', 'TOTALS', '', number_format($totalDebit, 2, '.', ''), number_format($totalCredit, 2, '.', '')]);
            fclose($handle);
        };

        return response()->streamDownload($callback, $fileName, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    private function payRunTransactionQuery(array $uniqueNos)
    {
        return Transaction::with(['account:id,name,account_number', 'payable'])
            ->whereIn('unique_no', $uniqueNos)
            ->orderBy('unique_no')
            ->orderBy('payable_type')
            ->orderBy('payable_id')
            ->orderBy('id');
    }

    private function payRunTransactionSummary(array $uniqueNos): array
    {
        if (empty($uniqueNos)) {
            return [
                'employee_count' => 0,
                'gross' => 0,
                'deductions' => 0,
                'net' => 0,
                'pay_run_count' => 0,
            ];
        }

        $summary = DB::table('payslips')
            ->join('pay_runs', 'payslips.pay_run_id', '=', 'pay_runs.id')
            ->whereIn('pay_runs.unique_no', $uniqueNos)
            ->whereNull('payslips.deleted_at')
            ->whereNull('pay_runs.deleted_at')
            ->selectRaw('COUNT(*) as employee_count')
            ->selectRaw('COALESCE(SUM(payslips.gross_earnings), 0) as gross')
            ->selectRaw('COALESCE(SUM(payslips.total_deductions), 0) as deductions')
            ->selectRaw('COALESCE(SUM(payslips.net_pay), 0) as net')
            ->first();

        return [
            'employee_count' => (int) ($summary->employee_count ?? 0),
            'gross' => (float) ($summary->gross ?? 0),
            'deductions' => (float) ($summary->deductions ?? 0),
            'net' => (float) ($summary->net ?? 0),
            'pay_run_count' => count($uniqueNos),
        ];
    }

    private function payRunEmployees(array $uniqueNos)
    {
        if (empty($uniqueNos)) {
            return collect();
        }

        return Employee::query()
            ->select('employees.id', 'employees.full_name', 'employees.employee_id')
            ->join('payslips', 'employees.id', '=', 'payslips.employee_id')
            ->join('pay_runs', 'payslips.pay_run_id', '=', 'pay_runs.id')
            ->whereIn('pay_runs.unique_no', $uniqueNos)
            ->whereNull('employees.deleted_at')
            ->whereNull('payslips.deleted_at')
            ->whereNull('pay_runs.deleted_at')
            ->distinct()
            ->orderBy('employees.full_name')
            ->get();
    }

    /**
     * Shared filter logic for transaction queries.
     */
    private function applyTransactionFilters($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('account', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('account_number', 'like', "%{$search}%");
                    })
                    ->orWhereHasMorph('payable', [Employee::class], function ($sq) use ($search) {
                        $sq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('employee_id', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $employeeIds = collect((array) $request->input('employee_ids', []));

        if ($request->filled('employee_id')) {
            $employeeIds->push($request->input('employee_id'));
        }

        $employeeIds = $employeeIds
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($employeeIds->isNotEmpty()) {
            $query->where('payable_type', 'employee')
                ->whereIn('payable_id', $employeeIds->all());
        }

        $min = $request->filled('min_amount') ? (float) $request->min_amount : null;
        $max = $request->filled('max_amount') ? (float) $request->max_amount : null;

        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        $amountExpression = 'CASE WHEN COALESCE(debit, 0) <> 0 THEN ABS(debit) ELSE ABS(COALESCE(credit, 0)) END';

        if ($min !== null) {
            $query->whereRaw("{$amountExpression} >= ?", [$min]);
        }

        if ($max !== null) {
            $query->whereRaw("{$amountExpression} <= ?", [$max]);
        }
    }

    public function filterPayslips(Request $request, $id)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'gross_min' => 'nullable|numeric|min:0',
            'gross_max' => 'nullable|numeric|min:0',
        ]);

        $payRun = PayRun::findOrFail($id);
        $query = $payRun->payslips()->select('id');

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('full_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        if (isset($validated['gross_min'])) {
            $query->where('gross_earnings', '>=', $validated['gross_min']);
        }

        if (isset($validated['gross_max'])) {
            $query->where('gross_earnings', '<=', $validated['gross_max']);
        }

        $ids = $query->pluck('id')->map(fn($id) => (int) $id)->values();

        return response()->json([
            'success' => true,
            'ids' => $ids,
            'matched' => $ids->count(),
            'total' => $payRun->payslips()->count(),
        ]);
    }

    /**
     * Re-run anomaly detection via AJAX.
     */
    public function validatePayRun($id)
    {
        try {
            $payRun = PayRun::with('payslips.employee', 'payslips.items.payItem')->findOrFail($id);
            $anomalies = $this->anomalyDetector->analyze($payRun);
            return response()->json(['success' => true, 'data' => $anomalies]);
        } catch (\Exception $e) {
            Log::error('PayRun Validate Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function post($id)
    {
        try {
            $payRun = PayRun::findOrFail($id);
            $preflight = $this->preflight->run($payRun, true);
            if (!$preflight['can_post']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payroll preflight failed. Correct blockers and review every warning in the Payroll Control Center before posting.',
                    'preflight' => $preflight,
                ], 422);
            }
            $this->payrollService->postToAccounting($id);
            return response()->json(['success' => true, 'message' => 'Pay Run posted to accounting successfully']);
        } catch (\Exception $e) {
            Log::error('PayRun Post Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error posting pay run: ' . $e->getMessage()]);
        }
    }

    public function supplementary(Request $request, $id)
    {
        $request->validate(['employee_ids' => ['nullable', 'array'], 'employee_ids.*' => ['integer']]);
        try {
            $original = PayRun::findOrFail($id);
            $payRun = $this->payrollService->createSupplementaryPayRun($original, $request->input('employee_ids', []));
            return response()->json([
                'success' => true,
                'message' => 'Supplementary payroll created. Add only the adjustment differences to its zero-base payslips.',
                'redirect_url' => route('payroll.pay-runs.show', $payRun->id),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function reverse(Request $request, $id)
    {
        $data = $request->validate([
            'reversal_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        try {
            $payRun = PayRun::findOrFail($id);
            $this->payrollService->reversePostedPayRun($payRun, $data['reversal_date'], $data['reason']);
            return response()->json(['success' => true, 'message' => 'Payroll reversed with linked opposite journal entries.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function notifyAccounting($id)
    {
        try {
            $payRun = PayRun::findOrFail($id);

            if ($payRun->status !== 'draft') {
                return response()->json(['success' => false, 'message' => 'Only draft pay runs can be sent to accounting.']);
            }

            if ($payRun->approval_status !== 'approved') {
                return response()->json(['success' => false, 'message' => 'Approve this pay run before notifying accounting.']);
            }

            $payRun->update([
                'accounting_notification_status' => 'notified',
                'accounting_notified_at' => now(),
                'accounting_notified_by' => Auth::id(),
            ]);

            return response()->json(['success' => true, 'message' => 'Accountant notified. This pay run is now visible on the accounting dashboard.']);
        } catch (\Exception $e) {
            Log::error('PayRun Notify Accounting Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error notifying accounting: ' . $e->getMessage()]);
        }
    }

    public function pay(Request $request, $id)
    {
        $request->validate([
            'bank_account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')
                    ->where('company_id', loggedCompanyId()['company_id'])
                    ->where('type', 'Account'),
            ],
            'payment_date'    => 'required|date',
            'reference'       => 'nullable|string'
        ]);

        try {
            $this->payrollService->recordPayment(
                $id,
                $request->bank_account_id,
                $request->payment_date,
                $request->reference
            );
            return response()->json(['success' => true, 'message' => 'Payment recorded successfully']);
        } catch (\Exception $e) {
            Log::error('PayRun Pay Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error recording payment: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $this->payrollService->deletePayRun($id);
            return response()->json(['success' => true, 'message' => 'Pay Run and associated records deleted successfully']);
        } catch (\Exception $e) {
            Log::error('PayRun Delete Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error deleting pay run: ' . $e->getMessage()]);
        }
    }

    public function payDeduction(Request $request, $id)
    {
        $request->validate([
            'pay_item_id'     => ['required', Rule::exists('pay_items', 'id')->where('company_id', loggedCompanyId()['company_id'])->where('type', 'deduction')],
            'bank_account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')
                    ->where('company_id', loggedCompanyId()['company_id'])
                    ->where('type', 'Account')
                    ->where('sub_type', 'BANK'),
            ],
            'payment_date'    => 'required|date',
            'reference'       => 'nullable|string|max:255',
        ]);

        try {
            $payRun = PayRun::findOrFail($id);
            if (! in_array($payRun->status, ['posted', 'paid'], true)) {
                return response()->json(['success' => false, 'message' => 'Post the pay run before paying its deductions.']);
            }

            $payslipItemIds = \App\Models\PayslipItem::whereHas('payslip', function ($q) use ($id) {
                $q->where('pay_run_id', $id);
            })
                ->where('pay_item_id', $request->pay_item_id)
                ->whereColumn('paid_amount', '<', 'amount')
                ->pluck('id')
                ->toArray();

            if (empty($payslipItemIds)) {
                return response()->json(['success' => false, 'message' => 'No unpaid items found for this deduction.']);
            }

            $this->payrollService->payDeduction(
                $payslipItemIds,
                $request->bank_account_id,
                $request->payment_date,
                $request->reference
            );

            return response()->json(['success' => true, 'message' => 'Deduction remittance submitted for approval.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Approve a draft pay run.
     */
    public function approve(Request $request, $id)
    {
        $request->validate(['notes' => 'nullable|string|max:500']);
        $payRun = PayRun::findOrFail($id);

        if ($payRun->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Only draft pay runs can be approved.']);
        }

        if ((int) $payRun->created_by === (int) Auth::id()) {
            return response()->json(['success' => false, 'message' => 'The pay run creator cannot approve the same pay run.']);
        }

        $preflight = $this->preflight->run($payRun, true);
        if (!$preflight['can_approve']) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll preflight is not ready. Correct blockers and review every warning in the Payroll Control Center.',
                'preflight' => $preflight,
            ], 422);
        }

        $payRun->update([
            'approval_status' => 'approved',
            'approved_by'     => Auth::id(),
            'approved_at'     => now(),
            'approval_notes'  => $request->notes,
        ]);

        return response()->json(['success' => true, 'message' => 'Pay run approved. You can now post it to accounting.']);
    }

    /**
     * Reject a pay run.
     */
    public function reject(Request $request, $id)
    {
        $request->validate(['notes' => 'nullable|string|max:500']);
        $payRun = PayRun::findOrFail($id);

        $payRun->update([
            'approval_status' => 'rejected',
            'approved_by'     => Auth::id(),
            'approved_at'     => now(),
            'approval_notes'  => $request->notes,
        ]);

        return response()->json(['success' => true, 'message' => 'Pay run rejected.']);
    }

    /**
     * Download bank payment CSV.
     */
    public function bankFile($id)
    {
        $payRun = PayRun::with('payslips.employee')->findOrFail($id);

        if (!in_array($payRun->status, ['posted', 'paid'])) {
            abort(400, 'Bank file only available for posted or paid pay runs.');
        }

        $period   = $payRun->period_end->format('M_Y');
        $filename = "bank_payment_{$period}.csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
        ];

        $callback = function () use ($payRun) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee Name', 'Bank Name', 'Account Number', 'IBAN', 'Net Pay', 'Reference', 'Payment Date']);
            foreach ($payRun->payslips as $slip) {
                fputcsv($handle, [
                    $slip->employee->full_name            ?? '',
                    $slip->employee->bank_name            ?? '',
                    $slip->employee->bank_account_number  ?? '',
                    $slip->employee->bank_iban            ?? '',
                    number_format($slip->net_pay, 2, '.', ''),
                    $payRun->unique_no ?? 'DRAFT',
                    $payRun->payment_date->format('Y-m-d'),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
