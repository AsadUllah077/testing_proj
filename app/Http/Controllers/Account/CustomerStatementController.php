<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Mail\CustomerStatementMail;
use App\Models\Customer;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Support\SelectedRecordOrdering;
use ZipArchive;

class CustomerStatementController extends Controller
{
    public function index(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];

        $query = Customer::where('company_id', $companyId)
            ->whereHas('sales', fn($q) => $q->where('approval_status', 'approved')->where('remaining_amount', '>', 0))
            ->withSum(['sales' => fn($q) => $q->where('approval_status', 'approved')->where('remaining_amount', '>', 0)], 'remaining_amount')
            ->withCount(['sales' => fn($q) => $q->where('approval_status', 'approved')->where('remaining_amount', '>', 0)]);

        // Customer filter
        if ($request->has('customer_id') && $request->customer_id != '') {
            $query->where('id', $request->customer_id);
        }

        $sumSubquery = "
            SELECT COALESCE(SUM(sales.remaining_amount), 0)
            FROM sales
            WHERE sales.customer_id = customers.id
            AND sales.remaining_amount > 0
            AND sales.approval_status = 'approved'
            AND sales.deleted_at IS NULL
        ";

        if ($request->filled('min_amount')) {
            $query->whereRaw("($sumSubquery) >= ?", [$request->min_amount]);
        }

        if ($request->filled('max_amount')) {
            $query->whereRaw("($sumSubquery) <= ?", [$request->max_amount]);
        }

        $selectedCustomerIds = SelectedRecordOrdering::ids($request, 'selected_customer_ids');

        $customers = SelectedRecordOrdering::apply($query, $selectedCustomerIds)
            ->orderByDesc('sales_sum_remaining_amount')
            ->paginate($request->per_page ?? 20);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.customer-statement.table', compact('customers'))->render(),
                'pagination' => $customers->appends(request()->query())->links()->toHtml(),
            ]);
        }

        $aging = $this->computeAgingBuckets($companyId);

        return view('pages.account.customer-statement.index', compact('customers', 'aging'));
    }

    /**
     * Aggregate outstanding receivables into aging buckets (due = invoice date + 30 days).
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

        $row = Sale::where('company_id', $companyId)
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

    public function show(Request $request, Customer $customer)
    {
        $this->validateStatementRequest($request);

        $companyId = loggedCompanyId()['company_id'];
        $filters = $this->statementFilters($request);
        $asOf = $filters['to_date'] ?? Carbon::today()->toDateString();

        $query = $this->statementQuery($companyId, $customer, $filters);
        $summaryInvoices = (clone $query)->get();

        $invoices = $query
            ->orderBy('date')
            ->paginate($filters['per_page'])
            ->appends($request->query());

        $totalBilled      = $summaryInvoices->sum('total');
        $totalPaid        = $summaryInvoices->sum('paid_amount');
        $totalOutstanding = $summaryInvoices->sum('remaining_amount');

        $overdueInvoices = $summaryInvoices->filter(
            fn($inv) => $inv->remaining_amount > 0
                && Carbon::parse($inv->due_date ?? Carbon::parse($inv->date)->addDays(30))->lt(Carbon::parse($asOf))
        );

        return view('pages.account.customer-statement.show', compact(
            'customer',
            'invoices',
            'asOf',
            'filters',
            'totalBilled',
            'totalPaid',
            'totalOutstanding',
            'overdueInvoices'
        ));
    }

    public function exportPdf(Request $request, Customer $customer)
    {
        $this->validateStatementRequest($request);

        $companyId = loggedCompanyId()['company_id'];
        $filters = $this->statementFilters($request);
        $asOf = $filters['to_date'] ?? Carbon::today()->toDateString();
        $pdf = $this->statementPdf($customer, $filters, $asOf, $companyId);

        return $pdf->download('statement-' . str($customer->name)->slug() . '-' . $asOf . '.pdf');
    }

    public function bulkPdf(Request $request)
    {
        $validated = $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'integer|exists:customers,id',
            'as_of' => 'nullable|date',
        ]);

        $companyId = loggedCompanyId()['company_id'];
        $asOf = $validated['as_of'] ?? Carbon::today()->toDateString();
        $filters = $this->bulkStatementFilters($asOf);

        $customers = Customer::where('company_id', $companyId)
            ->whereIn('id', $validated['customer_ids'])
            ->orderBy('name')
            ->get();

        if ($customers->isEmpty()) {
            return back()->with('error', 'No selected customers were found for this company.');
        }

        $statements = $customers
            ->map(fn($customer) => $this->statementData($customer, $filters, $asOf, $companyId))
            ->all();

        $pdf = Pdf::loadView('pages.account.customer-statement.bulk-pdf', compact('statements'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('customer_statements_' . now()->format('Ymd_His') . '.pdf');
    }

    public function bulkSeparatePdfs(Request $request)
    {
        $validated = $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'integer|exists:customers,id',
            'as_of' => 'nullable|date',
        ]);

        if (!class_exists(ZipArchive::class)) {
            return back()->with('error', 'ZIP extension is not enabled on this server.');
        }

        $companyId = loggedCompanyId()['company_id'];
        $asOf = $validated['as_of'] ?? Carbon::today()->toDateString();
        $filters = $this->bulkStatementFilters($asOf);

        $customers = Customer::where('company_id', $companyId)
            ->whereIn('id', $validated['customer_ids'])
            ->orderBy('name')
            ->get();

        if ($customers->isEmpty()) {
            return back()->with('error', 'No selected customers were found for this company.');
        }

        $tempDirectory = storage_path('app/temp/customer-statements');
        if (!is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0755, true);
        }

        $downloadName = 'customer_statements_' . now()->format('Ymd_His') . '.zip';
        $zipPath = $tempDirectory . DIRECTORY_SEPARATOR . Str::random(10) . '_' . $downloadName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Unable to create customer statement zip file.');
        }

        foreach ($customers as $customer) {
            $pdf = $this->statementPdf($customer, $filters, $asOf, $companyId);
            $fileName = 'statement_' . $this->safeFilenamePart($customer->name) . '_' . $customer->id . '_' . $asOf . '.pdf';
            $zip->addFromString($fileName, $pdf->output());
        }

        $zip->close();

        return response()->download($zipPath, $downloadName)->deleteFileAfterSend(true);
    }

    public function sendEmail(Request $request, Customer $customer)
    {
        $this->validateStatementRequest($request);

        $companyId = loggedCompanyId()['company_id'];
        $filters = $this->statementFilters($request);
        $asOf = $filters['to_date'] ?? Carbon::today()->toDateString();

        if (empty($customer->email)) {
            return back()->with('error', 'Customer ' . $customer->name . ' has no email address.');
        }

        $invoices = $this->statementQuery($companyId, $customer, $filters)
            ->where('remaining_amount', '>', 0)
            ->orderBy('date')
            ->get();

        if ($invoices->isEmpty()) {
            return back()->with('error', 'No outstanding invoices to send for ' . $customer->name . '.');
        }

        $company = auth('web')->user()->company;

        Mail::to($customer->email)->queue(new CustomerStatementMail(
            $customer,
            $invoices,
            $invoices->sum('total'),
            $invoices->sum('paid_amount'),
            $invoices->sum('remaining_amount'),
            $company->name,
            $asOf,
        ));

        return back()->with('success', 'Statement emailed to ' . $customer->email);
    }

    public function bulkSendSelectedEmail(Request $request)
    {
        $validated = $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'integer|exists:customers,id',
            'as_of' => 'nullable|date',
        ]);

        $companyId = loggedCompanyId()['company_id'];
        $asOf = $validated['as_of'] ?? Carbon::today()->toDateString();
        $company = auth('web')->user()->company;
        $sent = 0;
        $skipped = 0;

        $customers = Customer::where('company_id', $companyId)
            ->whereIn('id', $validated['customer_ids'])
            ->orderBy('name')
            ->get();

        foreach ($customers as $customer) {
            if (empty($customer->email)) {
                $skipped++;
                continue;
            }

            $invoices = Sale::where('company_id', $companyId)
                ->where('customer_id', $customer->id)
                ->where('approval_status', 'approved')
                ->whereDate('date', '<=', $asOf)
                ->where('remaining_amount', '>', 0)
                ->orderBy('date')
                ->get();

            if ($invoices->isEmpty()) {
                $skipped++;
                continue;
            }

            Mail::to($customer->email)->queue(new CustomerStatementMail(
                $customer,
                $invoices,
                $invoices->sum('total'),
                $invoices->sum('paid_amount'),
                $invoices->sum('remaining_amount'),
                $company->name,
                $asOf,
            ));
            $sent++;
        }

        return back()->with('success', "Selected customer statements queued: {$sent} sent, {$skipped} skipped.");
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

    private function statementQuery(int $companyId, Customer $customer, array $filters)
    {
        return Sale::where('company_id', $companyId)
            ->where('customer_id', $customer->id)
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

    private function statementData(Customer $customer, array $filters, string $asOf, int $companyId): array
    {
        $invoices = $this->statementQuery($companyId, $customer, $filters)
            ->orderBy('date')
            ->get();

        return [
            'customer' => $customer,
            'invoices' => $invoices,
            'asOf' => $asOf,
            'totalBilled' => $invoices->sum('total'),
            'totalPaid' => $invoices->sum('paid_amount'),
            'totalOutstanding' => $invoices->sum('remaining_amount'),
            'company' => auth('web')->user()->company,
        ];
    }

    private function statementPdf(Customer $customer, array $filters, string $asOf, int $companyId)
    {
        return Pdf::loadView('pages.account.customer-statement.pdf', $this->statementData(
            $customer,
            $filters,
            $asOf,
            $companyId
        ))->setPaper('a4', 'portrait');
    }

    private function safeFilenamePart($value): string
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) $value);
        $filename = trim($filename, '._-');

        return $filename !== '' ? $filename : 'customer';
    }

    public function bulkSendEmail(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];
        $asOf      = $request->get('as_of', Carbon::today()->toDateString());
        $company   = auth('web')->user()->company;

        $sent    = 0;
        $skipped = 0;

        // Optimization: Use chunk() to process customers in batches
        Customer::where('company_id', $companyId)
            ->whereHas('sales', fn($q) => $q->where('approval_status', 'approved')->where('remaining_amount', '>', 0))
            ->whereNotNull('email')
            ->chunk(50, function ($customers) use ($companyId, $asOf, $company, &$sent, &$skipped) {
                foreach ($customers as $customer) {
                    $invoices = Sale::where('company_id', $companyId)
                        ->where('customer_id', $customer->id)
                        ->where('approval_status', 'approved')
                        ->whereDate('date', '<=', $asOf)
                        ->where('remaining_amount', '>', 0)
                        ->orderBy('date')
                        ->get();

                    if ($invoices->isEmpty()) {
                        $skipped++;
                        continue;
                    }

                    Mail::to($customer->email)->queue(new CustomerStatementMail(
                        $customer,
                        $invoices,
                        $invoices->sum('total'),
                        $invoices->sum('paid_amount'),
                        $invoices->sum('remaining_amount'),
                        $company->name,
                        $asOf,
                    ));
                    $sent++;
                }
            });

        return back()->with('success', "Statements queued: {$sent} sent, {$skipped} skipped (no outstanding).");
    }
}
