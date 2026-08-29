<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\CustomerPayment;
use App\Models\Customer;
use App\Models\CustomerPaymentItem;
use App\Models\Sale;
use App\Models\SaleAuditLog;
use App\Models\Transaction;
use App\Services\SaleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;


class PartialPaymentController extends Controller
{
    public function __construct(private SaleService $saleService) {}

    /**
     * History list: all past CustomerPayment records.
     */
    public function index(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];

        $query = CustomerPayment::where('company_id', $companyId)
            ->with(['customer', 'account', 'createdBy']);

        // Date filter
        if ($request->has('from_date') && $request->from_date != '') {
            $query->where('payment_date', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date != '') {
            $query->where('payment_date', '<=', $request->to_date);
        }

        // Customer filter
        if ($request->has('customer_id') && $request->customer_id != '') {
            $query->where('customer_id', $request->customer_id);
        }

        // Invoice No filter
        if ($request->has('reference_no') && $request->reference_no != '') {
            $query->where('reference_no', 'like', "%{$request->reference_no}%");
        }

        $payments = $query->latest()
            ->paginate($request->per_page ?? 20);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.partial-payment.table', compact('payments'))->render(),
                'pagination' => $payments->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.account.partial-payment.index', compact('payments'));
    }

    /**
     * Show: select customer + pick invoices to allocate against. (Allocation Screen)
     */
    public function create(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];

        $customers = Customer::where('company_id', $companyId)
            ->whereHas('sales', fn($q) => $q->where('remaining_amount', '>', 0)->where('approval_status', 'approved'))
            ->orderBy('name')
            ->limit(500)
            ->get();

        $bankAccounts = ChartOfAccount::forCompany($companyId)
            ->where('type', 'Account')
            ->where('sub_type', 'BANK')
            ->get();

        $selectedCustomer = null;
        $openInvoices     = collect();

        if ($request->filled('customer_id')) {
            $selectedCustomer = Customer::where('company_id', $companyId)
                ->findOrFail($request->customer_id);

            $openInvoices = Sale::where('company_id', $companyId)
                ->where('customer_id', $selectedCustomer->id)
                ->where('remaining_amount', '>', 0)
                ->where('approval_status', 'approved')
                ->orderBy('date')
                ->get();
        }

        return view('pages.account.partial-payment.create', compact(
            'customers',
            'bankAccounts',
            'selectedCustomer',
            'openInvoices'
        ));
    }

    /**
     * Save a lump-sum payment allocation as a DRAFT.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'payment_date'   => 'required|date',
            'account_id'     => [
                'required',
                \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')
                    ->where('company_id', loggedCompanyId()['company_id'])
                    ->where('type', 'Account')
                    ->where('sub_type', 'BANK'),
            ],
            'description'    => 'nullable|string|max:500',
            'sale_ids'       => 'required|array|min:1',
            'sale_ids.*'     => 'required|exists:sales,id',
            'amounts'        => 'required|array',
            'amounts.*'      => 'required|numeric|min:0.01',
        ]);

        $companyId = loggedCompanyId()['company_id'];

        DB::beginTransaction();
        try {
            $userId  = Auth::id();

            // 1. Create the Master Payment Record in DRAFT status
            $paymentReference = CustomerPayment::nextReference($companyId);
            $customerPayment = CustomerPayment::create([
                'company_id'   => $companyId,
                'customer_id'  => $request->customer_id,
                'account_id'   => $request->account_id,
                'reference_no' => $paymentReference,
                'payment_date' => $request->payment_date,
                'note'         => $request->description,
                'total_amount' => 0, // Will update below
                'status'       => 'draft',
                'created_by'   => $userId,
            ]);

            $totalAllocated = 0;

            foreach ($request->sale_ids as $saleId) {
                $amount = (float) ($request->amounts[$saleId] ?? 0);
                if ($amount <= 0) continue;

                $sale = Sale::where('company_id', $companyId)
                    ->where('customer_id', $request->customer_id)
                    ->where('approval_status', 'approved')
                    ->findOrFail($saleId);

                // Basic validation (even for draft)
                if ($amount > $sale->remaining_amount) {
                    throw new \Exception("Amount {$amount} exceeds remaining balance for Invoice #{$sale->invoice_no}.");
                }

                // 2. Create the Payment Item Detail
                CustomerPaymentItem::create([
                    'customer_payment_id' => $customerPayment->id,
                    'sale_id'            => $sale->id,
                    'amount_to_apply'    => $amount,
                    'invoice_total'      => $sale->total_after_discount,
                    'invoice_remaining'  => $sale->remaining_amount,
                ]);

                $totalAllocated += $amount;
            }

            // Update final total on master record
            $customerPayment->update(['total_amount' => $totalAllocated]);

            DB::commit();

            return redirect()
                ->route('account.partial-payment.show', $customerPayment->id)
                ->with('success', 'Allocation saved as draft. Please review and confirm to finalize.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to save draft: ' . $e->getMessage());
        }
    }

    /**
     * Finalize a draft allocation: record transactions and update invoice balances.
     */
    public function confirm($id)
    {
        $companyId = loggedCompanyId()['company_id'];
        $customerPayment = CustomerPayment::where('company_id', $companyId)
            ->where('status', 'draft')
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            $period = $this->saleService->validateAccountingPeriod($customerPayment->payment_date, $customerPayment->company_id);
            $userId = Auth::id();
            $arId   = ChartOfAccount::getAccountsReceivableId($companyId);

            foreach ($customerPayment->items as $item) {
                $sale = Sale::whereKey($item->sale_id)->lockForUpdate()->firstOrFail();
                $amount = $item->amount_to_apply;

                if ($sale->approval_status !== 'approved') {
                    throw new \Exception("Invoice #{$sale->invoice_no} is not approved for payment.");
                }

                // Re-verify remaining amount in case of concurrent updates
                if ($amount > $sale->remaining_amount) {
                    throw new \Exception("Amount to apply ({$amount}) exceeds current remaining balance of Invoice #{$sale->invoice_no}.");
                }

                $oldRemaining = $sale->remaining_amount;
                $ledgerAmount = round((float) $amount * (float) ($sale->exchange_rate ?: 1), 4);

                // 1. Update Sale Balance
                $sale->remaining_amount -= $amount;
                $sale->paid_amount      += $amount;
                if ($sale->remaining_amount <= 0) {
                    $sale->remaining_amount = 0;
                }
                $sale->payment_status = $this->saleService->paymentStatus(
                    (float) $sale->paid_amount,
                    (float) $sale->remaining_amount
                );
                $sale->updated_by        = $userId;
                $sale->save();

                // 2. Create Transactions

                // Debit: bank/cash account
                Transaction::create([
                    'unique_no'           => $customerPayment->reference_no,
                    'date'                => $customerPayment->payment_date,
                    'account_id'          => $customerPayment->account_id,
                    'debit'               => $ledgerAmount,
                    'credit'              => 0,
                    'description'         => ($customerPayment->note ?? "Partial payment") . " — Invoice #{$sale->invoice_no}",
                    'accounting_period_id' => $period->id,
                    'customer_id'         => $sale->customer_id,
                    'reference_id'        => $sale->id,
                    'invoice_no'          => $sale->invoice_no,
                    'type'                => 'Sale',
                    'status'              => 1,
                    'posted_by'           => $userId,
                    'posted_at'           => now(),
                    'company_id'          => $companyId,
                    'created_by'          => $userId,
                    'updated_by'          => $userId,
                ]);

                // Credit: AR
                Transaction::create([
                    'unique_no'           => $customerPayment->reference_no,
                    'date'                => $customerPayment->payment_date,
                    'account_id'          => $arId,
                    'debit'               => 0,
                    'credit'              => $ledgerAmount,
                    'description'         => ($customerPayment->note ?? "Partial payment") . " — Invoice #{$sale->invoice_no}",
                    'accounting_period_id' => $period->id,
                    'customer_id'         => $sale->customer_id,
                    'reference_id'        => $sale->id,
                    'invoice_no'          => $sale->invoice_no,
                    'type'                => 'Sale',
                    'status'              => 1,
                    'posted_by'           => $userId,
                    'posted_at'           => now(),
                    'company_id'          => $companyId,
                    'created_by'          => $userId,
                    'updated_by'          => $userId,
                ]);

                // 3. Audit Log
                SaleAuditLog::create([
                    'sale_id'    => $sale->id,
                    'user_id'    => $userId,
                    'action'     => 'partial_payment_recorded',
                    'old_data'   => json_encode(['remaining_amount' => $oldRemaining]),
                    'new_data'   => json_encode([
                        'payment_amount'   => $amount,
                        'remaining_amount' => $sale->remaining_amount,
                        'payment_date'     => $customerPayment->payment_date,
                        'payment_reference' => $customerPayment->reference_no,
                    ]),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }

            // Mark as confirmed
            $customerPayment->update([
                'status'       => 'confirmed',
                'confirmed_by' => $userId,
                'confirmed_at' => Carbon::now(),
            ]);

            DB::commit();

            return redirect()
                ->route('account.partial-payment.show', $customerPayment->id)
                ->with('success', 'Customer payment allocation confirmed successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Confirmation failed: ' . $e->getMessage());
        }
    }

    /**
     * Show details of a specific payment allocation.
     */
    public function show(CustomerPayment $customerPayment)
    {
        $customerPayment->load(['items.sale', 'customer', 'account', 'createdBy', 'confirmedBy']);
        return view('pages.account.partial-payment.show', compact('customerPayment'));
    }

    /**
     * Generate a PDF receipt for the payment allocation.
     */
    public function downloadReceipt($id)
    {
        $companyId = loggedCompanyId()['company_id'];
        $customerPayment = CustomerPayment::where('company_id', $companyId)
            ->with(['items.sale', 'customer', 'account'])
            ->findOrFail($id);

        $company = Company::findOrFail($companyId);

        $pdf = Pdf::loadView('pages.account.partial-payment.receipt-pdf', compact(
            'customerPayment',
            'company'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('receipt-' . $customerPayment->reference_no . '.pdf');
    }
}
