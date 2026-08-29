<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\TaxPeriod;
use App\Models\ChartOfAccount;
use App\Services\TaxService;
use Illuminate\Http\Request;
use Exception;

class TaxPaymentController extends Controller
{
    protected $taxService;

    public function __construct(TaxService $taxService)
    {
        $this->taxService = $taxService;
    }

    public function create($id)
    {
        $taxPeriod = TaxPeriod::findOrFail($id);

        if ($taxPeriod->status !== 'settled') {
            return redirect()->back()->with('error', 'Tax period must be settled before payment.');
        }

        $bankAccounts = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])
            ->where('type', 'Account')
            ->where('sub_type', 'BANK')
            ->get();

        return view('pages.account.tax.payments.create', compact('taxPeriod', 'bankAccounts'));
    }

    public function store(Request $request, $id)
    {
        $request->validate([
            'bank_account_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')
                    ->where('company_id', loggedCompanyId()['company_id'])
                    ->where('type', 'Account')
                    ->where('sub_type', 'BANK'),
            ],
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        try {
            $taxPeriod = TaxPeriod::findOrFail($id);
            if ($taxPeriod->status !== 'settled') return redirect()->back()->with('error', 'Only a settled tax period can receive a payment/refund.');

            // Check if overpayment
            $paidAmount = $taxPeriod->paid_amount ?? 0;
            $remainingAmount = abs($taxPeriod->net_amount) - $paidAmount;
            if ($request->amount > $remainingAmount) {
                return redirect()->back()->with('error', 'Payment amount cannot exceed remaining balance.')->withInput();
            }

            $payment = $this->taxService->recordPartialPayment(
                $taxPeriod,
                $request->bank_account_id,
                $request->payment_date,
                $request->amount,
                $request->reference
            );

            if ($request->hasFile('attachment')) {
                $payment->update(['attachment' => $request->file('attachment')->store("private/tax-payments/{$taxPeriod->company_id}/{$taxPeriod->id}", 'local')]);
            }

            return redirect()->route('account.tax-periods.index')
                ->with('success', 'Tax payment recorded successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }
}
