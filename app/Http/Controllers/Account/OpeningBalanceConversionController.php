<?php

namespace App\Http\Controllers\Account;

use App\Exports\OpeningBalanceConversionTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\OpeningBalanceBatchRequest;
use App\Models\ChartOfAccount;
use App\Models\OpeningBalanceBatch;
use App\Models\Transaction;
use App\Services\Account\OpeningBalanceConversionService;
use App\Services\Account\OpeningBalanceImportService;
use App\Services\Account\OpeningBalanceReconciliationService;
use App\Support\AccountingConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use App\Support\OpeningBalanceAccountEligibility;
use Maatwebsite\Excel\Facades\Excel;

class OpeningBalanceConversionController extends Controller
{
    public function __construct(
        private readonly OpeningBalanceConversionService $conversionService,
        private readonly OpeningBalanceImportService $importService
    ) {
    }

    public function index()
    {
        $companyId = loggedCompanyId()['company_id'];
        $batches = OpeningBalanceBatch::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25);
        $period = \App\Models\AccountingPeriod::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByDesc('start_date')
            ->first();
        $defaultConversionDate = $period?->start_date
            ? CarbonImmutable::parse($period->start_date)->subDay()->toDateString()
            : null;
        $defaultClosingDate = $defaultConversionDate;
        $defaultGoLiveDate = $defaultClosingDate ? CarbonImmutable::parse($defaultClosingDate)->addDay()->toDateString() : null;
        $currentConversionSets = OpeningBalanceBatch::where('company_id', $companyId)
            ->when($defaultClosingDate, fn ($query) => $query->whereDate('closing_date', $defaultClosingDate))
            ->where('status', AccountingConstants::BATCH_STATUS_POSTED)
            ->whereIn('batch_kind', ['conversion', 'revision'])
            ->orderBy('id')
            ->get();
        $currentConversionBatch = $currentConversionSets->count() === 1 ? $currentConversionSets->first() : null;
        $legacyConversionConflictCount = $currentConversionSets->count() > 1 ? $currentConversionSets->count() : 0;
        $openConversionRevision = OpeningBalanceBatch::where('company_id', $companyId)
            ->when($defaultClosingDate, fn ($query) => $query->whereDate('closing_date', $defaultClosingDate))
            ->where('batch_kind', 'revision')
            ->whereIn('status', [
                AccountingConstants::BATCH_STATUS_DRAFT,
                AccountingConstants::BATCH_STATUS_VALIDATED,
                AccountingConstants::BATCH_STATUS_APPROVED,
            ])
            ->latest('id')
            ->first();
        $legacyOpeningBalances = Transaction::withoutGlobalScopes()
            ->from('transactions as transactions')
            ->join('chart_of_accounts as accounts', 'accounts.id', '=', 'transactions.account_id')
            ->where('transactions.company_id', $companyId)
            ->where('transactions.status', 1)
            ->whereNull('transactions.deleted_at')
            ->whereNull('transactions.opening_balance_batch_id')
            ->where(function ($query) {
                $query->where('transactions.is_opening_balance', true)
                    ->orWhere('transactions.type', AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE);
            })
            ->groupBy('transactions.account_id', 'accounts.account_number', 'accounts.name', 'accounts.category', 'accounts.system_account_type')
            ->selectRaw('transactions.account_id, accounts.account_number, accounts.name, accounts.category, accounts.system_account_type, MIN(transactions.date) AS first_date, MAX(transactions.date) AS last_date, SUM(transactions.debit) AS total_debit, SUM(transactions.credit) AS total_credit, SUM(transactions.debit - transactions.credit) AS net_debit')
            ->orderBy('accounts.account_number')
            ->get();
        $templateAccounts = ChartOfAccount::query()
            ->forCompany($companyId)
            ->orderBy('account_number')
            ->orderBy('id')
            ->get(['id', 'account_number', 'name', 'category', 'type', 'system_account_type']);
        $templateEligibleCount = $templateAccounts
            ->filter(fn (ChartOfAccount $account) => OpeningBalanceConversionTemplateExport::isEligible($account))
            ->count();
        $templateIneligibleCount = $templateAccounts->count() - $templateEligibleCount;

        return view('pages.account.conversion.opening-balances.index', compact('batches', 'defaultClosingDate', 'defaultConversionDate', 'defaultGoLiveDate', 'legacyOpeningBalances', 'templateAccounts', 'templateEligibleCount', 'templateIneligibleCount', 'currentConversionBatch', 'legacyConversionConflictCount', 'openConversionRevision'));
    }

    public function create()
    {
        $companyId = loggedCompanyId()['company_id'];
        $postedSets = OpeningBalanceBatch::where('company_id', $companyId)
            ->where('status', AccountingConstants::BATCH_STATUS_POSTED)
            ->whereIn('batch_kind', ['conversion', 'revision'])
            ->orderBy('id')
            ->get();
        if ($postedSets->count() === 1) {
            return redirect()->route('account.conversion.opening-balances.show', $postedSets->first())
                ->with('info', 'Conversion balances already exist. Use Continue / Revise Conversion Balances to enter remaining accounts or correct targets.');
        }
        if ($postedSets->count() > 1) {
            return redirect()->route('account.conversion.opening-balances.index')
                ->withErrors(['status' => $postedSets->count() . ' legacy Posted conversion sets require an approved consolidation repair before balances can be continued safely.']);
        }
        $accounts = $this->postingAccounts($companyId);
        $period = \App\Models\AccountingPeriod::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByDesc('start_date')
            ->first();
        $openingDate = $period?->start_date
            ? CarbonImmutable::parse($period->start_date)->subDay()->toDateString()
            : null;
        $resolvedPeriod = $openingDate
            ? \App\Models\AccountingPeriod::query()
                ->where('company_id', $companyId)
                ->whereDate('start_date', '<=', $openingDate)
                ->whereDate('end_date', '>=', $openingDate)
                ->first()
            : null;
        $batch = new OpeningBalanceBatch([
            'source_system' => 'XERO',
            'closing_date' => $openingDate,
            'conversion_date' => $openingDate,
            'go_live_date' => $openingDate ? CarbonImmutable::parse($openingDate)->addDay()->toDateString() : null,
            'fiscal_year' => $resolvedPeriod?->name,
            'reference' => 'XERO-OB-' . $companyId . '-' . now()->format('YmdHis'),
            'status' => AccountingConstants::BATCH_STATUS_DRAFT,
            'batch_kind' => 'conversion',
        ]);

        return view('pages.account.conversion.opening-balances.form', compact('batch', 'accounts'));
    }

    public function store(OpeningBalanceBatchRequest $request)
    {
        $companyId = loggedCompanyId()['company_id'];
        $data = $request->validated();
        if (OpeningBalanceBatch::where('company_id', $companyId)
            ->where('status', AccountingConstants::BATCH_STATUS_POSTED)
            ->whereIn('batch_kind', ['conversion', 'revision'])
            ->exists()) {
            throw ValidationException::withMessages([
                'closing_date' => 'This company already has conversion balances. Continue the existing conversion balance set instead of creating another batch.',
            ]);
        }

        DB::transaction(function () use ($data, $companyId) {
            $batch = OpeningBalanceBatch::create([
                'company_id' => $companyId,
                'reference' => $data['reference'],
                'idempotency_key' => (string) Str::uuid(),
                'source_system' => $data['source_system'],
                'closing_date' => $data['closing_date'],
                'conversion_date' => $data['conversion_date'],
                'go_live_date' => $data['go_live_date'] ?? CarbonImmutable::parse($data['closing_date'])->addDay()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'status' => AccountingConstants::BATCH_STATUS_DRAFT,
                'batch_kind' => 'conversion',
                'created_by' => auth('web')->id(),
                'updated_by' => auth('web')->id(),
            ]);

            $this->syncLines($batch, $data['lines'], $companyId);
            $this->conversionService->recalculateTotals($batch);
        });

        return redirect()->route('account.conversion.opening-balances.index')->with('success', 'Opening balance conversion draft saved.');
    }

    public function edit(OpeningBalanceBatch $openingBalanceBatch)
    {
        $this->ensureEditable($openingBalanceBatch);

        $companyId = loggedCompanyId()['company_id'];
        $accounts = $this->postingAccounts($companyId);
        $batch = $openingBalanceBatch->load([
            'lines' => fn ($query) => $query->orderBy('opening_balance_lines.id'),
        ]);
        $reconciliation = app(OpeningBalanceReconciliationService::class)->forBatch($batch);

        return view('pages.account.conversion.opening-balances.form', compact('batch', 'accounts', 'reconciliation'));
    }

    public function show(OpeningBalanceBatch $openingBalanceBatch)
    {
        $companyId = loggedCompanyId()['company_id'];
        abort_if((int) $openingBalanceBatch->company_id !== (int) $companyId, 404);

        $batch = $openingBalanceBatch->load([
            'lines' => fn ($query) => $query->orderBy('opening_balance_lines.id'),
            'lines.account',
            'transactions' => fn ($query) => $query
                ->orderBy('transactions.date')
                ->orderBy('transactions.id'),
            'transactions.account',
            'transactions.reversalOf',
        ]);

        $difference = round((float) $batch->total_debit - (float) $batch->total_credit, 4);
        $reconciliation = app(OpeningBalanceReconciliationService::class)->forBatch($batch);

        return view('pages.account.conversion.opening-balances.show', compact('batch', 'difference', 'reconciliation'));
    }

    public function update(OpeningBalanceBatchRequest $request, OpeningBalanceBatch $openingBalanceBatch)
    {
        $this->ensureEditable($openingBalanceBatch);

        $companyId = loggedCompanyId()['company_id'];
        $data = $request->validated();
        if ($openingBalanceBatch->supersedes_batch_id) {
            $original = $openingBalanceBatch->supersedes()->firstOrFail();
            $data['closing_date'] = $original->closing_date->toDateString();
            $data['conversion_date'] = $original->conversion_date->toDateString();
            $data['go_live_date'] = optional($original->go_live_date)->toDateString();
        }

        DB::transaction(function () use ($openingBalanceBatch, $data, $companyId) {
            $openingBalanceBatch->update([
                'reference' => $data['reference'],
                'source_system' => $data['source_system'],
                'closing_date' => $data['closing_date'],
                'conversion_date' => $data['conversion_date'],
                'go_live_date' => $data['go_live_date'] ?? CarbonImmutable::parse($data['closing_date'])->addDay()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'status' => AccountingConstants::BATCH_STATUS_DRAFT,
                'reviewed_by' => null,
                'approved_by' => null,
                'reviewed_at' => null,
                'approved_at' => null,
                'updated_by' => auth('web')->id(),
            ]);

            $openingBalanceBatch->lines()->delete();
            $this->syncLines($openingBalanceBatch, $data['lines'], $companyId);
            $this->conversionService->recalculateTotals($openingBalanceBatch);
        });

        return redirect()->route('account.conversion.opening-balances.index')->with('success', 'Opening balance conversion draft updated.');
    }

    public function validateBatch(OpeningBalanceBatch $openingBalanceBatch)
    {
        $this->ensureCompany($openingBalanceBatch);
        $this->conversionService->markValidated($openingBalanceBatch);

        return back()->with('success', 'Opening balance conversion validated.');
    }

    public function post(OpeningBalanceBatch $openingBalanceBatch)
    {
        $this->ensureCompany($openingBalanceBatch);
        $this->conversionService->post($openingBalanceBatch);

        return back()->with('success', 'Opening balance conversion posted.');
    }

    public function approve(OpeningBalanceBatch $openingBalanceBatch)
    {
        $this->ensureCompany($openingBalanceBatch);
        $this->conversionService->approve($openingBalanceBatch, (int) auth('web')->id());

        return back()->with('success', 'Opening balance conversion approved.');
    }

    public function revise(Request $request, OpeningBalanceBatch $openingBalanceBatch)
    {
        $this->ensureCompany($openingBalanceBatch);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $revision = $this->conversionService->createRevisionDraft($openingBalanceBatch, $data['reason'], (int) auth('web')->id());

        return redirect()->route('account.conversion.opening-balances.edit', $revision)
            ->with('success', 'The current conversion targets were copied. Add remaining accounts or replace incorrect balances, then validate, approve, and post the new version.');
    }

    public function reverse(Request $request, OpeningBalanceBatch $openingBalanceBatch)
    {
        $this->ensureCompany($openingBalanceBatch);
        $data = $request->validate([
            'reversal_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $this->conversionService->reverse($openingBalanceBatch, $data['reversal_date'], $data['reason']);

        return back()->with('success', 'Opening balance conversion reversed.');
    }

    public function correctPostingDate(Request $request, OpeningBalanceBatch $openingBalanceBatch)
    {
        $this->ensureCompany($openingBalanceBatch);
        $data = $request->validate([
            'target_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $replacement = $this->conversionService->correctPostingDate(
            $openingBalanceBatch,
            $data['target_date'],
            $data['reason'],
            (int) auth('web')->id()
        );

        return redirect()->route('account.conversion.opening-balances.show', $replacement)
            ->with('success', 'Posting date corrected. The original batch was reversed and all lines were copied automatically.');
    }

    public function copyReversedBatch(Request $request, OpeningBalanceBatch $openingBalanceBatch)
    {
        $this->ensureCompany($openingBalanceBatch);
        $data = $request->validate([
            'target_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $replacement = $this->conversionService->copyReversedBatchToDraft(
            $openingBalanceBatch,
            $data['target_date'],
            $data['reason'],
            (int) auth('web')->id()
        );

        return redirect()->route('account.conversion.opening-balances.edit', $replacement)
            ->with('success', 'All reversed batch lines were copied into a replacement Draft. Review, validate, approve, and post it.');
    }

    public function createLegacyAdjustment(Request $request, ChartOfAccount $account)
    {
        $companyId = (int) loggedCompanyId()['company_id'];
        abort_if((int) $account->company_id !== $companyId, 404);
        $data = $request->validate([
            'target_amount' => ['required', 'decimal:0,4', 'min:0'],
            'target_side' => ['required', Rule::in(['debit', 'credit'])],
            'posting_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        if ($account->type !== 'Account' || in_array($account->category, AccountingConstants::PROFIT_AND_LOSS_CATEGORIES, true)) {
            throw ValidationException::withMessages(['account' => 'This account cannot receive an opening-balance adjustment.']);
        }
        if (in_array($account->system_account_type, ['ACCOUNTS_RECEIVABLE', 'ACCOUNTS_PAYABLE', 'CURRENT_YEAR_EARNINGS'], true)) {
            throw ValidationException::withMessages(['account' => 'Use the appropriate subledger or closing workflow for this system account.']);
        }

        $postingDate = \App\Support\AccountingDate::fromIso($data['posting_date']);
        $period = app(\App\Services\Account\AccountingPeriodResolver::class)->resolve($companyId, $postingDate);
        $obe = ChartOfAccount::forCompany($companyId)
            ->where('system_account_type', AccountingConstants::SYSTEM_OPENING_BALANCE_EQUITY)
            ->where('account_number', '399')
            ->first();
        if (! $obe || $obe->is($account)) {
            throw ValidationException::withMessages(['account' => 'The designated account 399 Opening Balance Equity is missing or cannot adjust itself.']);
        }

        $current = (string) Transaction::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('account_id', $account->id)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('is_opening_balance', true)
                    ->orWhere('type', AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE);
            })
            ->selectRaw('COALESCE(SUM(debit - credit), 0) AS balance')
            ->value('balance');
        $latestOpeningDate = Transaction::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('account_id', $account->id)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('is_opening_balance', true)
                    ->orWhere('type', AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE);
            })
            ->max('date');
        if ($latestOpeningDate && $postingDate < CarbonImmutable::parse($latestOpeningDate)->toDateString()) {
            throw ValidationException::withMessages(['posting_date' => 'The adjustment date cannot be earlier than the latest posted opening journal date.']);
        }
        $target = \App\Support\DecimalAmount::normalize($data['target_amount']);
        $signedTarget = $data['target_side'] === AccountingConstants::OPENING_SIDE_DEBIT ? $target : bcsub('0', $target, 4);
        $delta = bcsub($signedTarget, $current, 4);
        if (bccomp($delta, '0', 4) === 0) {
            throw ValidationException::withMessages(['target_amount' => 'The target already equals the effective posted opening balance.']);
        }

        $batch = DB::transaction(function () use ($companyId, $account, $obe, $postingDate, $period, $data, $delta) {
            $batch = OpeningBalanceBatch::create([
                'company_id' => $companyId,
                'reference' => 'ADJ-OB-' . $companyId . '-A' . $account->id . '-' . now()->format('YmdHis'),
                'idempotency_key' => (string) Str::uuid(),
                'source_system' => 'LEGACY_OPENING_ADJUSTMENT',
                'closing_date' => $postingDate,
                'conversion_date' => $postingDate,
                'fiscal_year' => $period->name,
                'notes' => $data['reason'],
                'status' => AccountingConstants::BATCH_STATUS_DRAFT,
                'batch_kind' => 'adjustment',
                'created_by' => auth('web')->id(),
                'updated_by' => auth('web')->id(),
            ]);
            $amount = ltrim($delta, '-');
            $isDebit = bccomp($delta, '0', 4) === 1;
            $batch->lines()->createMany([
                [
                    'company_id' => $companyId,
                    'account_id' => $account->id,
                    'debit' => $isDebit ? $amount : '0.0000',
                    'credit' => $isDebit ? '0.0000' : $amount,
                    'description' => 'Controlled adjustment to target opening balance - ' . $account->name,
                    'source_reference' => 'LEGACY_ACCOUNT:' . $account->id,
                ],
                [
                    'company_id' => $companyId,
                    'account_id' => $obe->id,
                    'debit' => $isDebit ? '0.0000' : $amount,
                    'credit' => $isDebit ? $amount : '0.0000',
                    'description' => 'Opening Balance Equity offset - ' . $account->name,
                    'source_reference' => 'LEGACY_ACCOUNT:' . $account->id,
                ],
            ]);
            $this->conversionService->recalculateTotals($batch);

            return $batch;
        });

        return redirect()->route('account.conversion.opening-balances.edit', $batch)
            ->with('success', 'Adjustment draft created. Review the delta lines, then validate, approve, and post it.');
    }

    public function downloadTemplate(Request $request)
    {
        $companyId = (int) loggedCompanyId()['company_id'];
        $data = $request->validate([
            'export_scope' => ['nullable', Rule::in(['all', 'selected'])],
            'eligibility_scope' => ['nullable', Rule::in(['eligible', 'ineligible', 'both'])],
            'account_ids' => ['required_if:export_scope,selected', 'array', 'min:1'],
            'account_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId),
            ],
        ]);
        $selectedAccountIds = ($data['export_scope'] ?? 'all') === 'selected'
            ? array_map('intval', $data['account_ids'])
            : [];
        $scopeName = $selectedAccountIds ? 'selected-accounts' : 'all-accounts';
        $eligibilityScope = $data['eligibility_scope'] ?? 'eligible';

        return Excel::download(
            new OpeningBalanceConversionTemplateExport($companyId, $selectedAccountIds, $eligibilityScope),
            'opening-balance-template-company-' . $companyId . '-' . $scopeName . '-' . $eligibilityScope . '.xlsx'
        );
    }

    public function import(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];
        $data = $request->validate([
            'reference' => [
                'required',
                'string',
                'max:100',
                Rule::unique('opening_balance_batches', 'reference')->where('company_id', $companyId),
            ],
            'source_system' => ['required', 'string', 'max:50'],
            'closing_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:conversion_date'],
            'conversion_date' => ['required', 'date_format:Y-m-d'],
            'go_live_date' => ['nullable', 'date_format:Y-m-d', 'after:closing_date'],
            'notes' => ['nullable', 'string'],
            'import_file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ]);

        $postedSetCount = OpeningBalanceBatch::where('company_id', $companyId)
            ->whereDate('closing_date', $data['closing_date'])
            ->where('status', AccountingConstants::BATCH_STATUS_POSTED)
            ->whereIn('batch_kind', ['conversion', 'revision'])
            ->count();
        if ($postedSetCount > 1) {
            throw ValidationException::withMessages([
                'import_file' => $postedSetCount . ' legacy Posted conversion sets exist for this balance date. Complete an approved consolidation repair before importing revised targets.',
            ]);
        }

        $batch = $this->importService->importToDraft($request->file('import_file'), $data, $companyId);
        $targetBatch = $this->conversionService->mergeImportedDraftIntoCurrentSet($batch, (int) auth('web')->id());
        $merged = ! $targetBatch->is($batch);

        return redirect()
            ->route('account.conversion.opening-balances.edit', $targetBatch)
            ->with('success', $merged
                ? 'Imported balances were merged into the current conversion targets. Existing accounts were replaced and other targets were preserved.'
                : 'Opening balance import saved as Draft. Review the mapped lines, then validate and post.');
    }

    private function syncLines(OpeningBalanceBatch $batch, array $lines, int $companyId): void
    {
        foreach ($lines as $line) {
            $debit = \App\Support\DecimalAmount::normalize($line['debit'] ?? 0);
            $credit = \App\Support\DecimalAmount::normalize($line['credit'] ?? 0);

            if (\App\Support\DecimalAmount::isZero($debit) && \App\Support\DecimalAmount::isZero($credit)) {
                continue;
            }

            $batch->lines()->create([
                'company_id' => $companyId,
                'account_id' => $line['account_id'],
                'debit' => $debit,
                'credit' => $credit,
                'description' => $line['description'] ?? null,
                'source_account_code' => $line['source_account_code'] ?? null,
                'source_account_name' => $line['source_account_name'] ?? null,
                'source_reference' => $line['source_reference'] ?? null,
            ]);
        }
    }

    private function postingAccounts(int $companyId)
    {
        return ChartOfAccount::query()
            ->forCompany($companyId)
            ->where('type', 'Account')
            ->orderBy('account_number')
            ->get()
            ->each(function (ChartOfAccount $account) {
                $account->setAttribute('opening_balance_eligibility', OpeningBalanceAccountEligibility::label($account));
                $account->setAttribute('opening_balance_eligible', OpeningBalanceAccountEligibility::isEligible($account));
            });
    }

    private function ensureEditable(OpeningBalanceBatch $batch): void
    {
        $this->ensureCompany($batch);
        abort_if($batch->status === AccountingConstants::BATCH_STATUS_POSTED, 422, 'Posted opening balance conversions cannot be edited.');
        abort_if($batch->status === AccountingConstants::BATCH_STATUS_REVERSED, 422, 'Reversed opening balance conversions cannot be edited.');
        abort_if($batch->status === 'Merged', 422, 'This import staging batch was merged into a conversion revision and cannot be edited separately.');
    }

    private function ensureCompany(OpeningBalanceBatch $batch): void
    {
        abort_if((int) $batch->company_id !== (int) loggedCompanyId()['company_id'], 404);
    }
}
