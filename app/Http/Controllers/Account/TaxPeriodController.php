<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Jobs\ExportTaxAuthorityLedgerJob;
use App\Jobs\ExportTaxPeriodJob;
use App\Models\TaxPeriod;
use App\Models\Transaction;
use App\Services\TaxService;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Str;

class TaxPeriodController extends Controller
{
    protected $taxService;

    public function __construct(TaxService $taxService)
    {
        $this->taxService = $taxService;
    }

    private function filterRecord(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];

        $query = TaxPeriod::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        $taxPeriods = $query->where('company_id', $companyId)->sortable()->paginate($request->per_page ?? 20);

        return $taxPeriods;
    }

    public function index(Request $request)
    {
        $taxPeriods = $this->filterRecord($request);

        // Compliance summary — counts across ALL company periods (not just current page)
        $compliance = $this->buildComplianceSummary();

        // Handle AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.tax.periods.partials.tax-periods-table', compact('taxPeriods'))->render(),
                'pagination' => $taxPeriods->hasPages() ? $taxPeriods->withQueryString()->links()->render() : null,
                'compliance' => $compliance,
            ]);
        }

        return view('pages.account.tax.periods.index', compact('taxPeriods', 'compliance'));
    }

    /**
     * Compliance summary: counts of open / due-soon / overdue / paid periods,
     * plus the most overdue period for headline display.
     */
    private function buildComplianceSummary(): array
    {
        $companyId = loggedCompanyId()['company_id'];
        $allOpen = TaxPeriod::where('company_id', $companyId)
            ->whereIn('status', ['open', 'settled'])
            ->get();

        $overdue = $allOpen->filter(fn ($p) => $p->is_overdue);
        $dueSoon = $allOpen->filter(fn ($p) => $p->is_due_soon);

        return [
            'open_count' => $allOpen->count(),
            'overdue_count' => $overdue->count(),
            'due_soon_count' => $dueSoon->count(),
            'paid_count' => TaxPeriod::where('company_id', $companyId)->where('status', 'paid')->count(),
            'most_overdue_days' => (int) $overdue->max('days_overdue'),
        ];
    }

    public function authorityLedger(Request $request)
    {
        $taxPeriods = $this->filterRecord($request);

        // Calculate cumulative totals
        $totalSalesTax = 0;
        $totalPurchaseTax = 0;
        $totalSettlements = 0;
        $totalPayments = 0;
        $totalRefunds = 0;

        foreach ($taxPeriods as $period) {
            if ($period->status !== 'open') {
                $totalSalesTax += $period->total_payable;
                $totalPurchaseTax += $period->total_receivable;
                $totalSettlements += abs($period->net_amount);

                if ($period->net_amount > 0) {
                    $totalPayments += $period->net_amount;
                } else {
                    $totalRefunds += abs($period->net_amount);
                }
            }
        }

        $remainingBalance = $totalPayments - $totalRefunds;

        $data = compact(
            'totalSalesTax',
            'totalPurchaseTax',
            'totalSettlements',
            'totalPayments',
            'totalRefunds',
            'remainingBalance',
            'taxPeriods'
        );

        // Handle AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.tax.periods.partials.authority-ledger-table', $data)->render(),
                'summary' => [
                    'total_sales_tax' => $totalSalesTax,
                    'total_purchase_tax' => $totalPurchaseTax,
                    'total_settlements' => $totalSettlements,
                    'total_payments' => $totalPayments,
                    'remaining_balance' => $remainingBalance,
                ],
                'pagination' => $taxPeriods->hasPages() ? $taxPeriods->withQueryString()->links()->render() : null,
            ]);
        }

        return view('pages.account.tax.authority-ledger', $data);
    }

    public function exportAuthorityLedger($format)
    {
        // Generate unique job ID
        $jobId = Str::uuid()->toString();
        $companyId = loggedCompanyId()['company_id'];

        // Dispatch export job
        ExportTaxAuthorityLedgerJob::dispatch(
            auth('web')->id(),
            $jobId,
            $format,
            $companyId
        );

        return response()->json([
            'success' => true,
            'message' => 'Tax Authority Ledger export started. Please wait...',
            'job_id' => $jobId,
        ]);
    }

    public function exportPeriod($id, $format)
    {
        // Generate unique job ID
        $jobId = Str::uuid()->toString();

        // Dispatch export job
        ExportTaxPeriodJob::dispatch(
            auth('web')->id(),
            $jobId,
            $format,
            $id
        );

        return response()->json([
            'success' => true,
            'message' => 'Tax Period export started. Please wait...',
            'job_id' => $jobId,
        ]);
    }

    public function create()
    {
        return view('pages.account.tax.periods.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:monthly,quarterly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $companyId = loggedCompanyId()['company_id'];

        // Prevent duplicate/overlapping tax periods
        $overlapping = TaxPeriod::where('company_id', $companyId)
            ->where('start_date', '<=', $request->end_date)
            ->where('end_date', '>=', $request->start_date)
            ->first();

        if ($overlapping) {
            return redirect()->back()
                ->with('error', "A tax period already exists for this date range: {$overlapping->name} ({$overlapping->start_date->format('d M Y')} - {$overlapping->end_date->format('d M Y')}, Status: {$overlapping->status}).")
                ->withInput();
        }

        // Accumulate unclaimed refunds from ALL previous paid periods with negative net
        // Each period's carried_forward reduces its refund, so we sum what remains unclaimed
        $carriedForwardAmount = 0;
        $previousRefundPeriods = TaxPeriod::where('company_id', $companyId)
            ->where('end_date', '<', $request->start_date)
            ->where('status', 'paid')
            ->where('net_amount', '<', 0)
            ->get();

        foreach ($previousRefundPeriods as $prev) {
            // Unclaimed = abs(net) - what was already carried forward to a later period
            $alreadyCarried = TaxPeriod::where('company_id', $companyId)
                ->where('start_date', '>', $prev->end_date)
                ->where('start_date', '<', $request->start_date)
                ->sum('carried_forward_amount');

            $unclaimed = abs($prev->net_amount) - $alreadyCarried;
            if ($unclaimed > 0) {
                $carriedForwardAmount += $unclaimed;
            }
        }

        $balances = $this->taxService->calculateBalances($companyId, $request->start_date, $request->end_date);

        TaxPeriod::create(array_merge($request->all(), [
            'company_id' => $companyId,
            'status' => 'open',
            'total_payable' => $balances['payable'],
            'total_receivable' => $balances['receivable'],
            'net_amount' => $balances['payable'] - $balances['receivable'] - $carriedForwardAmount,
            'carried_forward_amount' => $carriedForwardAmount,
            'created_by' => auth('web')->id(),
            'updated_by' => auth('web')->id(),
            'approval_status' => 'draft',
        ]));

        return redirect()->route('account.tax-periods.index')
            ->with('success', 'Tax Period created successfully.');
    }

    public function show($id)
    {
        $taxPeriod = TaxPeriod::findOrFail($id);
        $entries = Transaction::with('account')->where('company_id', $taxPeriod->company_id)
            ->where('reference_id', $id)
            ->whereIn('type', ['Tax Settlement', 'Tax Settlement Reversal', 'Tax Payment', 'Tax Refund'])
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();
        $balances = $this->taxService->calculateBalances($taxPeriod->company_id, $taxPeriod->start_date, $taxPeriod->end_date);
        if ($taxPeriod->status === 'open') {
            $taxPeriod->fill([
                'total_payable' => $balances['payable'],
                'total_receivable' => $balances['receivable'],
                'net_amount' => $balances['net'],
            ])->save();
        }
        $settlements = $taxPeriod->settlementBatches()->latest()->get();
        $payments = $taxPeriod->paymentBatches()->latest()->get();
        return view('pages.account.tax.periods.show', compact('taxPeriod', 'balances', 'entries', 'settlements', 'payments'));
    }

    public function settle($id)
    {
        try {
            $taxPeriod = TaxPeriod::findOrFail($id);
            $this->taxService->settlePeriod($taxPeriod);

            return redirect()->back()->with('success', 'Tax Period settled successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function submit($id)
    {
        $period = TaxPeriod::findOrFail($id);
        if ($period->status !== 'open' || !in_array($period->approval_status, ['draft', 'rejected'], true)) return back()->with('error', 'Only draft or rejected open tax periods can be submitted.');
        $period->update(['approval_status' => 'submitted', 'updated_by' => auth('web')->id()]);
        return back()->with('success', 'Tax period submitted for review.');
    }

    public function approve(Request $request, $id)
    {
        $request->validate(['approval_note' => 'nullable|string|max:1000']);
        $period = TaxPeriod::findOrFail($id);
        if ($period->status !== 'open' || $period->approval_status !== 'reviewed') return back()->with('error', 'Only reviewed open tax periods can be approved.');
        $period->update(['approval_status' => 'approved', 'approved_by' => auth('web')->id(), 'approved_at' => now(), 'approval_note' => $request->approval_note, 'updated_by' => auth('web')->id()]);
        return back()->with('success', 'Tax period approved for settlement.');
    }

    public function review(Request $request, $id)
    {
        $request->validate(['approval_note' => 'nullable|string|max:1000']);
        $period = TaxPeriod::findOrFail($id);
        if ($period->status !== 'open' || $period->approval_status !== 'submitted') return back()->with('error', 'Only submitted open tax periods can be reviewed.');
        $period->update(['approval_status' => 'reviewed', 'reviewed_by' => auth('web')->id(), 'reviewed_at' => now(), 'approval_note' => $request->approval_note, 'updated_by' => auth('web')->id()]);
        return back()->with('success', 'Tax period reviewed and ready for approval.');
    }

    public function reverse(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:1000']);
        try { $this->taxService->reverseSettlement(TaxPeriod::findOrFail($id), $request->reason); return back()->with('success', 'Settlement reversed. The period is open for controlled correction.'); }
        catch (\Throwable $e) { return back()->with('error', $e->getMessage()); }
    }

    public function updateFiling(Request $request, $id)
    {
        $request->validate(['tax_authority' => 'nullable|string|max:255', 'jurisdiction' => 'nullable|string|max:255', 'registration_number' => 'nullable|string|max:255', 'filing_type' => 'nullable|string|max:255', 'filing_reference' => 'nullable|string|max:255', 'filed_at' => 'nullable|date', 'filing_status' => 'required|in:not_filed,prepared,filed,accepted,rejected', 'filing_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240']);
        $period = TaxPeriod::findOrFail($id);
        $data = $request->only(['tax_authority', 'jurisdiction', 'registration_number', 'filing_type', 'filing_reference', 'filed_at', 'filing_status']);
        if ($request->hasFile('filing_attachment')) $data['filing_attachment'] = $request->file('filing_attachment')->store("private/tax-filings/{$period->company_id}/{$period->id}", 'local');
        $period->update($data + ['updated_by' => auth('web')->id()]);
        return back()->with('success', 'Tax filing evidence updated.');
    }

    public function destroy($id)
    {
        $taxPeriod = TaxPeriod::findOrFail($id);

        if ($taxPeriod->status !== 'open') {
            return redirect()->back()->with('error', 'Only open tax periods can be deleted.');
        }

        $taxPeriod->delete();
        return redirect()->route('account.tax-periods.index')
            ->with('success', 'Tax Period deleted successfully.');
    }
}
