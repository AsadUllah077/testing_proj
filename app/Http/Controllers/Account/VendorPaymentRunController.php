<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Vendor;
use App\Models\VendorPaymentRun;
use App\Models\VendorPaymentRunItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorPaymentRunController extends Controller
{
    public function index(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];

        $query = VendorPaymentRun::where('company_id', $companyId)
            ->with(['createdBy', 'account', 'items.vendor'])
            ->withCount('items');

        // Date filter
        if ($request->has('from_date') && $request->from_date != '') {
            $query->where('payment_date', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date != '') {
            $query->where('payment_date', '<=', $request->to_date);
        }

        // Invoice No filter
        if ($request->has('reference_no') && $request->reference_no != '') {
            $query->where('run_reference', 'like', "%{$request->reference_no}%");
        }

        // Vendor filter
        if ($request->has('vendor_id') && $request->vendor_id != '') {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('vendor_id', $request->vendor_id);
            });
        }

        $runs = $query->latest()
            ->paginate($request->per_page ?? 20);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.vendor-payment-run.table', compact('runs'))->render(),
                'pagination' => $runs->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.account.vendor-payment-run.index', compact('runs'));
    }

    /**
     * Show: select vendor + pick bills to pay. (Step-by-step Flow)
     */
    public function create(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];

        // Vendors with approved bills and remaining balances
        $vendors = Vendor::where('company_id', $companyId)
            ->whereHas('bills', fn($q) => $q->where('remaining_amount', '>', 0)->where('approval_status', 'approved'))
            ->orderBy('name')
            ->limit(500)
            ->get();

        $bankAccounts = \App\Models\ChartOfAccount::forCompany($companyId)
            ->where('type', 'Account')
            ->where('sub_type', 'BANK')
            ->get();

        $selectedVendor = null;
        $openBills      = collect();

        if ($request->filled('vendor_id')) {
            $selectedVendor = Vendor::where('company_id', $companyId)
                ->findOrFail($request->vendor_id);

            $openBills = Bill::where('company_id', $companyId)
                ->where('vendor_id', $selectedVendor->id)
                ->where('remaining_amount', '>', 0)
                ->where('approval_status', 'approved')
                ->orderBy('date')
                ->get();
        }

        return view('pages.account.vendor-payment-run.create', compact(
            'vendors',
            'bankAccounts',
            'selectedVendor',
            'openBills'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'account_id'   => [
                'required',
                \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')
                    ->where('company_id', loggedCompanyId()['company_id'])
                    ->where('type', 'Account')
                    ->where('sub_type', 'BANK'),
            ],
            'notes'        => 'nullable|string',
            'bills'        => 'required|array',
            'amounts'      => 'required|array',
        ]);

        $companyId = loggedCompanyId()['company_id'];

        try {
            DB::transaction(function () use ($request, $validated, $companyId) {
                $run = VendorPaymentRun::create([
                    'company_id'    => $companyId,
                    'run_reference' => VendorPaymentRun::nextReference($companyId),
                    'payment_date'  => $validated['payment_date'],
                    'account_id'    => $validated['account_id'],
                    'notes'         => $validated['notes'],
                    'status'        => 'draft',
                    'created_by'    => auth('web')->id(),
                ]);

                $total = 0;
                foreach ($request->bills as $billId) {
                    $bill = Bill::where('company_id', $companyId)
                        ->where('approval_status', 'approved')
                        ->findOrFail($billId);
                    $amount = (float) ($request->amounts[$billId] ?? 0);

                    if ($amount <= 0) {
                        throw new \Exception("Pay amount for " . ($bill->invoice_no ?: $bill->unique_no) . " must be greater than zero.");
                    }

                    $amount = min($amount, $bill->remaining_amount);

                    VendorPaymentRunItem::create([
                        'vendor_payment_run_id' => $run->id,
                        'bill_id'               => $billId,
                        'vendor_id'             => $bill->vendor_id,
                        'amount_to_pay'         => $amount,
                        'bill_total'            => $bill->total,
                        'bill_remaining'        => $bill->remaining_amount,
                    ]);

                    $total += $amount;
                }

                $run->update(['total_amount' => $total]);
            });
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('account.vendor-payment-run.index')
            ->with('success', 'Payment run created successfully.');
    }

    public function show(VendorPaymentRun $vendorPaymentRun)
    {
        $vendorPaymentRun->load(['items.bill.vendor', 'createdBy', 'confirmedBy']);

        return view('pages.account.vendor-payment-run.show', compact('vendorPaymentRun'));
    }

    public function confirm(VendorPaymentRun $vendorPaymentRun)
    {
        if ($vendorPaymentRun->status !== 'draft') {
            return back()->with('error', 'Only draft payment runs can be confirmed.');
        }

        DB::transaction(function () use ($vendorPaymentRun) {
            $vendorPaymentRun = VendorPaymentRun::whereKey($vendorPaymentRun->id)->lockForUpdate()->firstOrFail();
            if ($vendorPaymentRun->status !== 'draft') throw new \RuntimeException('This payment run is no longer a draft.');
            $vendorPaymentRun->update([
                'status'       => 'confirmed',
                'confirmed_by' => auth('web')->id(),
                'confirmed_at' => Carbon::now(),
            ]);

            $runReference = $vendorPaymentRun->run_reference;
            $userId       = auth('web')->id();
            $period       = \App\Models\AccountingPeriod::findPeriodForDate($vendorPaymentRun->payment_date, $vendorPaymentRun->company_id);

            if (!$period) {
                throw new \Exception("No open accounting period found for date: " . \Carbon\Carbon::parse($vendorPaymentRun->payment_date)->toDateString());
            }

            // Apply payments to each bill
            foreach ($vendorPaymentRun->items as $item) {
                $bill = Bill::whereKey($item->bill_id)->lockForUpdate()->firstOrFail();
                $paymentAmount = $item->amount_to_pay;

                if ($bill->approval_status !== 'approved') throw new \RuntimeException("Bill #{$bill->invoice_no} is not approved for payment.");
                if ((float) $paymentAmount > (float) $bill->remaining_amount) throw new \RuntimeException("Payment exceeds the remaining balance for Bill #{$bill->invoice_no}.");

                $newPaid      = $bill->paid_amount + $paymentAmount;
                $newRemaining = max(0, $bill->remaining_amount - $paymentAmount);

                $bill->update([
                    'paid_amount'      => $newPaid,
                    'remaining_amount' => $newRemaining,
                    'payment_status' => app(\App\Services\BillService::class)->paymentStatus((float) $newPaid, (float) $newRemaining),
                ]);

                // ── Create Transactions ──────────────────────────────────────────

                // 1. Credit Payment Account (Asset decreasing)
                $ledgerAmount = round((float) $paymentAmount * (float) ($bill->exchange_rate ?: 1), 4);
                $postedAt = now();
                \App\Models\Transaction::create([
                    'unique_no'            => $runReference,
                    'date'                 => $vendorPaymentRun->payment_date,
                    'account_id'           => $vendorPaymentRun->account_id,
                    'credit'               => $ledgerAmount,
                    'debit'                => 0,
                    'description'          => "Partial payment via Run #{$runReference} for Bill #{$bill->invoice_no}",
                    'accounting_period_id' => $period->id,
                    'vendor_id'            => $bill->vendor_id,
                    'reference_id'         => $bill->id,
                    'invoice_no'           => $bill->invoice_no,
                    'type'                 => 'BillPayment',
                    'payable_type'         => 'vendor',
                    'payable_id'           => $bill->vendor_id,
                    'created_by'           => $userId,
                    'updated_by'           => $userId,
                    'status'               => 1,
                    'posted_by'            => $userId,
                    'posted_at'            => $postedAt,
                ]);

                // 2. Debit Accounts Payable (Liability decreasing)
                \App\Models\Transaction::create([
                    'unique_no'            => $runReference,
                    'date'                 => $vendorPaymentRun->payment_date,
                    'account_id'           => \App\Models\ChartOfAccount::getAccountsPayableId($vendorPaymentRun->company_id),
                    'credit'               => 0,
                    'debit'                => $ledgerAmount,
                    'description'          => "Partial payment via Run #{$runReference} for Bill #{$bill->invoice_no}",
                    'accounting_period_id' => $period->id,
                    'vendor_id'            => $bill->vendor_id,
                    'reference_id'         => $bill->id,
                    'invoice_no'           => $bill->invoice_no,
                    'type'                 => 'BillPayment',
                    'payable_type'         => 'vendor',
                    'payable_id'           => $bill->vendor_id,
                    'created_by'           => $userId,
                    'updated_by'           => $userId,
                    'status'               => 1,
                    'posted_by'            => $userId,
                    'posted_at'            => $postedAt,
                ]);
            }

            $vendorPaymentRun->update(['status' => 'processed']);
        });

        return back()->with('success', 'Payment run #' . $vendorPaymentRun->run_reference . ' confirmed and applied to bills.');
    }

    public function destroy(VendorPaymentRun $vendorPaymentRun)
    {
        if ($vendorPaymentRun->status === 'processed') {
            return back()->with('error', 'Processed payment runs cannot be deleted.');
        }

        $vendorPaymentRun->delete();

        return redirect()->route('account.vendor-payment-run.index')
            ->with('success', 'Payment run deleted.');
    }

    public function remittancePdf(VendorPaymentRun $vendorPaymentRun)
    {
        $vendorPaymentRun->load(['items.bill.vendor', 'createdBy', 'confirmedBy']);
        $company = auth('web')->user()->company;

        // Group items by vendor for per-vendor remittance
        $byVendor = $vendorPaymentRun->items->groupBy('vendor_id');

        $pdf = Pdf::loadView('pages.account.vendor-payment-run.remittance-pdf', compact(
            'vendorPaymentRun',
            'byVendor',
            'company'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('remittance-' . $vendorPaymentRun->run_reference . '.pdf');
    }
}
