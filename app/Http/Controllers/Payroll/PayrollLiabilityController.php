<?php

namespace App\Http\Controllers\Payroll;

use App\Exports\PayrollLiabilityExport;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayItem;
use App\Models\PayslipItem;
use App\Models\ChartOfAccount;
use App\Models\PayrollRemittanceBatch;
use App\Services\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Excel;

class PayrollLiabilityController extends Controller
{
    protected $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    public function index(Request $request)
    {
        // Get all unpaid deduction items from posted/paid pay runs
        $query = PayslipItem::with(['payItem.statutoryBody', 'payslip.payRun', 'payItem.account'])
            ->whereHas('payslip', function ($q) {
                $q->where('company_id', loggedCompanyId()['company_id']);
            })
            ->whereHas('payItem', fn($q) => $this->payrollLiabilityPayItemScope($q))
            ->whereHas('payslip.payRun', function ($q) {
                $q->whereIn('status', ['posted', 'paid']);
            })
            ->whereColumn('paid_amount', '<', 'amount');

        if ($request->pay_item_id) {
            $query->where('pay_item_id', $request->pay_item_id);
        }

        if ($request->pay_run_id) {
            $query->whereHas('payslip', function ($q) use ($request) {
                $q->where('pay_run_id', $request->pay_run_id);
            });
        }

        $unpaidLiabilities = $query->get();

        // Group by Pay Item for display
        $allGroups = $unpaidLiabilities->groupBy('pay_item_id');

        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $perPage = $request->get('per_page', 20);
        $groupedLiabilities = new \Illuminate\Pagination\LengthAwarePaginator(
            $allGroups->forPage($page, $perPage)->map(function ($items, $payItemId) use ($request) {
                $itemPage = $request->get("item_page_{$payItemId}", 1);
                $perItemPage = $request->get('per_page', 200); // Paginate items within the group
                return new \Illuminate\Pagination\LengthAwarePaginator(
                    $items->forPage($itemPage, $perItemPage),
                    $items->count(),
                    $perItemPage,
                    $itemPage,
                    [
                        'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                        'query' => $request->query(),
                        'pageName' => "item_page_{$payItemId}"
                    ]
                );
            }),
            $allGroups->count(),
            $perPage,
            $page,
            [
                'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                'query' => $request->query()
            ]
        );

        $bankAccounts = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])
            ->where('type', 'Account')
            ->where('sub_type', 'BANK')
            ->get();

        $data = [
            'groupedLiabilities' => $groupedLiabilities,
            'bankAccounts' => $bankAccounts,
            'unpaidLiabilities' => $unpaidLiabilities,
            'remittances' => PayrollRemittanceBatch::with(['statutoryBody', 'bankAccount', 'creator', 'approver'])->latest()->limit(25)->get(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.payroll.partials.liabilities-table', $data)->render(),
                'pagination' => $groupedLiabilities->hasPages()
                    ? (string) $groupedLiabilities->withQueryString()->links()
                    : null,
            ]);
        }

        return view('pages.payroll.liabilities.index', $data);
    }

    private function baseQuery()
    {
        return PayslipItem::with([
            'payItem',
            'payItem.account',
            'payslip',
            'payslip.employee',
            'payslip.payRun'
        ])->whereHas('payslip', function ($q) {
            $q->where('company_id', loggedCompanyId()['company_id']);
        })
            ->whereHas('payItem', fn($q) => $this->payrollLiabilityPayItemScope($q));
    }

    private function payrollLiabilityPayItemScope($query)
    {
        return $query
            ->where('type', 'deduction')
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) NOT LIKE ?', ['%advance%'])
                    ->whereRaw('LOWER(name) NOT LIKE ?', ['%salary advance%'])
                    ->whereRaw('LOWER(name) NOT LIKE ?', ['%employee advance%'])
                    ->whereRaw('LOWER(name) NOT LIKE ?', ['%staff advance%']);
            })
            ->where(function ($q) {
                $q->whereDoesntHave('account')
                    ->orWhereHas('account', function ($accountQuery) {
                        $accountQuery->where(function ($systemQuery) {
                            $systemQuery->whereNull('system_account_type')
                                ->orWhere('system_account_type', '!=', 'EMPLOYEE_ADVANCES');
                        });
                    });
            });
    }

    // If want to show all liability then uncomment this and comment the above 
    // private function payrollLiabilityPayItemScope($query)
    // {
    //     return $query
    //         ->where('type', 'deduction');
    // }

    private function applyFilters($query, Request $request)
    {
        return $query
            ->when(
                $request->pay_item_id,
                fn($q) =>
                $q->where('pay_item_id', $request->pay_item_id)
            )

            ->when(
                $request->employee_id,
                fn($q) =>
                $q->whereHas(
                    'payslip',
                    fn($qs) =>
                    $qs->where('employee_id', $request->employee_id)
                )
            )

            ->when(
                $request->type,
                fn($q) =>
                $q->whereHas(
                    'payItem',
                    fn($qs) =>
                    $qs->where('type', $request->type)
                )
            )

            ->when(
                $request->date_from,
                fn($q) =>
                $q->whereHas(
                    'payslip.payRun',
                    fn($qs) =>
                    $qs->where('period_start', '>=', $request->date_from)
                )
            )

            ->when(
                $request->date_to,
                fn($q) =>
                $q->whereHas(
                    'payslip.payRun',
                    fn($qs) =>
                    $qs->where('period_end', '<=', $request->date_to)
                )
            )

            ->when($request->status, fn ($q) => $q->whereHas('payslip.payRun', fn ($qs) => $qs->where('status', $request->status)))
            ->when($request->payment_status === 'unpaid', fn ($q) => $q->where('paid_amount', '<=', 0))
            ->when($request->payment_status === 'partial', fn ($q) => $q->where('paid_amount', '>', 0)->whereColumn('paid_amount', '<', 'amount'))
            ->when($request->payment_status === 'paid', fn ($q) => $q->whereColumn('paid_amount', '>=', 'amount'));
    }


    public function report(Request $request)
    {
        if ($request->has('export')) {
            return $this->export($request, $request->export);
        }

        $query = $this->applyFilters(
            $this->baseQuery(),
            $request
        );
        if (!$request->status) $query->whereHas('payslip.payRun', fn ($q) => $q->whereIn('status', ['posted', 'paid']));

        $totalAmount = (float) (clone $query)->sum('amount');
        $totalItems = (clone $query)->count();
        $totalPayItems = (clone $query)->distinct()->count('pay_item_id');

        $liabilities = $query
            ->sortable()
            ->paginate($request->per_page ?? 20);

        $groupedLiabilities = $liabilities->groupBy('pay_item_id');
        $payItems = PayItem::where('company_id', loggedCompanyId()['company_id'])->where('type', 'deduction')->orderBy('name')->get(['id', 'name']);

        $employees = Employee::select('id', 'full_name')->get();

        $data = compact(
            'liabilities',
            'groupedLiabilities',
            'totalAmount',
            'totalItems',
            'totalPayItems',
            'employees',
            'payItems'
        );

        if ($request->ajax()) {
            return response()->json([
                'html' => view(
                    'pages.payroll.liabilities.partials.liabilities-report-table',
                    $data
                )->render(),
                'summary' => [
                    'total_amount' => $totalAmount,
                    'total_items' => $totalItems,
                    'total_pay_items' => $totalPayItems,
                ],
                'pagination' => $liabilities->hasPages()
                    ? $liabilities->withQueryString()->links()->render()
                    : null,
            ]);
        }

        return view('pages.payroll.liabilities.report', $data);
    }

    public function export(Request $request, $format)
    {
        abort_unless(auth('web')->user()?->hasPermission('payroll.liability.export'), 403);
        $liabilities = $this->applyFilters(
            $this->baseQuery(),
            $request
        )->get();

        $groupedLiabilities = $liabilities->groupBy('pay_item_id');

        if (in_array($format, ['excel', 'xlsx'])) {
            return Excel::download(
                new PayrollLiabilityExport($liabilities),
                'payroll_liabilities.xlsx'
            );
        }

        if ($format === 'pdf') {
            return Pdf::loadView(
                'pdf.payroll-liability-report-pdf',
                compact('liabilities', 'groupedLiabilities')
            )->download('payroll_liabilities.pdf');
        }

        abort(404);
    }

    /**
     * Payment Summary by Statutory Body.
     *
     * Aggregates payroll liability balances per pay item (PAYE / EPF / SOCSO etc.),
     * showing total due, paid, outstanding, overdue & due-soon counts, and the
     * earliest upcoming statutory deadline. Mirrors how QuickBooks "Payroll
     * Liability Balances by Agency" and Xero "Payroll Activity Summary" present
     * statutory remittance obligations.
     */
    public function byStatutoryBody(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];

        $items = PayslipItem::with(['payItem.account', 'payslip.payRun'])
            ->whereHas('payslip', fn($q) => $q->where('company_id', $companyId))
            ->whereHas('payItem', fn($q) => $this->payrollLiabilityPayItemScope($q))
            ->whereHas('payslip.payRun', fn($q) => $q->whereIn('status', ['posted', 'paid']))
            ->when(
                $request->pay_item_id,
                fn($q) => $q->where('pay_item_id', $request->pay_item_id)
            )
            ->when(
                $request->date_from,
                fn($q) => $q->whereHas('payslip.payRun', fn($qs) => $qs->where('period_start', '>=', $request->date_from))
            )
            ->when(
                $request->date_to,
                fn($q) => $q->whereHas('payslip.payRun', fn($qs) => $qs->where('period_end', '<=', $request->date_to))
            )
            ->get();

        $bodies = $items->groupBy(fn ($item) => $item->payItem?->statutory_body_id ?: 'unassigned-' . $item->pay_item_id)->map(function ($rows, $bodyKey) {
            $payItem = $rows->first()->payItem;
            $body = $payItem?->statutoryBody;
            $unpaid = $rows->filter(fn ($row) => $row->outstanding_amount > 0.0001);
            $overdueRows = $unpaid->filter(fn($r) => $r->is_overdue);
            $dueSoonRows = $unpaid->filter(fn($r) => $r->is_due_soon);

            $earliestDue = $unpaid
                ->map(fn($r) => $r->statutory_due_date)
                ->filter()
                ->sort()
                ->first();

            return [
                'pay_item_id' => $payItem?->id,
                'statutory_body_id' => $body?->id,
                'name' => $body?->name ?? ($payItem?->name ?? 'Unassigned authority'),
                'pay_items' => $rows->pluck('payItem.name')->filter()->unique()->values()->implode(', '),
                'account' => $rows->pluck('payItem.account.name')->filter()->unique()->values()->implode(', '),
                'total_amount' => (float) $rows->sum('amount'),
                'paid_amount' => (float) $rows->sum('paid_amount'),
                'outstanding' => (float) $rows->sum('outstanding_amount'),
                'item_count' => $rows->count(),
                'unpaid_count' => $unpaid->count(),
                'overdue_count' => $overdueRows->count(),
                'overdue_amount' => (float) $overdueRows->sum('outstanding_amount'),
                'due_soon_count' => $dueSoonRows->count(),
                'due_soon_amount' => (float) $dueSoonRows->sum('outstanding_amount'),
                'earliest_due_date' => $earliestDue,
            ];
        })->sortByDesc('outstanding')->values();

        // Post-group status filter (operates on the summarised bodies collection)
        if ($request->status) {
            $bodies = $bodies->filter(function ($b) use ($request) {
                return match ($request->status) {
                    'overdue'     => $b['overdue_count'] > 0,
                    'due_soon'    => $b['due_soon_count'] > 0,
                    'outstanding' => $b['outstanding'] > 0,
                    'paid'        => $b['outstanding'] <= 0 && $b['paid_amount'] > 0,
                    default       => true,
                };
            })->values();
        }

        $totals = [
            'total_amount' => (float) $bodies->sum('total_amount'),
            'paid_amount' => (float) $bodies->sum('paid_amount'),
            'outstanding' => (float) $bodies->sum('outstanding'),
            'overdue_amount' => (float) $bodies->sum('overdue_amount'),
            'overdue_count' => (int) $bodies->sum('overdue_count'),
            'due_soon_count' => (int) $bodies->sum('due_soon_count'),
            'body_count' => $bodies->count(),
        ];

        // Paginate the bodies
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $perPage = $request->get('per_page', 20);
        $paginatedBodies = new \Illuminate\Pagination\LengthAwarePaginator(
            $bodies->forPage($page, $perPage),
            $bodies->count(),
            $perPage,
            $page,
            [
                'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                'query' => $request->query()
            ]
        );

        $payItems = PayItem::where('company_id', $companyId)
            ->where(fn($q) => $this->payrollLiabilityPayItemScope($q))
            ->orderBy('name')
            ->get(['id', 'name']);

        $data = compact('totals', 'payItems') + [
            'bodies'      => $paginatedBodies,
            'date_from'   => $request->date_from,
            'date_to'     => $request->date_to,
            'pay_item_id' => $request->pay_item_id,
            'status'      => $request->status,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html'       => view('pages.payroll.liabilities.partials.statutory-body-table', $data)->render(),
                'pagination' => $paginatedBodies->hasPages()
                    ? (string) $paginatedBodies->withQueryString()->links()
                    : null,
            ]);
        }

        return view('pages.payroll.liabilities.by-statutory-body', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*' => 'required|numeric|min:0.01',
            'bank_account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')
                    ->where('company_id', loggedCompanyId()['company_id'])
                    ->where('type', 'Account')
                    ->where('sub_type', 'BANK'),
            ],
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'payee' => 'required|string|max:255',
            'filing_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $attachment = null;
        try {
            $attachment = $request->hasFile('attachment')
                ? $request->file('attachment')->store('private/payroll-remittances/' . loggedCompanyId()['company_id'], 'local')
                : null;
            $this->payrollService->createDeductionRemittance(
                $request->allocations,
                $request->bank_account_id,
                $request->payment_date,
                ['external_reference' => $request->reference, 'payee' => $request->payee, 'filing_reference' => $request->filing_reference, 'notes' => $request->notes, 'attachment' => $attachment]
            );

            return redirect()->route('payroll.liabilities.index')
                ->with('success', 'Payroll remittance submitted for independent approval.');
        } catch (\Exception $e) {
            if ($attachment) Storage::disk('local')->delete($attachment);
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function updatePaymentDate(Request $request, $id)
    {
        $request->validate(['payment_date' => 'required|date']);

        try {
            $batch = PayrollRemittanceBatch::findOrFail($id);
            $wasApproved = $batch->status === 'approved';
            $this->payrollService->updateDeductionRemittancePaymentDate($batch, $request->payment_date);

            return back()->with(
                'success',
                $wasApproved
                    ? 'Payment date updated. The previous approval was cleared; submit it for independent approval again.'
                    : 'Payment date updated successfully.'
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
    public function approve($id)
    {
        try { $this->payrollService->approveDeductionRemittance(PayrollRemittanceBatch::findOrFail($id)); return back()->with('success', 'Payroll remittance approved.'); }
        catch (\Throwable $e) { return back()->with('error', $e->getMessage()); }
    }

    public function post($id)
    {
        try { $this->payrollService->postDeductionRemittance(PayrollRemittanceBatch::findOrFail($id)); return back()->with('success', 'Payroll remittance posted and allocated.'); }
        catch (\Throwable $e) { return back()->with('error', $e->getMessage()); }
    }

    public function reverse(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:1000']);
        try { $this->payrollService->reverseDeductionRemittance(PayrollRemittanceBatch::findOrFail($id), $request->reason); return back()->with('success', 'Payroll remittance reversed.'); }
        catch (\Throwable $e) { return back()->with('error', $e->getMessage()); }
    }

    public function receipt($id)
    {
        $batch = PayrollRemittanceBatch::findOrFail($id);
        abort_unless($batch->attachment && Storage::disk('local')->exists($batch->attachment), 404);
        return Storage::disk('local')->download($batch->attachment);
    }
}
