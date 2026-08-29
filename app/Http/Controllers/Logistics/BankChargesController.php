<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsBankCharge;
use App\Models\LogisticsLcPayment;
use App\Models\LogisticsLetterOfCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankChargesController extends Controller
{
    public function index(Request $request)
    {
        $query = LogisticsBankCharge::with('lc')->latest();

        if ($request->filled('charge_type')) {
            $query->where('charge_type', $request->charge_type);
        }
        if ($request->filled('bank')) {
            $query->where('bank', 'ilike', '%'.$request->bank.'%');
        }
        if ($request->filled('start_date')) {
            $query->whereDate('charge_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('charge_date', '<=', $request->end_date);
        }
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('charge_number', 'ilike', "%{$search}%")
                    ->orWhere('bank', 'ilike', "%{$search}%")
                    ->orWhere('linked_lc', 'ilike', "%{$search}%")
                    ->orWhere('bank_reference', 'ilike', "%{$search}%");
            });
        }

        $charges = $query->paginate(20)->withQueryString();

        $stats = [
            'total_pkr' => (float) LogisticsBankCharge::whereMonth('charge_date', now()->month)->sum('amount_pkr'),
            'lc_opening_pkr' => (float) LogisticsBankCharge::where('charge_type', 'lc_opening_commission')->whereMonth('charge_date', now()->month)->sum('amount_pkr'),
            'count' => LogisticsBankCharge::whereMonth('charge_date', now()->month)->count(),
            'avg_pkr' => (float) (LogisticsBankCharge::whereMonth('charge_date', now()->month)->avg('amount_pkr') ?? 0),
        ];

        $lcs = $this->bankInstruments();
        $banks = $this->banks();

        return view('pages.products.logistics.lc.bank-charges.index', compact('charges', 'stats', 'lcs', 'banks'));
    }

    public function create()
    {
        $lcs = $this->bankInstruments();
        $banks = $this->banks();

        return view('pages.products.logistics.lc.bank-charges.create', compact('lcs', 'banks'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        [$amountPkr, $amountUsd] = $this->convertedAmounts($data);

        DB::transaction(function () use ($data, $amountPkr, $amountUsd) {
            $instrument = filled($data['lc_id'] ?? null)
                ? LogisticsLetterOfCredit::find($data['lc_id'])
                : null;

            $charge = LogisticsBankCharge::create([
                ...$data,
                'charge_number' => LogisticsBankCharge::generateChargeNumber(),
                'linked_lc' => $instrument?->lc_number,
                'amount_pkr' => $amountPkr,
                'amount_usd' => $amountUsd,
                'company_id' => auth()->check() ? (int) auth()->user()->company_id ?: null : null,
            ]);

            $this->syncPayment($charge);
        });

        return response()->json(['success' => true, 'message' => 'Bank charge recorded successfully.']);
    }

    public function show($id)
    {
        $charge = LogisticsBankCharge::with('lc')->findOrFail($id);

        return view('pages.products.logistics.lc.bank-charges.show', compact('charge'));
    }

    public function edit($id)
    {
        $charge = LogisticsBankCharge::findOrFail($id);
        $lcs = $this->bankInstruments();
        $banks = $this->banks($charge->bank);

        return view('pages.products.logistics.lc.bank-charges.edit', compact('charge', 'lcs', 'banks'));
    }

    public function update(Request $request, $id)
    {
        $charge = LogisticsBankCharge::findOrFail($id);

        $data = $this->validatedData($request);
        [$amountPkr, $amountUsd] = $this->convertedAmounts($data);

        DB::transaction(function () use ($charge, $data, $amountPkr, $amountUsd) {
            $instrument = filled($data['lc_id'] ?? null)
                ? LogisticsLetterOfCredit::find($data['lc_id'])
                : null;

            $charge->update([
                ...$data,
                'linked_lc' => $instrument?->lc_number,
                'amount_pkr' => $amountPkr,
                'amount_usd' => $amountUsd,
            ]);

            $this->syncPayment($charge);
        });

        return response()->json(['success' => true, 'message' => 'Bank charge updated successfully.']);
    }

    public function destroy($id)
    {
        $charge = LogisticsBankCharge::findOrFail($id);

        DB::transaction(function () use ($charge) {
            $charge->payment()->delete();
            $charge->delete();
        });

        return response()->json(['success' => true]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'charge_type' => 'required|string|max:255',
            'bank' => 'required|string|max:255',
            'lc_id' => 'nullable|exists:logistics_letters_of_credit,id',
            'charge_date' => 'required|date',
            'charge_currency' => 'required|in:PKR,USD',
            'amount_pkr' => 'nullable|required_if:charge_currency,PKR|numeric|min:0',
            'amount_usd' => 'nullable|required_if:charge_currency,USD|numeric|min:0',
            'exchange_rate' => 'required|numeric|gt:0',
            'payment_mode' => 'nullable|in:debit_account,pay_order,cash,offset,third_party',
            'bank_reference' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
        ]);
    }

    private function convertedAmounts(array $data): array
    {
        $exchangeRate = (float) $data['exchange_rate'];

        if ($data['charge_currency'] === 'USD') {
            $amountUsd = round((float) $data['amount_usd'], 2);

            return [round($amountUsd * $exchangeRate, 2), $amountUsd];
        }

        $amountPkr = round((float) $data['amount_pkr'], 2);

        return [$amountPkr, round($amountPkr / $exchangeRate, 2)];
    }

    private function syncPayment(LogisticsBankCharge $charge): void
    {
        $charge->load('lc');
        $currency = $charge->charge_currency === 'USD' ? 'USD' : 'PKR';
        $amount = $currency === 'USD' ? $charge->amount_usd : $charge->amount_pkr;
        $details = "Bank Charge - {$charge->charge_number}: {$charge->charge_type_label} at {$charge->bank_name}";

        if ($charge->linked_lc) {
            $details .= " ({$charge->linked_lc})";
        }

        LogisticsLcPayment::updateOrCreate(
            ['bank_charge_id' => $charge->id],
            [
                'booking_id' => $charge->lc?->booking_id,
                'date' => $charge->charge_date,
                'details' => $details,
                'amount' => $amount,
                'currency' => $currency,
                'currency_value' => $charge->exchange_rate,
                'payment_type' => 'product_payment',
                'entry_source' => 'bank_charge',
                'remarks' => collect([
                    $charge->payment_mode
                        ? 'Payment Mode: '.ucwords(str_replace('_', ' ', $charge->payment_mode))
                        : null,
                    $charge->remarks,
                ])->filter()->implode(' | '),
                'status' => 'completed',
                'receipt_no' => $charge->bank_reference ?: $charge->charge_number,
                'company_id' => $charge->company_id,
            ]
        );
    }

    private function bankInstruments()
    {
        return LogisticsLetterOfCredit::orderBy('lc_number')
            ->get(['id', 'lc_number', 'beneficiary', 'record_type', 'booking_id']);
    }

    private function banks(?string $selectedBank = null): array
    {
        $banks = config('logistics.banks', []);

        if ($selectedBank && ! array_key_exists($selectedBank, $banks)) {
            $banks[$selectedBank] = ucwords(str_replace('_', ' ', $selectedBank));
        }

        return $banks;
    }
}
