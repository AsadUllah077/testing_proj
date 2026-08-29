<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Jobs\SendPayslipEmailJob;
use App\Models\LeaveBalance;
use App\Models\Payslip;
use App\Models\PayslipItem;
use App\Models\PayItem;
use App\Models\PayRun;
use App\Services\TaxCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;


class PayslipController extends Controller
{
    public function __construct(private readonly TaxCalculationService $taxCalculationService)
    {
    }

    public function updateItem(Request $request, $itemId)
    {
        $request->validate(['amount' => ['required', 'numeric', 'min:0']]);
        $companyId = auth('web')->user()->company_id;
        $item = PayslipItem::whereHas('payslip', fn ($query) => $query->where('company_id', $companyId))->findOrFail($itemId);
        $payslip = $item->payslip;

        if ($payslip->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Cannot edit processed payslips.']);
        }

        $item->update(['amount' => $request->amount]);
        $this->recalculatePayslip($payslip);

        return response()->json(['success' => true]);
    }

    public function addItem(Request $request, $payslipId)
    {
        $companyId = auth('web')->user()->company_id;
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'pay_item_id' => ['required', Rule::exists('pay_items', 'id')->where('company_id', $companyId)],
        ]);
        $payslip = Payslip::findOrFail($payslipId);
        $payItem = PayItem::where('company_id', $companyId)->findOrFail($request->pay_item_id);

        if ($payslip->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Cannot edit processed payslips.']);
        }

        PayslipItem::create([
            'payslip_id' => $payslip->id,
            'pay_item_id' => $payItem->id,
            'amount' => $request->amount,
            'description' => $payItem->name,
        ]);

        $this->recalculatePayslip($payslip);

        return response()->json(['success' => true]);
    }

    public function removeItem($itemId)
    {
        $companyId = auth('web')->user()->company_id;
        $item = PayslipItem::whereHas('payslip', fn ($query) => $query->where('company_id', $companyId))->findOrFail($itemId);
        $payslip = $item->payslip;

        if ($payslip->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Cannot edit processed payslips.']);
        }

        $item->delete();
        $this->recalculatePayslip($payslip);

        return response()->json(['success' => true]);
    }

    /**
     * Download payslip as PDF.
     */
    public function downloadPdf(PayRun $payRun, Payslip $payslip)
    {
        $this->assertPayslipBelongsToPayRun($payRun, $payslip);
        $payslip->load(['items.payItem', 'employee.company.currency', 'employee.department', 'payRun']);

        $leaveBalances = LeaveBalance::where('employee_id', $payslip->employee_id)
            ->where('year', $payslip->payRun->period_end->year)
            ->with('leaveType')
            ->get();

        $pdf = Pdf::loadView('pdf.payslip', [
            'payslip' => $payslip, 
            'leaveBalances' => $leaveBalances,
            'isDoc' => false
        ])->setPaper('a4', 'portrait');

        $fileName = 'Payslip_' . str_replace(' ', '_', $payslip->employee->full_name) . '_' . $payslip->payRun->period_end->format('M_Y') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Download payslip as DOC (Word) file.
     */
    public function downloadDoc(PayRun $payRun, Payslip $payslip)
    {
        $this->assertPayslipBelongsToPayRun($payRun, $payslip);
        $payslip->load(['items.payItem', 'employee.company.currency', 'employee.department', 'payRun']);

        $leaveBalances = LeaveBalance::where('employee_id', $payslip->employee_id)
            ->where('year', $payslip->payRun->period_end->year)
            ->with('leaveType')
            ->get();

        $html = view('pdf.payslip', [
            'payslip' => $payslip, 
            'leaveBalances' => $leaveBalances,
            'isDoc' => true
        ])->render();

        $fileName = 'Payslip_' . str_replace(' ', '_', $payslip->employee->full_name) . '_' . $payslip->payRun->period_end->format('M_Y') . '.doc';

        return response($html)
            ->header('Content-Type', 'application/msword')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    /**
     * Email payslip to the employee.
     */
    public function email(PayRun $payRun, Payslip $payslip)
    {
        $this->assertPayslipBelongsToPayRun($payRun, $payslip);
        $payslip->load(['items.payItem', 'employee.company', 'employee.department', 'payRun']);

        if (!$payslip->employee->email) {
            return response()->json(['success' => false, 'message' => 'Employee has no email address.']);
        }

        SendPayslipEmailJob::dispatch($payslip->id);

        return response()->json(['success' => true, 'message' => 'Payslip email queued for ' . $payslip->employee->full_name]);
    }

    /**
     * Email all payslips in a pay run.
     */
    public function emailAll(PayRun $payRun)
    {
        $payRun->load('payslips.employee');
        $count = 0;

        foreach ($payRun->payslips as $payslip) {
            if ($payslip->employee && $payslip->employee->email) {
                SendPayslipEmailJob::dispatch($payslip->id);
                $count++;
            }
        }

        return response()->json(['success' => true, 'message' => "Payslip emails queued for {$count} employees."]);
    }

    protected function recalculatePayslip($payslip)
    {
        $payslip->load(['items.payItem', 'payRun']);

        $taxableEarnings = $payslip->items
            ->filter(fn ($item) => $item->payItem?->is_taxable
                && in_array(strtolower((string) $item->payItem?->type), ['earning', 'reimbursement'], true))
            ->sum(fn ($item) => (float) $item->amount);

        $taxItem = $payslip->items->first(fn ($item) => $item->payItem
            && strtolower((string) $item->payItem->type) === 'deduction'
            && str_contains(strtolower((string) $item->payItem->name), 'income tax'));

        if ($taxItem) {
            $taxItem->update([
                'amount' => $this->taxCalculationService->calculateMonthlyTaxForDate(
                    $payslip->company_id,
                    (float) $taxableEarnings,
                    $payslip->payRun->period_end
                ),
            ]);
        }

        $gross = $payslip->items()->whereHas('payItem', fn($q) => $q->whereIn('type', ['earning', 'reimbursement']))->sum('amount');
        $deductions = $payslip->items()->whereHas('payItem', fn($q) => $q->where('type', 'deduction'))->sum('amount');

        $fiscalYear = $this->taxCalculationService->fiscalYearForDate($payslip->company_id, $payslip->payRun->period_end);
        preg_match('/(20\d{2})-(20\d{2})/', $fiscalYear, $years);
        $fiscalStart = ($years[1] ?? $payslip->payRun->period_end->year) . '-07-01';
        $fiscalEnd = ($years[2] ?? ($payslip->payRun->period_end->year + 1)) . '-06-30';
        $prior = DB::table('payslips')
            ->join('pay_runs', 'pay_runs.id', '=', 'payslips.pay_run_id')
            ->where('payslips.company_id', $payslip->company_id)
            ->where('payslips.employee_id', $payslip->employee_id)
            ->where('payslips.id', '<>', $payslip->id)
            ->whereNull('payslips.deleted_at')
            ->whereNull('pay_runs.deleted_at')
            ->whereBetween('pay_runs.period_end', [$fiscalStart, $fiscalEnd])
            ->where('pay_runs.period_end', '<', $payslip->payRun->period_end)
            ->whereNotIn('pay_runs.status', ['cancelled'])
            ->selectRaw('COALESCE(SUM(payslips.gross_earnings), 0) AS gross, COALESCE(SUM(payslips.total_deductions), 0) AS deductions, COALESCE(SUM(payslips.net_pay), 0) AS net')
            ->first();

        $payslip->update([
            'gross_earnings' => $gross,
            'total_deductions' => $deductions,
            'net_pay' => $gross - $deductions,
            'ytd_gross' => (float) $prior->gross + (float) $gross,
            'ytd_deductions' => (float) $prior->deductions + (float) $deductions,
            'ytd_net' => (float) $prior->net + ((float) $gross - (float) $deductions),
            'tax_fiscal_year' => $fiscalYear,
            'tax_calculation_snapshot' => $this->taxCalculationService->auditSnapshotForDate($payslip->company_id, (float) $taxableEarnings, $payslip->payRun->period_end),
        ]);

        $payslip->payRun->update([
            'approval_status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'approval_notes' => null,
        ]);
    }

    private function assertPayslipBelongsToPayRun(PayRun $payRun, Payslip $payslip): void
    {
        abort_if((int) $payslip->pay_run_id !== (int) $payRun->id, 404);
    }
}
