<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\Budget;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Services\Account\BudgetInsightService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    // ── Budget Management (index) ────────────────────────────────────────────

    public function index(Request $request)
    {
        $companyId = auth('web')->user()->company_id;
        $periods = AccountingPeriod::where('company_id', $companyId)->orderByDesc('start_date')->get();
        $periodId = $request->period_id ?? $periods->first()?->id;

        $period   = $periods->find($periodId);
        $accounts = ChartOfAccount::forCompany($companyId)
            ->whereIn('category', ['Expenses', 'Revenue', 'COGS'])
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        // All budgets for this period, keyed by account_id
        $budgets = Budget::where('accounting_period_id', $periodId)
            ->where('company_id', $companyId)
            ->get()
            ->keyBy('account_id');

        return view('pages.account.budget.index',
            compact('periods', 'period', 'accounts', 'budgets'));
    }

    // ── Upsert (store or update) via AJAX ────────────────────────────────────

    public function upsert(Request $request)
    {
        $companyId = auth('web')->user()->company_id;
        $validated = $request->validate([
            'accounting_period_id' => [
                'required',
                Rule::exists('accounting_periods', 'id')->where('company_id', $companyId),
            ],
            'account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId),
            ],
            'amount'               => 'required|numeric|min:0',
            'notes'                => 'nullable|string|max:500',
        ]);

        $budget = Budget::updateOrCreate(
            [
                'company_id'           => $companyId,
                'accounting_period_id' => $validated['accounting_period_id'],
                'account_id'           => $validated['account_id'],
            ],
            [
                'amount'     => $validated['amount'],
                'notes'      => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]
        );

        return response()->json(['success' => true, 'budget' => $budget]);
    }

    // ── Destroy ──────────────────────────────────────────────────────────────

    public function destroy(Budget $budget)
    {
        abort_if((int) $budget->company_id !== (int) auth('web')->user()->company_id, 404);

        $budget->delete();

        return response()->json(['success' => true]);
    }

    // ── Budget vs Actual Report ──────────────────────────────────────────────

    public function report(Request $request)
    {
        $companyId = auth('web')->user()->company_id;
        $periods  = AccountingPeriod::where('company_id', $companyId)->orderByDesc('start_date')->get();
        $periodId = $request->period_id ?? $periods->first()?->id;
        $period   = $periods->find($periodId);

        // Budgets for this period
        $budgets = Budget::where('accounting_period_id', $periodId)
            ->where('company_id', $companyId)
            ->with('account')
            ->get()
            ->keyBy('account_id');

        // Actual spend: sum of transactions in the period, grouped by account
        $actuals = Transaction::where('company_id', $companyId)
            ->where('accounting_period_id', $periodId)
            ->select('account_id',
                DB::raw('SUM(debit)  as total_debit'),
                DB::raw('SUM(credit) as total_credit'))
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        // All accounts that appear in either budgets or actuals
        $accountIds = $budgets->keys()->merge($actuals->keys())->unique();
        $accounts   = ChartOfAccount::forCompany($companyId)
            ->whereIn('id', $accountIds)
            ->whereIn('category', ['Expenses', 'Revenue', 'COGS'])
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        // Build report rows
        $rows = $accounts->map(function ($account) use ($budgets, $actuals) {
            $budget     = (float) ($budgets[$account->id]?->amount ?? 0);
            $actual_obj = $actuals[$account->id] ?? null;

            // For expense accounts: actual = net debit; for revenue: net credit
            if (in_array($account->category, ['Revenue'])) {
                $actual = (float) ($actual_obj?->total_credit ?? 0) - (float) ($actual_obj?->total_debit ?? 0);
            } else {
                $actual = (float) ($actual_obj?->total_debit ?? 0) - (float) ($actual_obj?->total_credit ?? 0);
            }

            $variance = $budget - $actual;
            $pct      = $budget > 0 ? round(($actual / $budget) * 100, 1) : null;

            return [
                'account'     => $account,
                'budget'      => $budget,
                'actual'      => $actual,
                'variance'    => $variance,       // positive = under budget, negative = over
                'pct'         => $pct,
                'over_budget' => $actual > $budget && $budget > 0,
            ];
        });

        $totals = [
            'budget'   => $rows->sum('budget'),
            'actual'   => $rows->sum('actual'),
            'variance' => $rows->sum('variance'),
        ];

        // AI-generated budget insights.
        $insightResult = app(BudgetInsightService::class)->generate($rows, $totals, $period);
        $insights = $insightResult['insights'];
        $health   = $insightResult['health'];

        return view('pages.account.budget.report',
            compact('periods', 'period', 'rows', 'totals', 'insights', 'health'));
    }
}
