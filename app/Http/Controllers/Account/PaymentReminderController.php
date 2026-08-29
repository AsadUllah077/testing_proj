<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Mail\OverdueInvoiceReminderMail;
use App\Models\Sale;
use App\Services\Account\SmartReminderService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PaymentReminderController extends Controller
{
    /**
     * Dashboard: show overdue invoices bucketed by days overdue.
     */
    public function index(Request $request)
    {
        $this->validateReminderFilters($request);

        $companyId = loggedCompanyId()['company_id'];
        $today     = Carbon::today();
        $dueOffset = 30; // Net-30 terms
        $filters = $this->reminderFilters($request);

        // Aggregate statistics for the summary
        $baseQuery = Sale::where('company_id', $companyId)
            ->where('remaining_amount', '>', 0)
            ->whereDate('due_date', '<', $today);

        $this->applyReminderFilters($baseQuery, $filters);

        $totalOverdue = (clone $baseQuery)->sum('remaining_amount');
        $overdueCount = (clone $baseQuery)->count();

        // Separate bucket labels based on days overdue
        $bucketConfig = [
            '1–30 days'  => ['min' => 1,  'max' => 30,  'page_param' => 'p1'],
            '31–60 days' => ['min' => 31, 'max' => 60,  'page_param' => 'p2'],
            '61–90 days' => ['min' => 61, 'max' => 90,  'page_param' => 'p3'],
            '90+ days'   => ['min' => 91, 'max' => 9999, 'page_param' => 'p4'],
        ];

        $perPage = $filters['per_page'];

        $bucketsData = [];
        foreach ($bucketConfig as $label => $range) {
            $minDate = $today->copy()->subDays($range['max'])->toDateString();
            $maxDate = $today->copy()->subDays($range['min'])->toDateString();

            // Fetch a limited sample (e.g., top 100) for UI display per bucket
            // for very large datasets, pagination per bucket is better, but this solves the memory crash.
            $query = Sale::with([
                'customer:id,name,email',
            ])
                ->where('company_id', $companyId)
                ->where('remaining_amount', '>', 0)
                ->whereBetween('due_date', [$minDate, $maxDate])
                ->orderBy('due_date');

            $this->applyReminderFilters($query, $filters);

            $invoices = $query->paginate($perPage, ['*'], $range['page_param'])
                ->withQueryString();

            // Store calculated but not fetched info
            foreach ($invoices as $invoice) {
                $dueDate               = Carbon::parse($invoice->due_date ?? Carbon::parse($invoice->date)->addDays($dueOffset));
                $invoice->days_overdue = (int) $dueDate->diffInDays($today);
                $invoice->due_date     = $dueDate;
            }

            $bucketsData[$label] = ['paginator' => $invoices, 'page_param' => $range['page_param']];
        }

        $buckets = collect($bucketsData);

        // AI customer segmentation for smart reminders.
        $allCustomerIds = collect($bucketsData)->flatMap(
            fn($b) => $b['paginator']->getCollection()->pluck('customer_id')
        )->unique()->filter()->values()->all();

        $segments = app(SmartReminderService::class)->segmentCustomers($allCustomerIds);

        // Scheduler config (read from console.php schedule definition)
        $schedulerConfig = [
            'thresholds'   => '7, 14, 30',
            'time'         => '09:00 daily',
            'log'          => storage_path('logs/overdue-reminders.log'),
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.payment-reminder._content', compact('buckets', 'totalOverdue', 'overdueCount', 'schedulerConfig', 'segments', 'filters'))->render(),
            ]);
        }

        return view('pages.account.payment-reminder.index', compact(
            'buckets',
            'totalOverdue',
            'overdueCount',
            'schedulerConfig',
            'segments',
            'filters'
        ));
    }

    /**
     * Send a reminder email for a single invoice.
     */
    public function sendOne(Request $request, Sale $sale)
    {
        $sale->load(['customer', 'company']);

        if ($sale->approval_status !== 'approved') {
            return back()->with('error', 'Only approved invoices can receive payment reminders.');
        }

        if ($sale->remaining_amount <= 0) {
            return back()->with('error', 'Invoice #' . $sale->invoice_no . ' is already fully paid.');
        }

        if (empty($sale->customer->email)) {
            return back()->with('error', 'Customer ' . $sale->customer->name . ' has no email address.');
        }

        $dueDate     = Carbon::parse($sale->due_date ?? Carbon::parse($sale->date)->addDays(30));
        $daysOverdue = max(0, (int) $dueDate->diffInDays(Carbon::today()));
        $companyName = optional($sale->company)->name ?? config('app.name');

        // Smart tone based on customer payment history.
        $segment = app(SmartReminderService::class)->segmentCustomer($sale->customer_id);

        Mail::to($sale->customer->email)
            ->queue(new OverdueInvoiceReminderMail($sale, $daysOverdue, $companyName, $segment['tone'], $segment['subject_prefix']));

        return back()->with('success', 'Reminder (' . $segment['segment'] . ' tone) sent to ' . $sale->customer->email . ' for Invoice #' . $sale->invoice_no . '.');
    }

    /**
     * Bulk-send reminders to all overdue invoices in a bucket.
     */
    public function sendBucket(Request $request)
    {
        $request->validate([
            'min_days' => 'required|integer|min:1',
            'max_days' => 'required|integer',
            'customer_id' => 'nullable|integer',
            'invoice_no' => 'nullable|string|max:100',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0|gte:min_amount',
        ]);

        $companyId = loggedCompanyId()['company_id'];
        $today     = Carbon::today();
        $minDate   = $today->copy()->subDays((int) $request->max_days)->toDateString();
        $maxDate   = $today->copy()->subDays((int) $request->min_days)->toDateString();
        $filters = $this->reminderFilters($request);

        $countQuery = Sale::where('company_id', $companyId)
            ->where('remaining_amount', '>', 0)
            ->whereBetween('due_date', [$minDate, $maxDate]);

        $this->applyReminderFilters($countQuery, $filters);

        $invoicesCount = $countQuery->count();

        if ($invoicesCount === 0) {
            return back()->with('error', 'No invoices found in this range.');
        }

        // Pre-compute segments for all customers in this bucket.
        $customerQuery = Sale::where('company_id', $companyId)
            ->where('remaining_amount', '>', 0)
            ->whereBetween('due_date', [$minDate, $maxDate]);

        $this->applyReminderFilters($customerQuery, $filters);

        $customerIds = $customerQuery->distinct()
            ->pluck('customer_id')
            ->filter()
            ->all();

        $segments = app(SmartReminderService::class)->segmentCustomers($customerIds);

        // Optimization: Use chunk() to ensure we get Model instances and handle large datasets
        $sendQuery = Sale::with(['customer', 'company'])
            ->where('company_id', $companyId)
            ->where('remaining_amount', '>', 0)
            ->whereBetween('due_date', [$minDate, $maxDate]);

        $this->applyReminderFilters($sendQuery, $filters);

        $sendQuery->chunk(100, function ($invoices) use ($today, $segments) {
                foreach ($invoices as $invoice) {
                    if (!empty($invoice->customer->email)) {
                        $daysOverdue = (int) Carbon::parse($invoice->due_date ?? Carbon::parse($invoice->date)->addDays(30))->diffInDays($today);
                        $companyName = optional($invoice->company)->name ?? config('app.name');

                        $seg = $segments[$invoice->customer_id] ?? ['tone' => 'neutral', 'subject_prefix' => 'Payment Reminder'];

                        Mail::to($invoice->customer->email)
                            ->queue(new OverdueInvoiceReminderMail($invoice, $daysOverdue, $companyName, $seg['tone'], $seg['subject_prefix']));
                    }
                }
            });

        return back()->with('success', "Reminders queued for {$invoicesCount} potential invoices (skipped if no email).");
    }

    private function validateReminderFilters(Request $request): void
    {
        $request->validate([
            'customer_id' => 'nullable|integer',
            'invoice_no' => 'nullable|string|max:100',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0|gte:min_amount',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);
    }

    private function reminderFilters(Request $request): array
    {
        return [
            'customer_id' => $request->filled('customer_id') ? (int) $request->customer_id : null,
            'invoice_no' => trim((string) $request->get('invoice_no', '')),
            'from_date' => $request->filled('from_date') ? $request->get('from_date') : null,
            'to_date' => $request->filled('to_date') ? $request->get('to_date') : null,
            'min_amount' => $request->filled('min_amount') ? (float) $request->min_amount : null,
            'max_amount' => $request->filled('max_amount') ? (float) $request->max_amount : null,
            'per_page' => max(5, min((int) $request->get('per_page', 20), 100)),
        ];
    }

    private function applyReminderFilters($query, array $filters): void
    {
        $query
            ->where('approval_status', 'approved')
            ->when($filters['customer_id'] ?? null, fn($q, $customerId) => $q->where('customer_id', $customerId))
            ->when($filters['invoice_no'] ?? '', function ($q, $invoiceNo) {
                $q->where(function ($subQuery) use ($invoiceNo) {
                    $subQuery->where('invoice_no', 'like', '%' . $invoiceNo . '%')
                        ->orWhere('unique_no', 'like', '%' . $invoiceNo . '%');
                });
            })
            ->when($filters['from_date'] ?? null, fn($q, $date) => $q->whereDate('date', '>=', $date))
            ->when($filters['to_date'] ?? null, fn($q, $date) => $q->whereDate('date', '<=', $date))
            ->when($filters['min_amount'] ?? null, fn($q, $amount) => $q->where('remaining_amount', '>=', $amount))
            ->when($filters['max_amount'] ?? null, fn($q, $amount) => $q->where('remaining_amount', '<=', $amount));
    }
}
