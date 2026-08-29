<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Mail\VendorStatementMail;
use App\Models\Vendor;
use App\Models\Bill;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Support\SelectedRecordOrdering;
use ZipArchive;

class VendorStatementController extends Controller
{
    public function index(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];

        $query = Vendor::where('company_id', $companyId)
            ->whereHas('bills', fn($q) => $q->where('approval_status', 'approved')->where('remaining_amount', '>', 0))
            ->withSum(['bills' => fn($q) => $q->where('remaining_amount', '>', 0)], 'remaining_amount')
            ->withCount(['bills' => fn($q) => $q->where('remaining_amount', '>', 0)]);

        // vendors filter
        if ($request->has('vendor_id') && $request->vendor_id != '') {
            $query->where('id', $request->vendor_id);
        }

        $sumSubquery = "
            SELECT COALESCE(SUM(bills.remaining_amount), 0)
            FROM bills
            WHERE bills.vendor_id = vendors.id
            AND bills.remaining_amount > 0
        ";

        if ($request->filled('min_amount')) {
            $query->whereRaw("($sumSubquery) >= ?", [$request->min_amount]);
        }

        if ($request->filled('max_amount')) {
            $query->whereRaw("($sumSubquery) <= ?", [$request->max_amount]);
        }

        $selectedVendorIds = SelectedRecordOrdering::ids($request, 'selected_vendor_ids');

        $vendors = SelectedRecordOrdering::apply($query, $selectedVendorIds)
            ->orderByDesc('bills_sum_remaining_amount')
            ->paginate($request->per_page ?? 20);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.vendor-statement.table', compact('vendors'))->render(),
                'pagination' => $vendors->appends(request()->query())->links()->toHtml(),
            ]);
        }

        $aging = $this->computeAgingBuckets($companyId);

        return view('pages.account.vendor-statement.index', compact('vendors', 'aging'));
    }

    /**
     * Aggregate outstanding payables into aging buckets (due = bill date + 30 days).
     */
    private function computeAgingBuckets(int $companyId): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $dueExpr  = "COALESCE(due_date::date, date::date + 30)";
            $today    = "CURRENT_DATE";
            $diffExpr = "($today - $dueExpr)";
        } else {
            // MySQL / MariaDB
            $dueExpr  = "COALESCE(due_date, DATE_ADD(date, INTERVAL 30 DAY))";
            $today    = "CURDATE()";
            $diffExpr = "DATEDIFF($today, $dueExpr)";
        }

        $row = Bill::where('company_id', $companyId)
            ->where('approval_status', 'approved')
            ->where('remaining_amount', '>', 0)
            ->selectRaw("
                SUM(CASE WHEN $dueExpr >= $today THEN remaining_amount ELSE 0 END) AS current_bucket,
                SUM(CASE WHEN $diffExpr BETWEEN 1  AND 30 THEN remaining_amount ELSE 0 END) AS b1,
                SUM(CASE WHEN $diffExpr BETWEEN 31 AND 60 THEN remaining_amount ELSE 0 END) AS b2,
                SUM(CASE WHEN $diffExpr BETWEEN 61 AND 90 THEN remaining_amount ELSE 0 END) AS b3,
                SUM(CASE WHEN $diffExpr > 90 THEN remaining_amount ELSE 0 END) AS b4,
                SUM(remaining_amount) AS total_outstanding
            ")
            ->first();

        return [
            'current' => (float) ($row->current_bucket ?? 0),
            'b1'      => (float) ($row->b1 ?? 0),
            'b2'      => (float) ($row->b2 ?? 0),
            'b3'      => (float) ($row->b3 ?? 0),
            'b4'      => (float) ($row->b4 ?? 0),
            'total'   => (float) ($row->total_outstanding ?? 0),
        ];
    }

    public function show(Request $request, Vendor $vendor)
    {
        $this->validateStatementRequest($request);

        $companyId = loggedCompanyId()['company_id'];
        $filters = $this->statementFilters($request);
        $asOf = $filters['to_date'] ?? Carbon::today()->toDateString();

        $query = $this->statementQuery($companyId, $vendor, $filters);
        $summaryBills = (clone $query)->get();

        $bills = $query
            ->orderBy('date')
            ->paginate($filters['per_page'])
            ->appends($request->query());

        $totalBilled      = $summaryBills->sum('total');
        $totalPaid        = $summaryBills->sum('paid_amount');
        $totalOutstanding = $summaryBills->sum('remaining_amount');

        $overdueBills = $summaryBills->filter(
            fn($bill) => $bill->remaining_amount > 0 && Carbon::parse($bill->date)->addDays(30)->lt(Carbon::parse($asOf))
        );

        return view('pages.account.vendor-statement.show', compact(
            'vendor',
            'bills',
            'asOf',
            'filters',
            'totalBilled',
            'totalPaid',
            'totalOutstanding',
            'overdueBills'
        ));
    }

    public function exportPdf(Request $request, Vendor $vendor)
    {
        $this->validateStatementRequest($request);

        $companyId = loggedCompanyId()['company_id'];
        $filters = $this->statementFilters($request);
        $asOf = $filters['to_date'] ?? Carbon::today()->toDateString();
        $pdf = $this->statementPdf($vendor, $filters, $asOf, $companyId);

        return $pdf->download('statement-' . str($vendor->name)->slug() . '-' . $asOf . '.pdf');
    }

    public function bulkPdf(Request $request)
    {
        $validated = $request->validate([
            'vendor_ids' => 'required|array|min:1',
            'vendor_ids.*' => 'integer|exists:vendors,id',
            'as_of' => 'nullable|date',
        ]);

        $companyId = loggedCompanyId()['company_id'];
        $asOf = $validated['as_of'] ?? Carbon::today()->toDateString();
        $filters = $this->bulkStatementFilters($asOf);

        $vendors = Vendor::where('company_id', $companyId)
            ->whereIn('id', $validated['vendor_ids'])
            ->orderBy('name')
            ->get();

        if ($vendors->isEmpty()) {
            return back()->with('error', 'No selected vendors were found for this company.');
        }

        $statements = $vendors
            ->map(fn($vendor) => $this->statementData($vendor, $filters, $asOf, $companyId))
            ->all();

        $pdf = Pdf::loadView('pages.account.vendor-statement.bulk-pdf', compact('statements'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('vendor_statements_' . now()->format('Ymd_His') . '.pdf');
    }

    public function bulkSeparatePdfs(Request $request)
    {
        $validated = $request->validate([
            'vendor_ids' => 'required|array|min:1',
            'vendor_ids.*' => 'integer|exists:vendors,id',
            'as_of' => 'nullable|date',
        ]);

        if (!class_exists(ZipArchive::class)) {
            return back()->with('error', 'ZIP extension is not enabled on this server.');
        }

        $companyId = loggedCompanyId()['company_id'];
        $asOf = $validated['as_of'] ?? Carbon::today()->toDateString();
        $filters = $this->bulkStatementFilters($asOf);

        $vendors = Vendor::where('company_id', $companyId)
            ->whereIn('id', $validated['vendor_ids'])
            ->orderBy('name')
            ->get();

        if ($vendors->isEmpty()) {
            return back()->with('error', 'No selected vendors were found for this company.');
        }

        $tempDirectory = storage_path('app/temp/vendor-statements');
        if (!is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0755, true);
        }

        $downloadName = 'vendor_statements_' . now()->format('Ymd_His') . '.zip';
        $zipPath = $tempDirectory . DIRECTORY_SEPARATOR . Str::random(10) . '_' . $downloadName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Unable to create vendor statement zip file.');
        }

        foreach ($vendors as $vendor) {
            $pdf = $this->statementPdf($vendor, $filters, $asOf, $companyId);
            $fileName = 'statement_' . $this->safeFilenamePart($vendor->name) . '_' . $vendor->id . '_' . $asOf . '.pdf';
            $zip->addFromString($fileName, $pdf->output());
        }

        $zip->close();

        return response()->download($zipPath, $downloadName)->deleteFileAfterSend(true);
    }

    public function sendEmail(Request $request, Vendor $vendor)
    {
        $this->validateStatementRequest($request);

        $companyId = loggedCompanyId()['company_id'];
        $filters = $this->statementFilters($request);
        $asOf = $filters['to_date'] ?? Carbon::today()->toDateString();

        if (empty($vendor->email)) {
            return back()->with('error', 'Vendor ' . $vendor->name . ' has no email address.');
        }

        $bills = $this->statementQuery($companyId, $vendor, $filters)
            ->where('remaining_amount', '>', 0)
            ->orderBy('date')
            ->get();

        if ($bills->isEmpty()) {
            return back()->with('error', 'No outstanding bills to send for ' . $vendor->name . '.');
        }

        $company = auth('web')->user()->company;

        Mail::to($vendor->email)->queue(new VendorStatementMail(
            $vendor,
            $bills,
            $bills->sum('total'),
            $bills->sum('paid_amount'),
            $bills->sum('remaining_amount'),
            $company->name,
            $asOf,
        ));

        return back()->with('success', 'Statement emailed to ' . $vendor->email);
    }

    public function bulkSendSelectedEmail(Request $request)
    {
        $validated = $request->validate([
            'vendor_ids' => 'required|array|min:1',
            'vendor_ids.*' => 'integer|exists:vendors,id',
            'as_of' => 'nullable|date',
        ]);

        $companyId = loggedCompanyId()['company_id'];
        $asOf = $validated['as_of'] ?? Carbon::today()->toDateString();
        $company = auth('web')->user()->company;
        $sent = 0;
        $skipped = 0;

        $vendors = Vendor::where('company_id', $companyId)
            ->whereIn('id', $validated['vendor_ids'])
            ->orderBy('name')
            ->get();

        foreach ($vendors as $vendor) {
            if (empty($vendor->email)) {
                $skipped++;
                continue;
            }

            $bills = Bill::where('company_id', $companyId)
                ->where('vendor_id', $vendor->id)
                ->where('approval_status', 'approved')
                ->whereDate('date', '<=', $asOf)
                ->where('remaining_amount', '>', 0)
                ->orderBy('date')
                ->get();

            if ($bills->isEmpty()) {
                $skipped++;
                continue;
            }

            Mail::to($vendor->email)->queue(new VendorStatementMail(
                $vendor,
                $bills,
                $bills->sum('total'),
                $bills->sum('paid_amount'),
                $bills->sum('remaining_amount'),
                $company->name,
                $asOf,
            ));
            $sent++;
        }

        return back()->with('success', "Selected vendor statements queued: {$sent} sent, {$skipped} skipped.");
    }

    private function validateStatementRequest(Request $request): void
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'as_of' => 'nullable|date',
            'invoice_no' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);
    }

    private function statementFilters(Request $request): array
    {
        $toDate = $request->get('to_date', $request->get('as_of', Carbon::today()->toDateString()));

        return [
            'from_date' => $request->filled('from_date') ? $request->get('from_date') : null,
            'to_date' => $toDate,
            'invoice_no' => trim((string) $request->get('invoice_no', '')),
            'per_page' => max(5, min((int) $request->get('per_page', 20), 100)),
        ];
    }

    private function statementQuery(int $companyId, Vendor $vendor, array $filters)
    {
        return Bill::where('company_id', $companyId)
            ->where('vendor_id', $vendor->id)
            ->where('approval_status', 'approved')
            ->when($filters['from_date'] ?? null, fn($query, $date) => $query->whereDate('date', '>=', $date))
            ->when($filters['to_date'] ?? null, fn($query, $date) => $query->whereDate('date', '<=', $date))
            ->when($filters['invoice_no'] ?? '', function ($query, $invoiceNo) {
                $query->where(function ($subQuery) use ($invoiceNo) {
                    $subQuery->where('invoice_no', 'like', '%' . $invoiceNo . '%')
                        ->orWhere('unique_no', 'like', '%' . $invoiceNo . '%');
                });
            });
    }

    private function bulkStatementFilters(string $asOf): array
    {
        return [
            'from_date' => null,
            'to_date' => $asOf,
            'invoice_no' => '',
            'per_page' => 100,
        ];
    }

    private function statementData(Vendor $vendor, array $filters, string $asOf, int $companyId): array
    {
        $bills = $this->statementQuery($companyId, $vendor, $filters)
            ->orderBy('date')
            ->get();

        return [
            'vendor' => $vendor,
            'bills' => $bills,
            'asOf' => $asOf,
            'totalBilled' => $bills->sum('total'),
            'totalPaid' => $bills->sum('paid_amount'),
            'totalOutstanding' => $bills->sum('remaining_amount'),
            'company' => auth('web')->user()->company,
        ];
    }

    private function statementPdf(Vendor $vendor, array $filters, string $asOf, int $companyId)
    {
        return Pdf::loadView('pages.account.vendor-statement.pdf', $this->statementData(
            $vendor,
            $filters,
            $asOf,
            $companyId
        ))->setPaper('a4', 'portrait');
    }

    private function safeFilenamePart($value): string
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) $value);
        $filename = trim($filename, '._-');

        return $filename !== '' ? $filename : 'vendor';
    }

    public function bulkSendEmail(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];
        $asOf      = $request->get('as_of', Carbon::today()->toDateString());
        $company   = auth('web')->user()->company;

        $sent    = 0;
        $skipped = 0;

        Vendor::where('company_id', $companyId)
            ->whereHas('bills', fn($q) => $q->where('remaining_amount', '>', 0))
            ->whereNotNull('email')
            ->chunk(50, function ($vendors) use ($companyId, $asOf, $company, &$sent, &$skipped) {
                foreach ($vendors as $vendor) {
                    $bills = Bill::where('company_id', $companyId)
                        ->where('vendor_id', $vendor->id)
                        ->where('approval_status', 'approved')
                        ->whereDate('date', '<=', $asOf)
                        ->where('remaining_amount', '>', 0)
                        ->orderBy('date')
                        ->get();

                    if ($bills->isEmpty()) {
                        $skipped++;
                        continue;
                    }

                    Mail::to($vendor->email)->queue(new VendorStatementMail(
                        $vendor,
                        $bills,
                        $bills->sum('total'),
                        $bills->sum('paid_amount'),
                        $bills->sum('remaining_amount'),
                        $company->name,
                        $asOf,
                    ));
                    $sent++;
                }
            });

        return back()->with('success', "Statements queued: {$sent} sent, {$skipped} skipped (no outstanding).");
    }
}
