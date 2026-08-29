<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\Bill;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\CustomerPaymentItem;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\Vendor;
use App\Models\VendorPaymentRun;
use App\Models\VendorPaymentRunItem;
use App\Support\SelectedRecordOrdering;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceAllocationController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    //  SALES (Customer Invoice Allocation)
    // ──────────────────────────────────────────────────────────────

    public function sales(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];

        $query = Sale::where('company_id', $companyId)
            ->with('customer')
            ->where('remaining_amount', '>', 0)
            ->where('approval_status', 'approved');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }
        if ($request->filled('search')) {
            $query->where('invoice_no', 'like', "%{$request->search}%");
        }

        $selectedSaleIds = SelectedRecordOrdering::ids($request, 'selected_sale_ids');

        $invoices     = SelectedRecordOrdering::apply($query, $selectedSaleIds)
            ->orderBy('date')
            ->paginate(200);
        $bankAccounts = $this->bankAccounts($companyId);

        return view('pages.account.invoice-allocation.sales', compact(
            'invoices', 'bankAccounts'
        ));
    }

    public function storeSales(Request $request)
    {
        $request->validate([
            'payment_date' => 'required|date',
            'account_id'   => [
                'required',
                \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')
                    ->where('company_id', loggedCompanyId()['company_id'])
                    ->where('type', 'Account')
                    ->where('sub_type', 'BANK'),
            ],
            'note'         => 'nullable|string|max:500',
            'sale_ids'     => 'required|array|min:1',
            'amounts'      => 'required|array',
        ]);

        $companyId = loggedCompanyId()['company_id'];

        DB::transaction(function () use ($request, $companyId) {
            $period = AccountingPeriod::findPeriodForDate($request->payment_date, $companyId);
            if (!$period) {
                throw new \Exception("No open accounting period found for date: {$request->payment_date}");
            }

            $userId = Auth::id();
            $arId   = ChartOfAccount::getAccountsReceivableId($companyId);

            // Load all selected invoices and group by customer
            $sales = Sale::where('company_id', $companyId)
                ->where('approval_status', 'approved')
                ->whereIn('id', $request->sale_ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $grouped = collect($request->sale_ids)
                ->groupBy(fn($id) => $sales[$id]->customer_id ?? null)
                ->filter();

            foreach ($grouped as $customerId => $saleIds) {
                $paymentRef  = CustomerPayment::nextReference($companyId);
                $totalAmount = 0;

                $customerPayment = CustomerPayment::create([
                    'company_id'   => $companyId,
                    'customer_id'  => $customerId,
                    'account_id'   => $request->account_id,
                    'reference_no' => $paymentRef,
                    'payment_date' => $request->payment_date,
                    'note'         => $request->note,
                    'status'       => 'confirmed',
                    'confirmed_at' => Carbon::now(),
                    'confirmed_by' => $userId,
                    'created_by'   => $userId,
                    'total_amount' => 0,
                ]);

                foreach ($saleIds as $saleId) {
                    $amount = (float) ($request->amounts[$saleId] ?? 0);
                    if ($amount <= 0) continue;

                    $sale   = $sales[$saleId];
                    $amount = min($amount, $sale->remaining_amount);
                    $ledgerAmount = round((float) $amount * (float) ($sale->exchange_rate ?: 1), 4);

                    CustomerPaymentItem::create([
                        'customer_payment_id' => $customerPayment->id,
                        'sale_id'             => $sale->id,
                        'amount_to_apply'     => $amount,
                        'invoice_total'       => $sale->total_after_discount,
                        'invoice_remaining'   => $sale->remaining_amount,
                    ]);

                    $sale->remaining_amount -= $amount;
                    $sale->paid_amount      += $amount;
                    if ($sale->remaining_amount <= 0) {
                        $sale->remaining_amount = 0;
                    }
                    $sale->payment_status = app(\App\Services\SaleService::class)->paymentStatus(
                        (float) $sale->paid_amount,
                        (float) $sale->remaining_amount
                    );
                    $sale->save();

                    // DR Bank / Cash (money received)
                    Transaction::create([
                        'unique_no'            => $paymentRef,
                        'date'                 => $request->payment_date,
                        'account_id'           => $request->account_id,
                        'debit'                => $ledgerAmount,
                        'credit'               => 0,
                        'description'          => "Payment allocation — Invoice #{$sale->invoice_no}",
                        'accounting_period_id' => $period->id,
                        'customer_id'          => $sale->customer_id,
                        'reference_id'         => $sale->id,
                        'invoice_no'           => $sale->invoice_no,
                        'type'                 => 'Sale',
                        'status'               => 1,
                        'posted_by'            => $userId,
                        'posted_at'            => now(),
                        'company_id'           => $companyId,
                        'created_by'           => $userId,
                        'updated_by'           => $userId,
                    ]);

                    // CR Accounts Receivable (AR reduced)
                    Transaction::create([
                        'unique_no'            => $paymentRef,
                        'date'                 => $request->payment_date,
                        'account_id'           => $arId,
                        'debit'                => 0,
                        'credit'               => $ledgerAmount,
                        'description'          => "Payment allocation — Invoice #{$sale->invoice_no}",
                        'accounting_period_id' => $period->id,
                        'customer_id'          => $sale->customer_id,
                        'reference_id'         => $sale->id,
                        'invoice_no'           => $sale->invoice_no,
                        'type'                 => 'Sale',
                        'status'               => 1,
                        'posted_by'            => $userId,
                        'posted_at'            => now(),
                        'company_id'           => $companyId,
                        'created_by'           => $userId,
                        'updated_by'           => $userId,
                    ]);

                    $totalAmount += $amount;
                }

                $customerPayment->update(['total_amount' => $totalAmount]);
            }
        });

        return redirect()->route('account.invoice-allocation.sales')
            ->with('success', 'Customer invoice payments recorded successfully.');
    }

    // ──────────────────────────────────────────────────────────────
    //  BILLS (Vendor Invoice Allocation)
    // ──────────────────────────────────────────────────────────────

    public function bills(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];

        $query = Bill::where('company_id', $companyId)
            ->with('vendor')
            ->where('remaining_amount', '>', 0)
            ->where('approval_status', 'approved');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('invoice_no', 'like', "%{$request->search}%")
                  ->orWhere('unique_no', 'like', "%{$request->search}%");
            });
        }

        $selectedBillIds = SelectedRecordOrdering::ids($request, 'selected_bill_ids');

        $bills        = SelectedRecordOrdering::apply($query, $selectedBillIds)
            ->orderBy('date')
            ->paginate(200);
        $bankAccounts = $this->bankAccounts($companyId);

        return view('pages.account.invoice-allocation.bills', compact(
            'bills', 'bankAccounts'
        ));
    }

    public function storeBills(Request $request)
    {
        $request->validate([
            'payment_date' => 'required|date',
            'account_id'   => [
                'required',
                \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')
                    ->where('company_id', loggedCompanyId()['company_id'])
                    ->where('type', 'Account')
                    ->where('sub_type', 'BANK'),
            ],
            'note'         => 'nullable|string|max:500',
            'bill_ids'     => 'required|array|min:1',
            'amounts'      => 'required|array',
        ]);

        $companyId = loggedCompanyId()['company_id'];

        DB::transaction(function () use ($request, $companyId) {
            $period = AccountingPeriod::findPeriodForDate($request->payment_date, $companyId);
            if (!$period) {
                throw new \Exception("No open accounting period found for date: {$request->payment_date}");
            }

            $userId = Auth::id();
            $apId   = ChartOfAccount::getAccountsPayableId($companyId);

            $bills = Bill::where('company_id', $companyId)
                ->where('approval_status', 'approved')
                ->whereIn('id', $request->bill_ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Create one VendorPaymentRun to keep history consistent
            $runRef      = VendorPaymentRun::nextReference($companyId);
            $totalAmount = 0;

            $run = VendorPaymentRun::create([
                'company_id'   => $companyId,
                'run_reference'=> $runRef,
                'payment_date' => $request->payment_date,
                'account_id'   => $request->account_id,
                'notes'        => $request->note,
                'status'       => 'processed',
                'confirmed_by' => $userId,
                'confirmed_at' => Carbon::now(),
                'created_by'   => $userId,
                'total_amount' => 0,
            ]);

            foreach ($request->bill_ids as $billId) {
                $amount = (float) ($request->amounts[$billId] ?? 0);
                if ($amount <= 0) continue;

                $bill   = $bills[$billId];
                $amount = min($amount, $bill->remaining_amount);

                VendorPaymentRunItem::create([
                    'vendor_payment_run_id' => $run->id,
                    'bill_id'               => $bill->id,
                    'vendor_id'             => $bill->vendor_id,
                    'amount_to_pay'         => $amount,
                    'bill_total'            => $bill->total,
                    'bill_remaining'        => $bill->remaining_amount,
                ]);

                $bill->remaining_amount -= $amount;
                $bill->paid_amount      += $amount;
                if ($bill->remaining_amount <= 0) $bill->remaining_amount = 0;
                $bill->payment_status = app(\App\Services\BillService::class)->paymentStatus((float) $bill->paid_amount, (float) $bill->remaining_amount);
                $bill->save();

                $ledgerAmount = round((float) $amount * (float) ($bill->exchange_rate ?: 1), 4);
                $postedAt = now();

                // CR Bank / Cash (money paid out)
                Transaction::create([
                    'unique_no'            => $runRef,
                    'date'                 => $request->payment_date,
                    'account_id'           => $request->account_id,
                    'debit'                => 0,
                    'credit'               => $ledgerAmount,
                    'description'          => "Bill payment allocation — #{$bill->invoice_no}",
                    'accounting_period_id' => $period->id,
                    'vendor_id'            => $bill->vendor_id,
                    'reference_id'         => $bill->id,
                    'invoice_no'           => $bill->invoice_no,
                    'type'                 => 'BillPayment',
                    'company_id'           => $companyId,
                    'payable_type'         => 'vendor',
                    'payable_id'           => $bill->vendor_id,
                    'created_by'           => $userId,
                    'updated_by'           => $userId,
                    'status'               => 1,
                    'posted_by'            => $userId,
                    'posted_at'            => $postedAt,
                ]);

                // DR Accounts Payable (liability reduced)
                Transaction::create([
                    'unique_no'            => $runRef,
                    'date'                 => $request->payment_date,
                    'account_id'           => $apId,
                    'debit'                => $ledgerAmount,
                    'credit'               => 0,
                    'description'          => "Bill payment allocation — #{$bill->invoice_no}",
                    'accounting_period_id' => $period->id,
                    'vendor_id'            => $bill->vendor_id,
                    'reference_id'         => $bill->id,
                    'invoice_no'           => $bill->invoice_no,
                    'type'                 => 'BillPayment',
                    'company_id'           => $companyId,
                    'payable_type'         => 'vendor',
                    'payable_id'           => $bill->vendor_id,
                    'created_by'           => $userId,
                    'updated_by'           => $userId,
                    'status'               => 1,
                    'posted_by'            => $userId,
                    'posted_at'            => $postedAt,
                ]);

                $totalAmount += $amount;
            }

            $run->update(['total_amount' => $totalAmount]);
        });

        return redirect()->route('account.invoice-allocation.bills')
            ->with('success', 'Vendor bill payments recorded successfully.');
    }

    // ──────────────────────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────────────────────

    private function bankAccounts($companyId)
    {
        return ChartOfAccount::forCompany($companyId)
            ->where('type', 'Account')
            ->where('sub_type', 'BANK')
            ->orderBy('account_number')
            ->get();
    }
}
