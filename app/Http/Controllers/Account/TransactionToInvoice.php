<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\PayRun;
use App\Models\Sale;
use App\Models\TaxPeriod;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransactionToInvoice extends Controller
{
    public function __invoke($unique_no)
    {
        try {
            $transaction = Transaction::where('unique_no', $unique_no)->first();

            switch ($transaction->type) {
                case 'Sale':
                    $saleId = Sale::where('unique_no', $unique_no)->first();
                    return to_route('account.sale-invoice.edit', $saleId->id);

                case 'Bill':
                    $billId = Bill::where('unique_no', $unique_no)->first();
                    return to_route('account.bill-invoice.edit', $billId->id);

                case 'Tax Payment':
                case 'Tax Settlement':
                    $taxPeriodId = TaxPeriod::where('unique_no', $unique_no)->first();
                    return to_route('account.tax-periods.show', $taxPeriodId->id);

                case 'Payroll':
                    $payRunId = PayRun::where('unique_no', $unique_no)->first();
                    return to_route('payroll.pay-runs.show', $payRunId->id);
            }
        } catch (\Throwable $e) {
            Log::error('transactionToInvoice failed', [
                'unique_no' => $unique_no,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Unable to open related transaction.');
        }
    }

    public function paginate(Request $request)
    {
        $search = $request->query('q', '');
        $page   = $request->query('page', 1);

        $query = Transaction::query()
            ->where('company_id', loggedCompanyId()['company_id'])
            ->whereNotNull('invoice_no')
            ->select('invoice_no')
            ->distinct();
        if ($search !== '') {
            $searchLower = strtolower($search);
            $query->whereRaw('LOWER(invoice_no) LIKE ?', ["%{$searchLower}%"]);
        }
        $invoices = $query
            ->orderBy('invoice_no')
            ->paginate(20, ['invoice_no'], 'page', $page);
        return response()->json([
            'data'       => $invoices->items(),
            'next_page'  => $invoices->nextPageUrl() ? $page + 1 : null,
        ]);
    }
}
