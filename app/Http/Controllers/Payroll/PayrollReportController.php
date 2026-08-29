<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\PayRun;
use App\Services\Payroll\OvertimeAnalyzer;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollReportController extends Controller
{
    protected $otAnalyzer;

    public function __construct(OvertimeAnalyzer $otAnalyzer)
    {
        $this->otAnalyzer = $otAnalyzer;
    }

    public function summary(Request $request)
    {
        $companyId = Auth::user()->company_id;

        // Get available pay runs for comparison
        $payRuns = PayRun::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'paid'])
            ->orderBy('period_end', 'desc')
            ->get();

        $payRunId     = $request->pay_run_id ?? $payRuns->first()?->id;
        $prevPayRunId = $request->prev_pay_run_id;

        $currentRun  = $payRunId ? PayRun::with('payslips.employee.department')->find($payRunId) : null;
        $previousRun = $prevPayRunId ? PayRun::with('payslips')->find($prevPayRunId) : null;

        $deptBreakdown = [];
        $topItems      = [];

        if ($currentRun) {
            // Department breakdown
            foreach ($currentRun->payslips as $slip) {
                $dept = $slip->employee->department->department ?? 'Unassigned';
                if (!isset($deptBreakdown[$dept])) {
                    $deptBreakdown[$dept] = ['gross' => 0, 'deductions' => 0, 'net' => 0, 'count' => 0];
                }
                $deptBreakdown[$dept]['gross']      += $slip->gross_earnings;
                $deptBreakdown[$dept]['deductions'] += $slip->total_deductions;
                $deptBreakdown[$dept]['net']        += $slip->net_pay;
                $deptBreakdown[$dept]['count']++;
            }

            // Paginate Department Breakdown
            $deptCollection = collect($deptBreakdown);
            $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
            $perPage = $request->get('per_page', 20);
            
            $paginatedDeptBreakdown = new \Illuminate\Pagination\LengthAwarePaginator(
                $deptCollection->forPage($page, $perPage),
                $deptCollection->count(),
                $perPage,
                $page,
                [
                    'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                    'query' => $request->query()
                ]
            );
            $deptBreakdown = $paginatedDeptBreakdown;

            // Top pay items by total amount
            $topItems = DB::table('payslip_items')
                ->join('payslips', 'payslip_items.payslip_id', '=', 'payslips.id')
                ->join('pay_items', 'payslip_items.pay_item_id', '=', 'pay_items.id')
                ->where('payslips.pay_run_id', $currentRun->id)
                ->select('pay_items.name', 'pay_items.type', DB::raw('SUM(payslip_items.amount) as total'))
                ->groupBy('pay_items.id', 'pay_items.name', 'pay_items.type')
                ->orderByDesc('total')
                ->paginate(
                    $request->get('per_page', 20),
                    ['*'],
                    'top_items_page'
                )
                ->withQueryString();
        }

        return view('pages.payroll.reports.summary', compact(
            'payRuns', 'currentRun', 'previousRun', 'deptBreakdown', 'topItems', 'payRunId', 'prevPayRunId'
        ));
    }

    public function overtime(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $months = $request->months ?? 6;
        $perPage = $request->per_page ?? 20;
        $analysis = $this->otAnalyzer->analyze($companyId, $months, $perPage);

        return view('pages.payroll.reports.overtime', compact('analysis', 'months'));
    }

    public function employeeHistory(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $employees = Employee::where('company_id', $companyId)
            ->whereHas('payslips.payRun', fn($query) => $query->whereIn('status', ['posted', 'paid']))
            ->orderBy('full_name')
            ->get(['id', 'employee_id', 'full_name']);

        $employeeId = $request->integer('employee_id') ?: $employees->first()?->id;
        $employee = $employeeId
            ? Employee::where('company_id', $companyId)->find($employeeId)
            : null;

        $history = collect();
        if ($employee) {
            $taxSubquery = $this->taxSubquery();

            $history = Payslip::query()
                ->join('pay_runs', 'payslips.pay_run_id', '=', 'pay_runs.id')
                ->leftJoinSub($taxSubquery, 'tax_lines', 'tax_lines.payslip_id', '=', 'payslips.id')
                ->where('payslips.company_id', $companyId)
                ->where('payslips.employee_id', $employee->id)
                ->whereIn('pay_runs.status', ['posted', 'paid'])
                ->whereNull('payslips.deleted_at')
                ->whereNull('pay_runs.deleted_at')
                ->select([
                    'payslips.id',
                    'payslips.pay_run_id',
                    'payslips.gross_earnings',
                    'payslips.total_deductions',
                    'payslips.net_pay',
                    'pay_runs.unique_no',
                    'pay_runs.period_start',
                    'pay_runs.period_end',
                    'pay_runs.payment_date',
                    'pay_runs.status',
                    DB::raw('COALESCE(tax_lines.tax_amount, 0) as tax_amount'),
                ])
                ->orderByDesc('pay_runs.period_end')
                ->paginate($request->integer('per_page', 12))
                ->withQueryString();
        }

        return view('pages.payroll.reports.employee-history', compact('employees', 'employee', 'employeeId', 'history'));
    }

    public function employeeComparison(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $payRuns = PayRun::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'paid'])
            ->orderByDesc('period_end')
            ->get();

        $currentRunId = $request->integer('pay_run_id') ?: $payRuns->first()?->id;
        $previousRunId = $request->integer('prev_pay_run_id') ?: $payRuns->skip(1)->first()?->id;

        $currentRun = $currentRunId ? $payRuns->firstWhere('id', $currentRunId) : null;
        $previousRun = $previousRunId ? $payRuns->firstWhere('id', $previousRunId) : null;
        $comparison = collect();

        if ($currentRun) {
            $taxSubquery = $this->taxSubquery();
            $previousSubquery = DB::table('payslips')
                ->leftJoinSub($taxSubquery, 'previous_tax', 'previous_tax.payslip_id', '=', 'payslips.id')
                ->where('payslips.pay_run_id', $previousRunId ?: 0)
                ->whereNull('payslips.deleted_at')
                ->select([
                    'payslips.employee_id',
                    DB::raw('payslips.gross_earnings as previous_gross'),
                    DB::raw('payslips.net_pay as previous_net'),
                    DB::raw('COALESCE(previous_tax.tax_amount, 0) as previous_tax'),
                ]);

            $comparison = Payslip::query()
                ->join('employees', 'employees.id', '=', 'payslips.employee_id')
                ->leftJoinSub($taxSubquery, 'current_tax', 'current_tax.payslip_id', '=', 'payslips.id')
                ->leftJoinSub($previousSubquery, 'previous_slips', 'previous_slips.employee_id', '=', 'payslips.employee_id')
                ->where('payslips.company_id', $companyId)
                ->where('payslips.pay_run_id', $currentRun->id)
                ->whereNull('payslips.deleted_at')
                ->whereNull('employees.deleted_at')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = trim($request->search);
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('employees.full_name', 'like', "%{$search}%")
                            ->orWhere('employees.employee_id', 'like', "%{$search}%");
                    });
                })
                ->select([
                    'employees.id as employee_id',
                    'employees.employee_id as employee_code',
                    'employees.full_name',
                    'payslips.gross_earnings',
                    'payslips.net_pay',
                    DB::raw('COALESCE(current_tax.tax_amount, 0) as tax_amount'),
                    DB::raw('COALESCE(previous_slips.previous_gross, 0) as previous_gross'),
                    DB::raw('COALESCE(previous_slips.previous_net, 0) as previous_net'),
                    DB::raw('COALESCE(previous_slips.previous_tax, 0) as previous_tax'),
                ])
                ->orderBy('employees.full_name')
                ->paginate($request->integer('per_page', 25))
                ->withQueryString();
        }

        return view('pages.payroll.reports.employee-comparison', compact(
            'payRuns',
            'currentRun',
            'previousRun',
            'currentRunId',
            'previousRunId',
            'comparison'
        ));
    }

    public function calendar(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $month = $request->input('month', now()->format('Y-m'));
        $calendarDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $start = $calendarDate->copy()->startOfWeek();
        $end = $calendarDate->copy()->endOfMonth()->endOfWeek();

        $payRuns = PayRun::where('company_id', $companyId)
            ->whereBetween('period_end', [$calendarDate->copy()->subMonth(), $calendarDate->copy()->addMonth()])
            ->orderBy('period_end')
            ->get();

        $eventsByDate = [];
        foreach ($payRuns as $payRun) {
            $this->addCalendarEvent($eventsByDate, $payRun->period_start, 'Processing starts', $payRun, 'bg-blue-50 text-blue-700 border-blue-200');
            if ($payRun->accounting_posted_at) {
                $this->addCalendarEvent($eventsByDate, $payRun->accounting_posted_at, 'Posted to accounting', $payRun, 'bg-green-50 text-green-700 border-green-200');
            }
            $this->addCalendarEvent($eventsByDate, $payRun->payment_date, 'Salary payment', $payRun, 'bg-indigo-50 text-indigo-700 border-indigo-200');
            $this->addCalendarEvent($eventsByDate, Carbon::parse($payRun->period_end)->addMonthNoOverflow()->day(15), 'Tax due', $payRun, 'bg-amber-50 text-amber-700 border-amber-200');
        }

        $weeks = collect(CarbonPeriod::create($start, $end))
            ->chunk(7)
            ->map(fn($week) => collect($week));

        return view('pages.payroll.reports.calendar', compact('calendarDate', 'weeks', 'eventsByDate'));
    }

    public function analytics(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $monthsBack = max(3, min(24, $request->integer('months', 12)));
        $fromDate = now()->startOfMonth()->subMonths($monthsBack - 1);

        $taxSubquery = $this->taxSubquery();
        $monthly = Payslip::query()
            ->join('pay_runs', 'payslips.pay_run_id', '=', 'pay_runs.id')
            ->leftJoinSub($taxSubquery, 'tax_lines', 'tax_lines.payslip_id', '=', 'payslips.id')
            ->where('payslips.company_id', $companyId)
            ->whereIn('pay_runs.status', ['posted', 'paid'])
            ->where('pay_runs.period_end', '>=', $fromDate)
            ->whereNull('payslips.deleted_at')
            ->whereNull('pay_runs.deleted_at')
            ->selectRaw("to_char(pay_runs.period_end, 'YYYY-MM') as period_key")
            ->selectRaw("to_char(pay_runs.period_end, 'Mon YYYY') as period_label")
            ->selectRaw('COUNT(payslips.id) as headcount')
            ->selectRaw('SUM(payslips.gross_earnings) as gross')
            ->selectRaw('SUM(payslips.net_pay) as net')
            ->selectRaw('SUM(COALESCE(tax_lines.tax_amount, 0)) as tax')
            ->selectRaw('AVG(payslips.gross_earnings) as average_salary')
            ->selectRaw('MAX(payslips.gross_earnings) as highest_salary')
            ->selectRaw('MIN(payslips.gross_earnings) as lowest_salary')
            ->groupByRaw("to_char(pay_runs.period_end, 'YYYY-MM'), to_char(pay_runs.period_end, 'Mon YYYY')")
            ->orderBy('period_key')
            ->get();

        $latest = $monthly->last();
        $previous = $monthly->slice(-2, 1)->first();
        $payrollGrowth = $previous && (float) $previous->gross > 0
            ? (((float) $latest?->gross - (float) $previous->gross) / (float) $previous->gross) * 100
            : 0;

        $departmentGrowth = $this->departmentGrowth($companyId);

        $analytics = [
            'average_salary' => (float) ($latest->average_salary ?? 0),
            'highest_salary' => (float) ($latest->highest_salary ?? 0),
            'lowest_salary' => (float) ($latest->lowest_salary ?? 0),
            'average_tax' => $latest && $latest->headcount > 0 ? (float) $latest->tax / (int) $latest->headcount : 0,
            'payroll_growth' => $payrollGrowth,
        ];

        return view('pages.payroll.reports.analytics', compact('monthly', 'analytics', 'departmentGrowth', 'monthsBack'));
    }

    public function export(Request $request)
    {
        $request->validate(['pay_run_id' => 'required|exists:pay_runs,id']);

        $currentRun = PayRun::with('payslips.employee.department', 'payslips.items.payItem')->findOrFail($request->pay_run_id);

        $deptBreakdown = [];
        foreach ($currentRun->payslips as $slip) {
            $dept = $slip->employee->department->department ?? 'Unassigned';
            if (!isset($deptBreakdown[$dept])) {
                $deptBreakdown[$dept] = ['gross' => 0, 'deductions' => 0, 'net' => 0, 'count' => 0];
            }
            $deptBreakdown[$dept]['gross']      += $slip->gross_earnings;
            $deptBreakdown[$dept]['deductions'] += $slip->total_deductions;
            $deptBreakdown[$dept]['net']        += $slip->net_pay;
            $deptBreakdown[$dept]['count']++;
        }

        $pdf = Pdf::loadView('pdf.payroll-summary-report', compact('currentRun', 'deptBreakdown'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('payroll-summary-' . $currentRun->period_end->format('M-Y') . '.pdf');
    }

    private function taxSubquery()
    {
        return DB::table('payslip_items')
            ->join('pay_items', 'pay_items.id', '=', 'payslip_items.pay_item_id')
            ->where('pay_items.type', 'deduction')
            ->where(function ($query) {
                $query->where('pay_items.name', 'like', '%tax%')
                    ->orWhere('pay_items.name', 'like', '%PAYE%');
            })
            ->select('payslip_items.payslip_id', DB::raw('SUM(payslip_items.amount) as tax_amount'))
            ->groupBy('payslip_items.payslip_id');
    }

    private function addCalendarEvent(array &$eventsByDate, $date, string $label, PayRun $payRun, string $class): void
    {
        if (! $date) {
            return;
        }

        $key = Carbon::parse($date)->toDateString();
        $eventsByDate[$key][] = [
            'label' => $label,
            'pay_run_id' => $payRun->id,
            'period' => $payRun->period_start->format('M d') . ' - ' . $payRun->period_end->format('M d'),
            'status' => $payRun->status,
            'class' => $class,
        ];
    }

    private function departmentGrowth(int $companyId)
    {
        $payRuns = PayRun::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'paid'])
            ->orderByDesc('period_end')
            ->limit(2)
            ->get();

        if ($payRuns->count() < 2) {
            return collect();
        }

        [$currentRun, $previousRun] = [$payRuns[0], $payRuns[1]];

        $current = $this->departmentTotals($currentRun->id);
        $previous = $this->departmentTotals($previousRun->id);

        return $current->map(function ($row) use ($previous) {
            $previousGross = (float) ($previous->get($row->department)->gross ?? 0);
            $currentGross = (float) $row->gross;
            $row->previous_gross = $previousGross;
            $row->growth = $previousGross > 0 ? (($currentGross - $previousGross) / $previousGross) * 100 : 0;
            return $row;
        })->values();
    }

    private function departmentTotals(int $payRunId)
    {
        return Payslip::query()
            ->join('employees', 'employees.id', '=', 'payslips.employee_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->where('payslips.pay_run_id', $payRunId)
            ->whereNull('payslips.deleted_at')
            ->selectRaw("COALESCE(departments.department, 'Unassigned') as department")
            ->selectRaw('SUM(payslips.gross_earnings) as gross')
            ->groupByRaw("COALESCE(departments.department, 'Unassigned')")
            ->get()
            ->keyBy('department');
    }
}
