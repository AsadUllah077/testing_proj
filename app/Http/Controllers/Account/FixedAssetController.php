<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\FixedAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FixedAssetController extends Controller
{
    private const CATEGORIES = ['Equipment', 'Vehicle', 'Building', 'Furniture', 'Intangible', 'Land', 'Other'];
    private const METHODS     = ['straight_line' => 'Straight-Line', 'declining_balance' => 'Double-Declining Balance'];

    public function index(Request $request)
    {
        $query = FixedAsset::with(['assetAccount', 'depreciationExpenseAccount'])
            ->latest('purchase_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(fn($q) => $q->where('name', 'ILIKE', "%{$term}%")
                ->orWhere('asset_number', 'ILIKE', "%{$term}%"));
        }

        $perPage = $request->per_page ?? 20;

        $assets = $query->paginate($perPage)->withQueryString();

        $stats = [
            'total'            => FixedAsset::count(),
            'active'           => FixedAsset::where('status', 'active')->count(),
            'fully_deprecated' => FixedAsset::where('status', 'fully_depreciated')->count(),
            'total_cost'       => FixedAsset::sum('purchase_cost'),
            'total_book_value' => FixedAsset::where('status', 'active')->sum('current_book_value'),
        ];

        if ($request->ajax()) {
            return response()->json([
                'html'       => view(
                    'pages.account.fixed-asset.table',
                    compact('assets')
                )->render(),
                'pagination' => $assets->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view(
            'pages.account.fixed-asset.index',
            compact('assets', 'stats') + ['categories' => self::CATEGORIES]
        );
    }

    public function create()
    {
        $accounts   = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])->orderBy('category')->orderBy('name')->get();
        $categories = self::CATEGORIES;
        $methods    = self::METHODS;

        return view('pages.account.fixed-asset.create', compact('accounts', 'categories', 'methods'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                                   => 'required|string|max:255',
            'description'                            => 'nullable|string',
            'category'                               => 'nullable|string|max:100',
            'purchase_date'                          => 'required|date',
            'in_service_date'                        => 'required|date',
            'purchase_cost'                          => 'required|numeric|min:0',
            'residual_value'                         => 'required|numeric|min:0',
            'useful_life_months'                     => 'required|integer|min:1',
            'depreciation_method'                    => 'required|in:straight_line,declining_balance',
            'asset_account_id'                       => ['required', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'depreciation_expense_account_id'        => ['required', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'accumulated_depreciation_account_id'    => ['required', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'notes'                                  => 'nullable|string',
        ]);

        FixedAsset::create(array_merge($validated, [
            'company_id'         => auth('web')->user()->company_id,
            'asset_number'       => FixedAsset::generateAssetNumber(),
            'current_book_value' => $validated['purchase_cost'],
            'status'             => 'active',
            'created_by'         => Auth::id(),
            'updated_by'         => Auth::id(),
        ]));

        return redirect()->route('account.fixed-asset.index')
            ->with('success', 'Fixed asset added to the register.');
    }

    public function show(FixedAsset $fixedAsset)
    {
        $fixedAsset->load(['assetAccount', 'depreciationExpenseAccount', 'accumulatedDepreciationAccount']);

        // Build depreciation schedule (straight-line only, for display)
        $schedule = $this->buildSchedule($fixedAsset);

        return view('pages.account.fixed-asset.show', compact('fixedAsset', 'schedule'));
    }

    public function edit(FixedAsset $fixedAsset)
    {
        abort_if((int) $fixedAsset->company_id !== (int) loggedCompanyId()['company_id'], 404);
        $accounts   = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])->orderBy('category')->orderBy('name')->get();
        $categories = self::CATEGORIES;
        $methods    = self::METHODS;

        return view('pages.account.fixed-asset.edit', compact('fixedAsset', 'accounts', 'categories', 'methods'));
    }

    public function update(Request $request, FixedAsset $fixedAsset)
    {
        $validated = $request->validate([
            'name'                                   => 'required|string|max:255',
            'description'                            => 'nullable|string',
            'category'                               => 'nullable|string|max:100',
            'purchase_date'                          => 'required|date',
            'in_service_date'                        => 'required|date',
            'residual_value'                         => 'required|numeric|min:0',
            'useful_life_months'                     => 'required|integer|min:1',
            'depreciation_method'                    => 'required|in:straight_line,declining_balance',
            'asset_account_id'                       => ['required', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'depreciation_expense_account_id'        => ['required', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'accumulated_depreciation_account_id'    => ['required', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'status'                                 => 'required|in:active,fully_depreciated,disposed',
            'notes'                                  => 'nullable|string',
        ]);

        $fixedAsset->update(array_merge($validated, ['updated_by' => Auth::id()]));

        return redirect()->route('account.fixed-asset.index')
            ->with('success', 'Fixed asset updated.');
    }

    public function destroy(FixedAsset $fixedAsset)
    {
        $fixedAsset->delete();

        return redirect()->route('account.fixed-asset.index')
            ->with('success', 'Asset removed from register.');
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function buildSchedule(FixedAsset $asset): array
    {
        $schedule   = [];
        $bookValue  = (float) $asset->purchase_cost;
        $monthly    = $asset->monthlyDepreciation();
        $date       = $asset->in_service_date->copy()->startOfMonth();
        $maxMonths  = $asset->useful_life_months;

        for ($i = 1; $i <= min($maxMonths, 120); $i++) {
            $dep      = min($monthly, max(0, $bookValue - (float) $asset->residual_value));
            $bookValue = round($bookValue - $dep, 2);

            $schedule[] = [
                'month'      => $date->format('M Y'),
                'dep'        => $dep,
                'book_value' => $bookValue,
                'done'       => $asset->last_depreciation_date &&
                    $date->lte($asset->last_depreciation_date),
            ];

            $date->addMonth();

            if ($bookValue <= (float) $asset->residual_value) break;
        }

        return $schedule;
    }
}
