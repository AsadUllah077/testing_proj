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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PayRunController extends Controller
{
    protected $payrollService;
    protected $anomalyDetector;

    public function __construct(PayrollService $payrollService, PayrollAnomalyDetector $anomalyDetector)
    {
        $this->payrollService = $payrollService;
        $this->anomalyDetector = $anomalyDetector;
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
        $employees = Employee::where('status', 'active')->get();
        return view('pages.payroll.pay-run.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after:period_start',
            'payment_date' => 'required|date',
            'frequency'    => 'required|in:weekly,fortnightly,monthly',
            'employee_ids' => 'required|array',
        ]);

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

    public function show($id)
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

        $payslips = $payRun->payslips;
        $currencySymbol = currency()?->symbol ?? '';
        $summary = [
            'gross' => $payslips->sum('gross_earnings'),
            'deductions' => $payslips->sum('total_deductions'),
            'net' => $payslips->sum('net_pay'),
            'employee_count' => $payslips->count(),
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

        $bankAccounts = ChartOfAccount::where('category', 'Assets')
            ->where('type', 'Account')
            ->orderBy('name')
            ->get();

        $deductionItems = $payslips
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
            'deductionItems'
        ));
    }

    public function viewTransaction($uniqueNo) {
        $payRun = PayRun::with(['company', 'createdBy', 'approvedBy', 'payslips.employee'])
            ->where('unique_no', $uniqueNo)
            ->firstOrFail();

        $transactions = Transaction::with(['account', 'vendor', 'employee', 'customer', 'payable'])
            ->where('unique_no', $uniqueNo)
            ->paginate(50);

        return view('pages.payroll.pay-run.view-transaction', compact('transactions', 'payRun'));
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
            $this->payrollService->postToAccounting($id);
            return response()->json(['success' => true, 'message' => 'Pay Run posted to accounting successfully']);
        } catch (\Exception $e) {
            Log::error('PayRun Post Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error posting pay run: ' . $e->getMessage()]);
        }
    }

    public function pay(Request $request, $id)
    {
        $request->validate([
            'bank_account_id' => 'required|exists:chart_of_accounts,id',
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
            'pay_item_id'     => 'required|exists:pay_items,id',
            'bank_account_id' => 'required|exists:chart_of_accounts,id',
            'payment_date'    => 'required|date',
            'reference'       => 'nullable|string|max:255',
        ]);

        try {
            $payslipItemIds = \App\Models\PayslipItem::whereHas('payslip', function ($q) use ($id) {
                $q->where('pay_run_id', $id);
            })
                ->where('pay_item_id', $request->pay_item_id)
                ->whereNull('paid_at')
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

            return response()->json(['success' => true, 'message' => 'Deduction payment recorded successfully.']);
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
