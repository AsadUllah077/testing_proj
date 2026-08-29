<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Jobs\SendPayslipEmailJob;
use App\Models\LeaveBalance;
use App\Models\PayRun;
use App\Models\Payslip;
use App\Support\SelectedRecordOrdering;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use ZipArchive;

class PayslipDownloadController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'period_from' => 'nullable|date_format:Y-m',
            'period_to' => 'nullable|date_format:Y-m',
        ]);

        $payRuns = PayRun::whereIn('status', ['posted', 'paid'])
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->get();

        $hasRange = $request->filled('period_from') || $request->filled('period_to');
        $selectedPayRunId = $hasRange ? null : ($request->integer('pay_run_id') ?: $payRuns->first()?->id);
        $selectedPayRun = $payRuns->firstWhere('id', $selectedPayRunId);
        $perPage = (int) $request->input('per_page', 50);
        $perPage = $perPage > 0 ? $perPage : 50;

        $selectedEmployeeIds = SelectedRecordOrdering::ids($request, 'selected_employee_ids');

        $payslipQuery = Payslip::with(['employee', 'payRun'])
            ->whereHas('payRun', fn($query) => $query->whereIn('status', ['posted', 'paid']))
            ->when($selectedPayRun, fn($query) => $query->where('pay_run_id', $selectedPayRun->id))
            ->when($hasRange, fn($query) => $this->applyPayRunPeriodRange($query, $request))
            ->when(!$selectedPayRun && !$hasRange, fn($query) => $query->whereRaw('1 = 0'))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->search);

                $query->whereHas('employee', function ($employeeQuery) use ($search) {
                    $employeeQuery->where(function ($subQuery) use ($search) {
                        $subQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('employee_id', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            });

        SelectedRecordOrdering::apply($payslipQuery, $selectedEmployeeIds, 'employee_id');
        $payslipQuery->orderByDesc('id');

        $payslips = $payslipQuery
            ->paginate($perPage, ['*'], 'payslip_page')
            ->appends($request->except('payslip_page'));

        return view('pages.payroll.payslip-download.index', compact('payRuns', 'selectedPayRun', 'payslips', 'hasRange'));
    }

    public function bulkPdf(Request $request)
    {
        $validated = $request->validate([
            'pay_run_id' => 'nullable|integer|exists:pay_runs,id',
            'period_from' => 'nullable|date_format:Y-m',
            'period_to' => 'nullable|date_format:Y-m',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => [
                'integer',
                Rule::exists('employees', 'id')->where('company_id', auth('web')->user()->company_id),
            ],
        ]);

        $hasRange = !empty($validated['period_from']) || !empty($validated['period_to']);

        if (!$hasRange && empty($validated['pay_run_id'])) {
            abort(422, 'Please select a pay run or a pay run month range.');
        }

        $payRun = null;
        if (!$hasRange) {
            $payRun = PayRun::whereIn('status', ['posted', 'paid'])
                ->findOrFail($validated['pay_run_id']);
        }

        $payslips = Payslip::with(['items.payItem', 'employee.company.currency', 'employee.department', 'payRun'])
            ->whereHas('payRun', fn($query) => $query->whereIn('status', ['posted', 'paid']))
            ->when($payRun, fn($query) => $query->where('pay_run_id', $payRun->id))
            ->when($hasRange, fn($query) => $this->applyPayRunPeriodRange($query, $request))
            ->whereIn('employee_id', $validated['employee_ids'])
            ->get()
            ->sortBy(fn($payslip) => ($payslip->employee->full_name ?? '') . '|' . $payslip->payRun->period_end->format('Ymd'));

        if ($payslips->isEmpty()) {
            abort(404, 'No downloadable payslips were found for the selected employees.');
        }

        if (!class_exists(ZipArchive::class)) {
            abort(500, 'Bulk payslip download requires the PHP Zip extension.');
        }

        $tempDir = storage_path('app/temp/payslips');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir . DIRECTORY_SEPARATOR . 'payslips_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Unable to create the payslip archive.');
        }

        foreach ($payslips as $payslip) {
            $leaveBalances = LeaveBalance::where('employee_id', $payslip->employee_id)
                ->where('year', $payslip->payRun->period_end->year)
                ->with('leaveType')
                ->get();

            $pdf = Pdf::loadView('pdf.payslip', [
                'payslip' => $payslip,
                'leaveBalances' => $leaveBalances,
                'isDoc' => false,
            ])->setPaper('a4', 'portrait');

            $zip->addFromString($this->payslipPdfFileName($payslip), $pdf->output());
        }

        $zip->close();

        $period = $payRun
            ? $payRun->period_end->format('M_Y')
            : $this->rangeLabel($request);

        return response()
            ->download($zipPath, "Payslips_{$period}.zip")
            ->deleteFileAfterSend(true);
    }

    public function bulkEmail(Request $request)
    {
        $validated = $request->validate([
            'pay_run_id' => 'nullable|integer|exists:pay_runs,id',
            'period_from' => 'nullable|date_format:Y-m',
            'period_to' => 'nullable|date_format:Y-m',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => [
                'integer',
                Rule::exists('employees', 'id')->where('company_id', auth('web')->user()->company_id),
            ],
        ]);

        $hasRange = !empty($validated['period_from']) || !empty($validated['period_to']);

        if (!$hasRange && empty($validated['pay_run_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a pay run or a pay run month range.',
            ], 422);
        }

        $payRun = null;
        if (!$hasRange) {
            $payRun = PayRun::whereIn('status', ['posted', 'paid'])
                ->findOrFail($validated['pay_run_id']);
        }

        $payslips = Payslip::with(['employee', 'payRun'])
            ->whereHas('payRun', fn($query) => $query->whereIn('status', ['posted', 'paid']))
            ->when($payRun, fn($query) => $query->where('pay_run_id', $payRun->id))
            ->when($hasRange, fn($query) => $this->applyPayRunPeriodRange($query, $request))
            ->whereIn('employee_id', $validated['employee_ids'])
            ->get();

        if ($payslips->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No payslips were found for the selected employees.',
            ], 404);
        }

        $queued = 0;
        $skipped = 0;

        foreach ($payslips as $payslip) {
            if ($payslip->employee?->email) {
                SendPayslipEmailJob::dispatch($payslip->id);
                $queued++;
            } else {
                $skipped++;
            }
        }

        return response()->json([
            'success' => $queued > 0,
            'message' => $queued > 0
                ? "Payslip emails queued for {$queued} payslip(s)." . ($skipped > 0 ? " {$skipped} skipped because employee email is missing." : '')
                : 'No emails were queued because the selected employees do not have email addresses.',
        ], $queued > 0 ? 200 : 422);
    }

    private function applyPayRunPeriodRange($query, Request $request)
    {
        return $query->whereHas('payRun', function ($payRunQuery) use ($request) {
            if ($request->filled('period_from')) {
                $from = Carbon::createFromFormat('Y-m', $request->period_from)->startOfMonth();
                $payRunQuery->whereDate('period_end', '>=', $from);
            }

            if ($request->filled('period_to')) {
                $to = Carbon::createFromFormat('Y-m', $request->period_to)->endOfMonth();
                $payRunQuery->whereDate('period_end', '<=', $to);
            }
        });
    }

    private function rangeLabel(Request $request): string
    {
        $from = $request->filled('period_from')
            ? Carbon::createFromFormat('Y-m', $request->period_from)->format('M_Y')
            : 'Start';

        $to = $request->filled('period_to')
            ? Carbon::createFromFormat('Y-m', $request->period_to)->format('M_Y')
            : 'Latest';

        return "{$from}_to_{$to}";
    }

    private function payslipPdfFileName(Payslip $payslip): string
    {
        $employeeName = (string) Str::of($payslip->employee->full_name ?? 'Employee')
            ->replaceMatches('/[^A-Za-z0-9]+/', '_')
            ->trim('_');

        return 'Payslip_' . ($employeeName ?: 'Employee') . '_' . $payslip->payRun->period_end->format('M_Y') . '_' . $payslip->id . '.pdf';
    }
}
