<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\FixedAsset;
use App\Models\JournalEntry;
use App\Models\MonthEndAdjustment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MonthEndAdjustmentController extends Controller
{
    private const TYPES = [
        'accrual' => 'Expense / income accrual',
        'prepayment' => 'Prepayment release',
        'depreciation' => 'Fixed asset depreciation',
        'correction' => 'Accounting correction',
        'other' => 'Other adjustment',
    ];

    public function index(Request $request)
    {
        $periods = AccountingPeriod::latest('start_date')->get();
        $period = $request->filled('period_id') ? AccountingPeriod::findOrFail($request->integer('period_id')) : $periods->first();
        $adjustments = MonthEndAdjustment::with(['period', 'creator'])
            ->when($period, fn ($query) => $query->where('accounting_period_id', $period->id))
            ->latest('date')
            ->paginate(20)
            ->withQueryString();
        $depreciationSuggestions = $period ? $this->depreciationSuggestions($period) : collect();

        return view('pages.account.month-end-adjustments.index', compact('periods', 'period', 'adjustments', 'depreciationSuggestions'));
    }

    public function create(Request $request)
    {
        $periods = AccountingPeriod::where('is_active', true)->where('is_locked', false)->latest('start_date')->get();
        $period = $request->filled('period_id') ? $periods->firstWhere('id', $request->integer('period_id')) : $periods->first();
        $asset = $request->filled('fixed_asset_id')
            ? FixedAsset::with(['depreciationExpenseAccount', 'accumulatedDepreciationAccount'])->findOrFail($request->integer('fixed_asset_id'))
            : null;
        abort_if($asset && (int) $asset->company_id !== $this->companyId(), 404);
        $accounts = ChartOfAccount::forCompany($this->companyId())->orderBy('category')->orderBy('name')->get();
        $prefillLines = [];

        if ($asset && $period) {
            $amount = min($asset->monthlyDepreciation(), max(0, (float) $asset->current_book_value - (float) $asset->residual_value));
            $prefillLines = [
                ['account_id' => $asset->depreciation_expense_account_id, 'description' => 'Depreciation - ' . $asset->name, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $asset->accumulated_depreciation_account_id, 'description' => 'Accumulated depreciation - ' . $asset->name, 'debit' => 0, 'credit' => $amount],
            ];
        }

        return view('pages.account.month-end-adjustments.create', [
            'periods' => $periods,
            'period' => $period,
            'asset' => $asset,
            'accounts' => $accounts,
            'types' => self::TYPES,
            'prefillLines' => $prefillLines,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateAdjustment($request);
        $period = AccountingPeriod::where('is_active', true)->where('is_locked', false)->findOrFail($data['accounting_period_id']);
        $this->assertDateInPeriod($data['date'], $period);
        $lines = $this->normalizedLines($data['lines']);
        $this->assertBalanced($lines);
        $asset = $this->validateDepreciation($data['type'], $data['fixed_asset_id'] ?? null, $lines, $period->id);

        $adjustment = MonthEndAdjustment::create([
            'company_id' => $this->companyId(),
            'accounting_period_id' => $period->id,
            'fixed_asset_id' => $asset?->id,
            'unique_no' => $this->uniqueNo($data['date']),
            'type' => $data['type'],
            'date' => $data['date'],
            'narration' => $data['narration'],
            'lines' => $lines,
            'total_amount' => collect($lines)->sum('debit'),
            'status' => 'draft',
            'created_by' => auth('web')->id(),
        ]);

        return redirect()->route('account.month-end-adjustments.show', $adjustment)->with('success', 'Adjustment saved as draft. Review it before posting.');
    }

    public function show(MonthEndAdjustment $monthEndAdjustment)
    {
        abort_if((int) $monthEndAdjustment->company_id !== $this->companyId(), 404);
        $monthEndAdjustment->load(['period', 'fixedAsset']);
        $accountIds = collect($monthEndAdjustment->lines)->pluck('account_id');
        $accounts = ChartOfAccount::forCompany($this->companyId())->whereIn('id', $accountIds)->get()->keyBy('id');

        return view('pages.account.month-end-adjustments.show', compact('monthEndAdjustment', 'accounts'));
    }

    public function post(MonthEndAdjustment $monthEndAdjustment)
    {
        abort_if((int) $monthEndAdjustment->company_id !== $this->companyId(), 404);

        DB::transaction(function () use ($monthEndAdjustment) {
            $adjustment = MonthEndAdjustment::whereKey($monthEndAdjustment->id)->lockForUpdate()->firstOrFail();
            if ($adjustment->status !== 'draft') {
                throw ValidationException::withMessages(['adjustment' => 'Only draft adjustments can be posted.']);
            }

            $period = AccountingPeriod::whereKey($adjustment->accounting_period_id)
                ->where('is_active', true)
                ->where('is_locked', false)
                ->first();
            if (! $period) {
                throw ValidationException::withMessages(['period' => 'The accounting period is locked or inactive.']);
            }
            $this->assertDateInPeriod($adjustment->date->toDateString(), $period);
            $lines = $this->normalizedLines($adjustment->lines);
            $this->assertBalanced($lines);
            $this->validateDepreciation($adjustment->type, $adjustment->fixed_asset_id, $lines, $period->id, $adjustment->id);

            $now = now();
            foreach ($lines as $line) {
                $common = [
                    'company_id' => $this->companyId(),
                    'accounting_period_id' => $period->id,
                    'account_id' => $line['account_id'],
                    'unique_no' => $adjustment->unique_no,
                    'date' => $adjustment->date,
                    'description' => $line['description'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'created_by' => auth('web')->id(),
                    'updated_by' => auth('web')->id(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                JournalEntry::create($common + ['narration' => $adjustment->narration, 'manual_item' => self::TYPES[$adjustment->type] ?? 'Month-end adjustment']);
                Transaction::create($common + [
                    'type' => 'Month_End_Adjustment',
                    'source_system' => 'accounting',
                    'source_reference' => $adjustment->unique_no,
                    'status' => 1,
                ]);
            }

            if ($adjustment->type === 'depreciation' && $adjustment->fixed_asset_id) {
                $asset = FixedAsset::whereKey($adjustment->fixed_asset_id)->lockForUpdate()->firstOrFail();
                $amount = (float) $adjustment->total_amount;
                $newBookValue = max((float) $asset->residual_value, (float) $asset->current_book_value - $amount);
                $asset->update([
                    'current_book_value' => $newBookValue,
                    'accumulated_depreciation' => (float) $asset->accumulated_depreciation + $amount,
                    'last_depreciation_date' => $adjustment->date,
                    'status' => $newBookValue <= (float) $asset->residual_value ? 'fully_depreciated' : 'active',
                    'updated_by' => auth('web')->id(),
                ]);
            }

            $adjustment->update(['status' => 'posted', 'posted_by' => auth('web')->id(), 'posted_at' => now()]);
        });

        return back()->with('success', 'Month-end adjustment posted to the general ledger.');
    }

    public function destroy(MonthEndAdjustment $monthEndAdjustment)
    {
        abort_if((int) $monthEndAdjustment->company_id !== $this->companyId(), 404);
        if ($monthEndAdjustment->status !== 'draft') {
            return back()->with('error', 'Posted adjustments cannot be deleted. Use a reversing journal.');
        }
        $monthEndAdjustment->delete();

        return redirect()->route('account.month-end-adjustments.index')->with('success', 'Draft adjustment deleted.');
    }

    private function validateAdjustment(Request $request): array
    {
        return $request->validate([
            'accounting_period_id' => ['required', Rule::exists('accounting_periods', 'id')->where('company_id', $this->companyId())],
            'fixed_asset_id' => ['nullable', Rule::exists('fixed_assets', 'id')->where('company_id', $this->companyId())],
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'date' => 'required|date',
            'narration' => 'required|string|max:255',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => ['required', Rule::exists('chart_of_accounts', 'id')->where('company_id', $this->companyId())],
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);
    }

    private function normalizedLines(array $lines): array
    {
        return collect($lines)->map(function ($line) {
            $debit = round((float) ($line['debit'] ?? 0), 4);
            $credit = round((float) ($line['credit'] ?? 0), 4);
            if (($debit > 0 && $credit > 0) || ($debit <= 0 && $credit <= 0)) {
                throw ValidationException::withMessages(['lines' => 'Each line must contain either a debit or a credit, but not both.']);
            }

            return [
                'account_id' => (int) $line['account_id'],
                'description' => trim($line['description'] ?? ''),
                'debit' => $debit,
                'credit' => $credit,
            ];
        })->values()->all();
    }

    private function assertBalanced(array $lines): void
    {
        $debit = round((float) collect($lines)->sum('debit'), 2);
        $credit = round((float) collect($lines)->sum('credit'), 2);
        if ($debit <= 0 || abs($debit - $credit) > 0.01) {
            throw ValidationException::withMessages(['lines' => 'Total debit and total credit must be equal and greater than zero.']);
        }
    }

    private function assertDateInPeriod(string $date, AccountingPeriod $period): void
    {
        if ($date < $period->start_date->toDateString() || $date > $period->end_date->toDateString()) {
            throw ValidationException::withMessages(['date' => 'The adjustment date must fall inside the selected accounting period.']);
        }
    }

    private function depreciationSuggestions(AccountingPeriod $period)
    {
        return FixedAsset::with(['depreciationExpenseAccount', 'accumulatedDepreciationAccount'])
            ->where('status', 'active')
            ->whereDate('in_service_date', '<=', $period->end_date)
            ->where(function ($query) use ($period) {
                $query->whereNull('last_depreciation_date')->orWhereDate('last_depreciation_date', '<', $period->start_date);
            })
            ->get()
            ->map(function ($asset) {
                $asset->suggested_amount = min($asset->monthlyDepreciation(), max(0, (float) $asset->current_book_value - (float) $asset->residual_value));
                return $asset;
            })
            ->filter(fn ($asset) => $asset->suggested_amount > 0);
    }

    private function validateDepreciation(string $type, ?int $assetId, array $lines, int $periodId, ?int $ignoreAdjustmentId = null): ?FixedAsset
    {
        if ($type !== 'depreciation') {
            if ($assetId) {
                throw ValidationException::withMessages(['fixed_asset_id' => 'A fixed asset can only be linked to a depreciation adjustment.']);
            }
            return null;
        }

        if (! $assetId) {
            throw ValidationException::withMessages(['fixed_asset_id' => 'Select a fixed asset for a depreciation adjustment.']);
        }

        $asset = FixedAsset::where('company_id', $this->companyId())->findOrFail($assetId);
        $debitAccounts = collect($lines)->where('debit', '>', 0)->pluck('account_id')->unique();
        $creditAccounts = collect($lines)->where('credit', '>', 0)->pluck('account_id')->unique();
        if ($debitAccounts->count() !== 1 || (int) $debitAccounts->first() !== (int) $asset->depreciation_expense_account_id
            || $creditAccounts->count() !== 1 || (int) $creditAccounts->first() !== (int) $asset->accumulated_depreciation_account_id) {
            throw ValidationException::withMessages(['lines' => 'Depreciation must debit the asset depreciation expense account and credit its accumulated depreciation account.']);
        }

        $amount = (float) collect($lines)->sum('debit');
        $maximum = max(0, (float) $asset->current_book_value - (float) $asset->residual_value);
        if ($amount > $maximum + 0.01) {
            throw ValidationException::withMessages(['lines' => 'Depreciation cannot reduce book value below residual value.']);
        }

        $duplicate = MonthEndAdjustment::where('fixed_asset_id', $asset->id)
            ->where('accounting_period_id', $periodId)
            ->whereIn('status', ['draft', 'posted'])
            ->when($ignoreAdjustmentId, fn ($query) => $query->where('id', '<>', $ignoreAdjustmentId))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['fixed_asset_id' => 'A depreciation adjustment already exists for this asset in the selected period.']);
        }

        return $asset;
    }

    private function uniqueNo(string $date): string
    {
        return 'JV-ME-' . date('Ym', strtotime($date)) . '-' . Str::upper(Str::random(6));
    }

    private function companyId(): int
    {
        return (int) (loggedCompanyId()['company_id'] ?? auth('web')->user()->company_id);
    }
}
