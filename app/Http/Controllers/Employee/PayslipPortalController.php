<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\Payslip;
use App\Models\EmployeeBankChangeRequest;
use App\Models\EmployeeSalaryAdvance;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\TaxCalculationService;
use Illuminate\Http\Request;

class PayslipPortalController extends Controller
{
    private function authEmployee()
    {
        return auth('employee')->user();
    }

    public function index(Request $request)
    {
        $employee = $this->authEmployee();

        $payslips = Payslip::with(['payRun'])
            ->where('employee_id', $employee->id)
            ->whereHas('payRun', fn($q) => $q->whereIn('status', ['posted', 'paid']))
            ->orderByDesc('created_at')
            ->paginate(12);

        $latestPayslip = $payslips->getCollection()->sortByDesc(fn ($slip) => $slip->payRun?->period_end)->first();
        $advances = EmployeeSalaryAdvance::with('recoveries.payRun')
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'paid'])
            ->latest('advance_date')->get();
        $bankChangeRequests = EmployeeBankChangeRequest::where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)->latest()->limit(5)->get();

        return view('pages.employee.payslips.index', compact('payslips', 'employee', 'latestPayslip', 'advances', 'bankChangeRequests'));
    }

    public function requestBankChange(Request $request)
    {
        $employee = $this->authEmployee();
        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_account_number' => ['required', 'string', 'max:255'],
            'bank_iban' => ['nullable', 'string', 'max:255'],
        ]);
        $pending = EmployeeBankChangeRequest::where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)->where('status', 'pending')->exists();
        if ($pending) {
            return back()->withErrors(['bank_name' => 'A bank change request is already pending review.']);
        }
        EmployeeBankChangeRequest::create($data + [
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'status' => 'pending',
        ]);
        return back()->with('success', 'Bank change request submitted for payroll review.');
    }

    public function taxCertificate(?string $fiscalYear = null)
    {
        $employee = $this->authEmployee();
        $taxService = app(TaxCalculationService::class);
        $fiscalYear = $fiscalYear ?: $taxService->fiscalYearForDate($employee->company_id, now());
        abort_unless(preg_match('/^(20\d{2})-(20\d{2})$/', $fiscalYear, $years), 422, 'Invalid fiscal year.');
        $start = $years[1] . '-07-01';
        $end = $years[2] . '-06-30';
        $payslips = Payslip::with(['payRun', 'items.payItem', 'employee.company'])
            ->where('company_id', $employee->company_id)->where('employee_id', $employee->id)
            ->whereHas('payRun', fn ($query) => $query->whereIn('status', ['posted', 'paid'])->whereBetween('period_end', [$start, $end]))
            ->get();
        $summary = [
            'gross' => (float) $payslips->sum('gross_earnings'),
            'taxable' => (float) $payslips->flatMap->items->filter(fn ($item) => $item->payItem?->is_taxable && in_array($item->payItem?->type, ['earning', 'reimbursement']))->sum('amount'),
            'income_tax' => (float) $payslips->flatMap->items->filter(fn ($item) => $item->payItem?->type === 'deduction' && str_contains(strtolower((string) $item->payItem?->name), 'income tax'))->sum('amount'),
        ];
        return Pdf::loadView('pages.employee.payslips.tax-certificate', compact('employee', 'payslips', 'summary', 'fiscalYear'))
            ->setPaper('a4', 'portrait')->download("Tax_Certificate_{$fiscalYear}.pdf");
    }

    public function show(Request $request, $payslipId)
    {
        $employee = $this->authEmployee();
        $payslip  = Payslip::with(['items.payItem', 'employee.company.currency', 'employee.department', 'payRun'])
            ->where('employee_id', $employee->id) // Security: own payslips only
            ->findOrFail($payslipId);

        $leaveBalances = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', $payslip->payRun->period_end->year)
            ->with('leaveType')
            ->get();

        return view('pages.employee.payslips.show', compact('payslip', 'leaveBalances'));
    }

    public function downloadPdf($payslipId)
    {
        $employee = $this->authEmployee();
        $payslip  = Payslip::with(['items.payItem', 'employee.company.currency', 'employee.department', 'payRun'])
            ->where('employee_id', $employee->id) // Security: own payslips only
            ->findOrFail($payslipId);

        $leaveBalances = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', $payslip->payRun->period_end->year)
            ->with('leaveType')
            ->get();

        $pdf = Pdf::loadView('pdf.payslip', [
            'payslip' => $payslip,
            'leaveBalances' => $leaveBalances,
            'isDoc' => false
        ])->setPaper('a4', 'portrait');

        $fileName = 'Payslip_' . $payslip->payRun->period_end->format('M_Y') . '.pdf';
        return $pdf->download($fileName);
    }

    public function downloadDoc($payslipId)
    {
        $employee = $this->authEmployee();
        $payslip  = Payslip::with(['items.payItem', 'employee.company.currency', 'employee.department', 'payRun'])
            ->where('employee_id', $employee->id) // Security: own payslips only
            ->findOrFail($payslipId);

        $leaveBalances = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', $payslip->payRun->period_end->year)
            ->with('leaveType')
            ->get();

        $html = view('pdf.payslip', [
            'payslip' => $payslip,
            'leaveBalances' => $leaveBalances,
            'isDoc' => true
        ])->render();

        $fileName = 'Payslip_' . $payslip->payRun->period_end->format('M_Y') . '.doc';

        return response($html)
            ->header('Content-Type', 'application/msword')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}
