<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\PayRun;
use App\Models\Payslip;
use App\Models\PayItem;
use App\Models\EmployeePayStructure;
use App\Services\Payroll\TaxOptimizationService;
use App\Services\Payroll\CompensationBenchmarkService;
use App\Services\TaxCalculationService;
use App\Support\SelectedRecordOrdering;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use ZipArchive;

class EmployeePayController extends Controller
{
    private const MEDICAL_ALLOWANCE_NAME = 'Medical Allowance';
    private const MEDICAL_ALLOWANCE_RATE = 0.10;

    protected $taxOptimizationService;
    protected $benchmarkService;
    protected $taxCalculationService;

    public function __construct(
        TaxOptimizationService $taxOptimizationService,
        CompensationBenchmarkService $benchmarkService,
        TaxCalculationService $taxCalculationService
    ) {
        $this->taxOptimizationService = $taxOptimizationService;
        $this->benchmarkService = $benchmarkService;
        $this->taxCalculationService = $taxCalculationService;
    }
    public function index(Request $request)
    {
        $publishedPayRuns = PayRun::whereIn('status', ['posted', 'paid'])
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->get();

        $selectedPayRunId = $request->integer('pay_run_id') ?: $publishedPayRuns->first()?->id;
        $selectedPayRun = $publishedPayRuns->firstWhere('id', $selectedPayRunId);
        $selectedPayRunId = $selectedPayRun?->id;

        $selectedEmployeeIds = SelectedRecordOrdering::ids($request, 'selected_employee_ids');

        $employeeQuery = Employee::where('status', 'active')
            ->with(['payStructures.payItem'])
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('full_name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%");
                });
            })
            ->when($request->salary_set === '0', function ($q) {
                $q->whereNull('salary');
            }, function ($q) use ($request) {
                if ($request->salary_set === '1' || !$request->has('salary_set')) {
                    $q->whereNotNull('salary');
                }
            });

        SelectedRecordOrdering::apply($employeeQuery, $selectedEmployeeIds);
        $employeeQuery->sortable();

        if ($selectedPayRunId) {
            $employeeQuery->with([
                'payslips' => fn($q) => $q->where('pay_run_id', $selectedPayRunId)
                    ->with('payRun'),
            ]);
        }

        $employees = $employeeQuery->paginate($request->per_page ?? 20);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.payroll.partials.employee-pay-table', compact('employees', 'selectedPayRun'))->render(),
                'pagination' => $employees->appends($request->query())->links()->toHtml(),
            ]);
        }

        $payItems = PayItem::all();
        $this->findOrCreateMedicalAllowanceItem(Auth::user()->company_id, $payItems, $payItems->first(fn($item) => stripos($item->name, 'basic salary') !== false));
        $payItems = PayItem::all();

        return view('pages.payroll.employee-pay.index', compact('employees', 'payItems', 'publishedPayRuns', 'selectedPayRun'));
    }

    public function edit($employee_id)
    {
        $employee = Employee::findOrFail($employee_id);
        $payItems = PayItem::all();
        $this->findOrCreateMedicalAllowanceItem($employee->company_id, $payItems, $payItems->first(fn($item) => stripos($item->name, 'basic salary') !== false));
        $payItems = PayItem::all();
        $structures = EmployeePayStructure::with('payItem')->where('employee_id', $employee_id)->get();

        $taxInsights = $this->taxOptimizationService->analyze($employee, $structures);
        $benchmarkData = $this->benchmarkService->getBenchmarkingData($employee);

        return view('pages.payroll.employee-pay.edit', compact('employee', 'payItems', 'structures', 'taxInsights', 'benchmarkData'));
    }

    /**
     * AJAX endpoint to get tax optimization insights based on updated amounts.
     */
    public function taxOptimize(Request $request, $employee_id)
    {
        $employee = Employee::findOrFail($employee_id);

        // Mock a collection of structures from request data
        $structures = collect();
        if ($request->has('pay_items')) {
            foreach ($request->pay_items as $itemId => $data) {
                if (!empty($data['amount'])) {
                    $payItem = PayItem::find($itemId);
                    if ($payItem) {
                        $structures->push((object)[
                            'pay_item_id' => $itemId,
                            'amount' => (float)$data['amount'],
                            'payItem' => $payItem
                        ]);
                    }
                }
            }
        }

        $insights = $this->taxOptimizationService->analyze($employee, $structures);

        // Mock benchmarking for the new total
        $totalComp = $structures->sum('amount');
        $benchmarkData = $this->benchmarkService->getBenchmarkingData($employee);
        $benchmarkData['total_monthly_compensation'] = $totalComp; // Overwrite with mock value

        return response()->json([
            'success' => true,
            'data' => $insights,
            'benchmarks' => $benchmarkData
        ]);
    }

    public function update(Request $request, $employee_id)
    {
        $employee = Employee::findOrFail($employee_id);
        $companyId = Auth::user()->company_id;

        // Save bank details + optional portal password
        $updateData = [
            'bank_name'           => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'bank_iban'           => $request->bank_iban,
        ];

        if ($request->filled('portal_password') && $request->portal_password === $request->portal_password_confirmation) {
            $updateData['password'] = Hash::make($request->portal_password);
        }

        $employee->update($updateData);

        DB::transaction(function () use ($request, $employee, $employee_id, $companyId) {
            EmployeePayStructure::where('employee_id', $employee_id)->delete();

            $payItemsData = $request->input('pay_items', []);
            $payItems = PayItem::where('company_id', $companyId)->get()->keyBy('id');
            $resolvedItems = $this->resolvePayTemplateItems(
                $employee,
                $payItemsData,
                $payItems,
                $request->boolean('apply_medical_allowance')
            );

            $hourlyRate = $request->hourly_rate;
            $sortOrder  = 0;

            foreach ($resolvedItems as $itemId => $amount) {
                EmployeePayStructure::create([
                    'company_id'  => $companyId,
                    'employee_id' => $employee_id,
                    'pay_item_id' => $itemId,
                    'amount'      => $amount,
                    'hourly_rate' => $hourlyRate,
                    'sort_order'  => ++$sortOrder,
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Employee pay structure updated successfully']);
    }

    /**
     * Bulk update pay templates for multiple employees.
     * Automatically calculates Income Tax based on taxable earnings.
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => [
                'integer',
                Rule::exists('employees', 'id')->where('company_id', Auth::user()->company_id),
            ],
            'pay_items'      => 'required|array|min:1',
        ]);

        $employeeIds = $request->employee_ids;
        $payItemsData = $request->pay_items;
        $companyId = Auth::user()->company_id;
        $updated = 0;
        $now = now();
        $payItemIds = array_keys($payItemsData);

        $employees = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');
        $payItems = PayItem::where('company_id', $companyId)
            ->where(function ($query) use ($payItemIds) {
                $query->whereIn('id', $payItemIds)
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%income tax%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%basic salary%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%medical allowance%']);
            })
            ->get()
            ->keyBy('id');
        $applyMedicalAllowance = $request->boolean('apply_medical_allowance');

        DB::transaction(function () use ($employeeIds, $employees, $payItemsData, &$payItems, $applyMedicalAllowance, $companyId, $now, &$updated) {
            $existingEmployeeIds = $employees->keys()->all();

            if (!empty($existingEmployeeIds)) {
                // Delete existing structures for selected employees in one query.
                EmployeePayStructure::whereIn('employee_id', $existingEmployeeIds)->delete();
            }

            $rows = [];

            foreach ($employeeIds as $employeeId) {
                $employee = $employees->get($employeeId);
                if (!$employee) continue;

                $sortOrder = 0;
                $resolvedItems = $this->resolvePayTemplateItems($employee, $payItemsData, $payItems, $applyMedicalAllowance);

                // Second pass: prepare structures for bulk insert
                foreach ($resolvedItems as $itemId => $amount) {
                    $rows[] = [
                        'company_id' => $companyId,
                        'employee_id' => $employeeId,
                        'pay_item_id' => $itemId,
                        'amount' => $amount,
                        'hourly_rate' => null,
                        'sort_order' => ++$sortOrder,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                $updated++;
            }

            foreach (array_chunk($rows, 1000) as $chunk) {
                EmployeePayStructure::insert($chunk);
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Pay templates updated for {$updated} employees (Income Tax auto-calculated from taxable earnings).",
        ]);
    }

    private function resolvePayTemplateItems(Employee $employee, array $payItemsData, $payItems, bool $applyMedicalAllowance): array
    {
        $companyId = (int) $employee->company_id;
        $taxItem = $this->findIncomeTaxItem($companyId, $payItems);
        $resolvedItems = [];

        foreach ($payItemsData as $itemId => $data) {
            $itemId = (int) $itemId;
            if ($taxItem && $itemId === (int) $taxItem->id) {
                continue;
            }

            $amount = $data['amount'] ?? null;
            if ($amount === 'auto_salary') {
                $amount = $employee->salary;
            }

            if ($payItems->has($itemId) && is_numeric($amount) && (float) $amount > 0) {
                $resolvedItems[$itemId] = (float) $amount;
            }
        }

        if ($applyMedicalAllowance) {
            $resolvedItems = $this->applyMedicalAllowanceShift($employee, $resolvedItems, $payItems);
        }

        $monthlyTaxable = $this->calculateTaxableMonthlyAmount($resolvedItems, $payItems);
        if ($taxItem && $monthlyTaxable > 0) {
            $monthlyTax = $this->taxCalculationService->calculateMonthlyTax($companyId, $monthlyTaxable);
            if ($monthlyTax > 0) {
                $resolvedItems[$taxItem->id] = $monthlyTax;
            }
        }

        return $resolvedItems;
    }

    private function applyMedicalAllowanceShift(Employee $employee, array $resolvedItems, $payItems): array
    {
        $companyId = (int) $employee->company_id;
        $monthlyTaxable = $this->calculateTaxableMonthlyAmount($resolvedItems, $payItems);

        if ($this->taxCalculationService->calculateMonthlyTax($companyId, $monthlyTaxable) <= 0) {
            return $resolvedItems;
        }

        $basicItem = $payItems->first(fn($item) => stripos($item->name, 'basic salary') !== false);
        if (!$basicItem || empty($resolvedItems[$basicItem->id])) {
            return $resolvedItems;
        }

        $medicalItem = $this->findOrCreateMedicalAllowanceItem($companyId, $payItems, $basicItem);
        $currentBasic = (float) ($resolvedItems[$basicItem->id] ?? 0);
        $currentMedical = (float) ($resolvedItems[$medicalItem->id] ?? 0);
        $medicalBasis = $currentBasic + $currentMedical;
        $medicalAllowanceAmount = round($medicalBasis * self::MEDICAL_ALLOWANCE_RATE, 2);

        if ($medicalAllowanceAmount <= 0 || $medicalBasis <= $medicalAllowanceAmount) {
            return $resolvedItems;
        }

        $resolvedItems[$basicItem->id] = round($medicalBasis - $medicalAllowanceAmount, 2);
        $resolvedItems[$medicalItem->id] = $medicalAllowanceAmount;

        if ($resolvedItems[$basicItem->id] <= 0) {
            unset($resolvedItems[$basicItem->id]);
        }

        return $resolvedItems;
    }

    private function calculateTaxableMonthlyAmount(array $resolvedItems, $payItems): float
    {
        $monthlyTaxable = 0;

        foreach ($resolvedItems as $itemId => $amount) {
            $payItem = $payItems->get((int) $itemId);
            if (
                $payItem
                && $payItem->is_taxable
                && in_array(strtolower($payItem->type), ['earning', 'reimbursement'], true)
            ) {
                $monthlyTaxable += (float) $amount;
            }
        }

        return $monthlyTaxable;
    }

    private function findIncomeTaxItem(int $companyId, $payItems): ?PayItem
    {
        return $payItems->first(fn($item) => strtolower($item->type) === 'deduction' && stripos($item->name, 'income tax') !== false)
            ?? PayItem::where('company_id', $companyId)
                ->where('type', 'deduction')
                ->whereRaw('LOWER(name) LIKE ?', ['%income tax%'])
                ->first();
    }

    private function findOrCreateMedicalAllowanceItem(int $companyId, $payItems, ?PayItem $basicItem = null): PayItem
    {
        $medicalItem = $payItems->first(fn($item) => stripos($item->name, self::MEDICAL_ALLOWANCE_NAME) !== false);

        if (!$medicalItem) {
            $medicalItem = PayItem::create([
                'company_id' => $companyId,
                'name' => self::MEDICAL_ALLOWANCE_NAME,
                'type' => 'earning',
                'account_id' => $basicItem?->account_id,
                'calculation_type' => 'fixed',
                'is_taxable' => false,
            ]);

            $payItems->put($medicalItem->id, $medicalItem);
        }

        if ($medicalItem->is_taxable) {
            $medicalItem->forceFill(['is_taxable' => false])->save();
            $medicalItem->refresh();
            $payItems->put($medicalItem->id, $medicalItem);
        }

        return $medicalItem;
    }

    public function bulkDownloadPayslips(Request $request)
    {
        $validated = $request->validate([
            'pay_run_id' => 'required|integer|exists:pay_runs,id',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => [
                'integer',
                Rule::exists('employees', 'id')->where('company_id', Auth::user()->company_id),
            ],
        ]);

        $payRun = PayRun::whereIn('status', ['posted', 'paid'])
            ->findOrFail($validated['pay_run_id']);

        $payslips = Payslip::with(['items.payItem', 'employee.company.currency', 'employee.department', 'payRun'])
            ->where('pay_run_id', $payRun->id)
            ->whereIn('employee_id', $validated['employee_ids'])
            ->get()
            ->sortBy(fn($payslip) => $payslip->employee->full_name ?? '');

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

        $zipPath = $tempDir . DIRECTORY_SEPARATOR . 'payslips_' . $payRun->id . '_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.zip';
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

        $period = $payRun->period_end->format('M_Y');

        return response()
            ->download($zipPath, "Payslips_{$period}.zip")
            ->deleteFileAfterSend(true);
    }

    private function payslipPdfFileName(Payslip $payslip): string
    {
        $employeeName = (string) Str::of($payslip->employee->full_name ?? 'Employee')
            ->replaceMatches('/[^A-Za-z0-9]+/', '_')
            ->trim('_');

        return 'Payslip_' . ($employeeName ?: 'Employee') . '_' . $payslip->payRun->period_end->format('M_Y') . '_' . $payslip->id . '.pdf';
    }
}
