<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\LiabilityPaymentAllocation;
use App\Models\LiabilityPaymentBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LiabilityPaymentController extends Controller
{
    public function index(Request $request)
    {
        $liabilityAccounts = $this->payableLiabilityAccounts()->get();

        $accountIds = $liabilityAccounts->pluck('id');

        // Liability outstanding balance = credits less debits. Payments posted in
        // later periods must reduce the balance, so group by account, not source period.
        $query = Transaction::whereIn('account_id', $accountIds)
            ->select(
                'account_id',
                DB::raw('SUM(credit) - SUM(debit) as balance')
            )
            ->groupBy('account_id')
            ->havingRaw('SUM(credit) - SUM(debit) > 0');

        if ($request->account_id) {
            $query->where('account_id', $request->account_id);
        }

        $balances = $query->get();

        $groupedLiabilities = [];
        foreach ($balances as $row) {
            $account = $liabilityAccounts->find($row->account_id);

            if (!$account) continue;

            $groupedLiabilities[$row->account_id][] = [
                'account' => $account,
                'amount' => $row->balance,
                'id' => (string) $row->account_id,
            ];
        }

        $bankAccounts = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])
            ->where('type', 'Account')
            ->where('sub_type', 'BANK')
            ->get();

        $data = [
            'groupedLiabilities' => $groupedLiabilities,
            'liabilityAccounts' => $liabilityAccounts,
            'bankAccounts' => $bankAccounts,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.liabilities.partials.table', $data)->render(),
            ]);
        }

        return view('pages.account.liabilities.index', $data);
    }

    public function history(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];
        $filters = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'account_id' => ['nullable', 'integer', Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId)],
            'bank_account_id' => ['nullable', 'integer', Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId)],
            'status' => ['nullable', Rule::in(['posted', 'reversed'])],
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $search = strtolower(trim((string) ($filters['search'] ?? '')));
        $query = LiabilityPaymentBatch::where('company_id', $companyId)
            ->with(['bankAccount', 'allocations.account'])
            ->when($filters['from_date'] ?? null, fn ($builder, $date) => $builder->whereDate('payment_date', '>=', $date))
            ->when($filters['to_date'] ?? null, fn ($builder, $date) => $builder->whereDate('payment_date', '<=', $date))
            ->when($filters['account_id'] ?? null, fn ($builder, $accountId) => $builder->whereHas('allocations', fn ($allocation) => $allocation->where('account_id', $accountId)))
            ->when($filters['bank_account_id'] ?? null, fn ($builder, $bankAccountId) => $builder->where('bank_account_id', $bankAccountId))
            ->when($filters['status'] ?? null, fn ($builder, $status) => $builder->where('status', $status))
            ->when($search !== '', function ($builder) use ($search) {
                $term = '%' . $search . '%';
                $builder->where(function ($searchQuery) use ($term) {
                    $searchQuery->whereRaw('LOWER(reference) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(COALESCE(external_reference, ?)) LIKE ?', ['', $term])
                        ->orWhereRaw('LOWER(COALESCE(payee, ?)) LIKE ?', ['', $term])
                        ->orWhereHas('bankAccount', fn ($account) => $account->whereRaw('LOWER(name) LIKE ?', [$term]))
                        ->orWhereHas('allocations.account', fn ($account) => $account->whereRaw('LOWER(name) LIKE ?', [$term]));
                });
            });

        $summary = [
            'records' => (clone $query)->count(),
            'posted_amount' => (clone $query)->where('status', 'posted')->sum('total_amount'),
            'reversed_amount' => (clone $query)->where('status', 'reversed')->sum('total_amount'),
        ];

        $payments = $query->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 20))
            ->withQueryString();

        $historyAccountIds = LiabilityPaymentAllocation::where('company_id', $companyId)
            ->distinct()
            ->pluck('account_id');
        $liabilityAccounts = ChartOfAccount::forCompany($companyId)
            ->whereIn('id', $historyAccountIds)
            ->orderBy('name')
            ->get();
        $bankAccounts = ChartOfAccount::forCompany($companyId)
            ->whereIn('id', LiabilityPaymentBatch::where('company_id', $companyId)->distinct()->pluck('bank_account_id'))
            ->orderBy('name')
            ->get();

        return view('pages.account.liabilities.history', compact(
            'payments',
            'summary',
            'liabilityAccounts',
            'bankAccounts'
        ));
    }

    public function downloadAttachment($id)
    {
        $payment = LiabilityPaymentBatch::where('company_id', loggedCompanyId()['company_id'])->findOrFail($id);

        abort_unless($payment->attachment && Storage::disk('local')->exists($payment->attachment), 404);

        $extension = pathinfo($payment->attachment, PATHINFO_EXTENSION);

        return Storage::disk('local')->download(
            $payment->attachment,
            'liability-payment-' . $payment->reference . ($extension ? '.' . $extension : '')
        );
    }
    public function store(Request $request)
    {
        $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*.account_id' => ['required', 'integer', Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'allocations.*.amount' => 'required|numeric|min:0.01',
            'bank_account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')
                    ->where('company_id', loggedCompanyId()['company_id'])
                    ->where('type', 'Account')
                    ->where('sub_type', 'BANK'),
            ],
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'payee' => 'required|string|max:255',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        try {
            DB::beginTransaction();

            $authId = auth('web')->id() ?: 1;
            $now = now();
            $companyId = loggedCompanyId()['company_id'];
            $paymentDate = $request->payment_date;
            $period = AccountingPeriod::findPeriodForDate($paymentDate, $companyId);
            $bankAccount = ChartOfAccount::forCompany($companyId)
                ->where('type', 'Account')
                ->where('sub_type', 'BANK')
                ->find($request->bank_account_id);

            if (!$period) {
                throw new Exception("The payment date does not fall within any unlocked accounting period.");
            }

            if (!$bankAccount) {
                throw new Exception("Please select a valid bank or cash account for the payment.");
            }

            $uniqueNo = 'LIAB-PAY-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(5)));
            $totalBatchAmount = 0;
            $transactions = [];
            $journalEntries = [];
            $liabilityAccountIds = $this->payableLiabilityAccounts()
                ->whereIn('id', collect($request->allocations)->pluck('account_id'))
                ->pluck('id');

            // PostgreSQL does not allow FOR UPDATE on aggregate/GROUP BY queries.
            // Lock the payable account rows first so concurrent payments for the
            // same liability are serialized, then calculate the current balances.
            ChartOfAccount::forCompany($companyId)
                ->whereIn('id', $liabilityAccountIds)
                ->lockForUpdate()
                ->get(['id']);

            $balances = Transaction::withoutGlobalScopes()->where('company_id', $companyId)->whereIn('account_id', $liabilityAccountIds)
                ->where('status', 1)->whereNull('deleted_at')
                ->select('account_id', DB::raw('SUM(credit) - SUM(debit) as balance'))
                ->groupBy('account_id')
                ->havingRaw('SUM(credit) - SUM(debit) > 0')
                ->get()
                ->keyBy('account_id');

            $requested = collect($request->allocations)->keyBy('account_id');
            foreach ($liabilityAccountIds as $accountId) {
                $liabilityAccount = ChartOfAccount::forCompany($companyId)->find($accountId);
                $amount = (float) ($requested->get($accountId)['amount'] ?? 0);
                $available = (float) ($balances->get($accountId)?->balance ?? 0);

                if (!$liabilityAccount || $amount <= 0) continue;
                if ($amount > $available + 0.0001) throw new Exception("Payment for {$liabilityAccount->name} exceeds its current outstanding balance.");

                $totalBatchAmount += $amount;
                $description = "Liability Payment: " . $liabilityAccount->name;

                // Debit Liability Account
                $commonData = [
                    'company_id' => $companyId,
                    'accounting_period_id' => $period->id,
                    'date' => $paymentDate,
                    'unique_no' => $uniqueNo,
                    // 'reference_id' => null,
                    'created_by' => $authId,
                    'updated_by' => $authId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $transactions[] = array_merge($commonData, [
                    'account_id' => $accountId,
                    'description' => $description . ($request->reference ? " ({$request->reference})" : ""),
                    'type' => 'Liability Payment',
                    'debit' => $amount,
                    'credit' => 0,
                    'status' => 1,
                ]);

                $journalEntries[] = array_merge($commonData, [
                    'account_id' => $accountId,
                    'narration' => $description,
                    'description' => $request->reference ?: "Liability Payment",
                    'debit' => $amount,
                    'credit' => 0,
                ]);
            }

            if ($totalBatchAmount > 0) {
                $batch = \App\Models\LiabilityPaymentBatch::create([
                    'company_id' => $companyId,
                    'bank_account_id' => $bankAccount->id,
                    'reference' => $uniqueNo,
                    'payment_date' => $paymentDate,
                    'total_amount' => $totalBatchAmount,
                    'payee' => $request->payee,
                    'external_reference' => $request->reference,
                    'attachment' => $request->hasFile('attachment') ? $request->file('attachment')->store("private/liability-payments/{$companyId}", 'local') : null,
                    'created_by' => $authId,
                ]);
                // Credit Bank Account
                $transactions[] = array_merge($commonData, [
                    'account_id' => $request->bank_account_id,
                    'description' => "Liability Payment Batch" . ($request->reference ? " ({$request->reference})" : ""),
                    'type' => 'Liability Payment',
                    'debit' => 0,
                    'credit' => $totalBatchAmount,
                    'status' => 1,
                ]);

                $journalEntries[] = array_merge($commonData, [
                    'account_id' => $request->bank_account_id,
                    'narration' => "Credit Bank for Liability Payment",
                    'description' => $request->reference ?: "Liability Payment",
                    'debit' => 0,
                    'credit' => $totalBatchAmount,
                ]);

                Transaction::insert($transactions);
                JournalEntry::insert($journalEntries);
                foreach ($liabilityAccountIds as $accountId) {
                    $amount = (float) ($requested->get($accountId)['amount'] ?? 0);
                    if ($amount <= 0) continue;
                    \App\Models\LiabilityPaymentAllocation::create(['company_id' => $companyId, 'liability_payment_batch_id' => $batch->id, 'account_id' => $accountId, 'source_reference' => 'ACCOUNT-BALANCE-' . $accountId, 'source_type' => 'account_balance', 'amount' => $amount]);
                }
            } else {
                Log::info("No outstanding liability balance found for the selected accounts.");
                throw new Exception("No outstanding liability balance found for the selected accounts.");
            }

            DB::commit();
            Log::info("Liability payment recorded successfully.");
            return redirect()->route('account.liabilities.index')
                ->with('success', 'Liability payment recorded successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::info("Liability payment failed: " . $e->getMessage());
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    private function payableLiabilityAccounts()
    {
        return ChartOfAccount::forCompany(loggedCompanyId()['company_id'])
            ->where('category', 'Liabilities')
            ->where('type', 'Account')
            ->where(function ($query) {
                $query->whereNull('system_account_type')
                    ->orWhereNotIn('system_account_type', [
                        'ACCOUNTS_PAYABLE',
                        'SALES_TAX',
                        'PURCHASE_TAX',
                    ]);
            })
            ->where('name', 'NOT LIKE', '%Accounts Payable%')
            ->where('name', 'NOT LIKE', '%Sales Tax%')
            ->where('name', 'NOT LIKE', '%Income Tax Payable%')
            ->where('name', 'NOT LIKE', '%Output Tax%')
            ->where('name', 'NOT LIKE', '%VAT Output%')
            ->where('name', 'NOT LIKE', '%GST Output%');
    }
}
