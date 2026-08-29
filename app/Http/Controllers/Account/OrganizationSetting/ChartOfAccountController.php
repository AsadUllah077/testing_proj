<?php

namespace App\Http\Controllers\Account\OrganizationSetting;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationSetting\ChartOfAccountCreateRequest;
use App\Http\Requests\OrganizationSetting\ChartOfAccountUpdateRequest;
use App\Models\AccountingPeriod;
use App\Models\ActivityLog;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountLog;
use App\Models\Transaction;
use App\Models\TaxRate;
use App\Services\Account\OpeningBalanceService;
use App\Support\AccountingConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\ChartOfAccountExport;
use App\Exports\ChartOfAccountTemplateExport;
use App\Imports\ChartOfAccountImport;
use Maatwebsite\Excel\Facades\Excel;

class ChartOfAccountController extends Controller
{
    public function __construct(private readonly OpeningBalanceService $openingBalanceService)
    {
    }

    /**
     * Display all Chart of Accounts
     */
    public function index(Request $request)
    {
        $tab = strtolower($request->input('tab', 'all'));

        if ($tab === 'deleted_history') {
            $logQuery = ActivityLog::where('company_id', loggedCompanyId()['company_id'])
                ->where('loggable_type', 'App\Models\ChartOfAccount')
                ->where('action', 'deleted')
                ->with('user', 'employee');

            if ($request->filled('name')) {
                $nameVal = strtolower($request->name);
                $logQuery->where(function($q) use ($nameVal) {
                    $q->where('changes', 'LIKE', "%{$nameVal}%");
                });
            }

            $accounts = $logQuery->orderBy('id', 'desc')
                ->paginate($request->per_page ?? 50);
        } else {
            // Base query
            $query = ChartOfAccount::query()->with('parent');
            if (\Illuminate\Support\Facades\Schema::hasColumn('chart_of_accounts', 'company_id')) {
                $query->where('company_id', loggedCompanyId()['company_id']);
            }

            // Tab filtering
            if ($tab === 'assets') {
                $query->where('category', 'Assets');
            } elseif ($tab === 'liabilities') {
                $query->where('category', 'Liabilities');
            } elseif ($tab === 'equity') {
                $query->where('category', 'Equity');
            } elseif ($tab === 'expenses') {
                $query->where('category', 'Expenses');
            } elseif ($tab === 'cogs') {
                $query->where('category', 'COGS');
            } elseif ($tab === 'revenue') {
                $query->where('category', 'Revenue');
            } elseif ($tab === 'archive') {
                $query->onlyTrashed();
            }

            // Filters
            if ($request->filled('account_no')) {
                $query->where('account_number', 'LIKE', "%{$request->account_no}%");
            }

            if ($request->filled('name')) {
                $nameVal = strtolower($request->name);
                $query->where(function($q) use ($nameVal) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%' . $nameVal . '%'])
                      ->orWhereRaw('LOWER(category) LIKE ?', ['%' . $nameVal . '%'])
                      ->orWhereRaw('LOWER(CAST(account_number AS text)) LIKE ?', ['%' . $nameVal . '%']);
                });
            }

            if ($request->filled('parent_id')) {
                $query->where('parent_id', $request->parent_id);
            }

            // Pagination
            $accounts = $query
                ->sortable()
                ->orderBy('account_number')
                ->paginate($request->per_page ?? 50); // Larger default page size like Xero
        }

        $parentAccounts = ChartOfAccount::whereNull('parent_id')
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('chart_of_accounts', 'company_id'), fn ($query) => $query->where('company_id', loggedCompanyId()['company_id']))
            ->get();

        $totalAccounts = ChartOfAccount::when(\Illuminate\Support\Facades\Schema::hasColumn('chart_of_accounts', 'company_id'), fn ($query) => $query->where('company_id', loggedCompanyId()['company_id']))
            ->count();

        $companyId = loggedCompanyId()['company_id'];
        $obeAccountId = ChartOfAccount::getOpeningBalanceEquityId($companyId);
        $totalInitialBalance = DB::table('transactions')
            ->where('type', AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE)
            ->where('company_id', $companyId)
            ->when($obeAccountId, fn($q) => $q->where('account_id', '!=', $obeAccountId))
            ->selectRaw('COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total')
            ->first();

        // Calculate actual balances for YTD column
        $ytdBalances = DB::table('transactions')
            ->where('company_id', $companyId)
            ->groupBy('account_id')
            ->select('account_id', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(credit) as total_credit'))
            ->get()
            ->keyBy('account_id');

        $taxRates = TaxRate::where('company_id', $companyId)->get();

        $data = [
            'accounts'            => $accounts,
            'totalAccounts'       => $totalAccounts,
            'rootAccounts'        => $parentAccounts->count(),
            'parentAccounts'      => $parentAccounts,
            'totalInitialBalance' => (float) ($totalInitialBalance?->debit_total ?? 0),
            'totalOpeningDebit'   => (float) ($totalInitialBalance?->debit_total ?? 0),
            'totalOpeningCredit'  => (float) ($totalInitialBalance?->credit_total ?? 0),
            'parents'             => $parentAccounts,
            'ytdBalances'         => $ytdBalances,
            'taxRates'            => $taxRates,
            'currentTab'          => $tab,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html'       => view(
                    'pages.account.organization-settings.chart-of-account.partial.table',
                    $data
                )->render(),
                'pagination' => $accounts->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view(
            'pages.account.organization-settings.chart-of-account.index',
            $data
        );
    }

    /**
     * show create Chart of Accounts
     */
    public function create()
    {
        return view('pages.account.organization-settings.chart-of-account.create');
    }

    /**
     * Store a new COA account
     */
    public function store(ChartOfAccountCreateRequest $request)
    {
        $data = $request->validated();
        $companyId = loggedCompanyId()['company_id'];
        unset($data['opening_balance_amount'], $data['opening_balance_side'], $data['opening_date']);
        $data['initial_balance'] = 0;
        if (\Illuminate\Support\Facades\Schema::hasColumn('chart_of_accounts', 'company_id')) {
            $data['company_id'] = $companyId;
        }

        DB::beginTransaction();

        try {
            $account = ChartOfAccount::create($data);
            $account->refresh();

            ChartOfAccountLog::create([
                'chart_of_account_id' => $account->id,
                'user_id'     => auth('web')->id(),
                'event'       => 'created',
                'old_values'  => null,
                'new_values'  => $account->toArray()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Account created successfully",
                'data' => $account
            ]);
        } catch (\Exception $e) {

            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Show account details
     */
    public function show($id)
    {
        $account = ChartOfAccount::with([
            'parent',
            'childrenRecursive'
        ])
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('chart_of_accounts', 'company_id'), fn ($query) => $query->where('company_id', loggedCompanyId()['company_id']))
            ->findOrFail($id);

        $associatedAccounts = $account->childrenRecursive->count();

        $totalInitialBalance = $this->sumOpeningBalance($account);

        $level = $this->findLevel($account);

        return view('pages.account.organization-settings.chart-of-account.detail', compact(
            'account',
            'associatedAccounts',
            'totalInitialBalance',
            'level'
        ));
    }

    /**
     * Post a double-entry journal for opening balance.
     * DR account / CR OBE  for debit-normal accounts (Assets, Expenses, COGS)
     * DR OBE / CR account  for credit-normal accounts (Liabilities, Equity, Revenue)
     */
    private function postOpeningBalanceEntry(ChartOfAccount $account, float $amount): void
    {
        $obe = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])
            ->where('system_account_type', 'OPENING_BALANCE_EQUITY')
            ->first();
        if (!$obe) {
            throw new \Exception('Opening Balance Equity account not found. Please run seeder.');
        }

        $companyId     = loggedCompanyId()['company_id'];
        $period        = AccountingPeriod::where('is_active', true)
            ->where('company_id', $companyId)
            ->orderBy('start_date')
            ->first();
        $date          = $period?->start_date?->toDateString() ?? now()->toDateString();
        $isDebitNormal = in_array($account->category, ['Assets', 'Expenses', 'COGS']);
        $ref           = 'OBE-' . str_pad($account->id, 5, '0', STR_PAD_LEFT);

        Transaction::insert([
            [
                'unique_no'            => $ref,
                'date'                 => $date,
                'account_id'           => $account->id,
                'debit'                => $isDebitNormal ? $amount : 0,
                'credit'               => $isDebitNormal ? 0 : $amount,
                'description'          => 'Opening balance — ' . $account->name,
                'accounting_period_id' => $period?->id,
                'type'                 => 'OpeningBalance',
                'company_id'           => $companyId,
                'created_by'           => auth('web')->id(),
                'updated_by'           => auth('web')->id(),
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
            [
                'unique_no'            => $ref,
                'date'                 => $date,
                'account_id'           => $obe->id,
                'debit'                => $isDebitNormal ? 0 : $amount,
                'credit'               => $isDebitNormal ? $amount : 0,
                'description'          => 'Opening balance — ' . $account->name,
                'accounting_period_id' => $period?->id,
                'type'                 => 'OpeningBalance',
                'company_id'           => $companyId,
                'created_by'           => auth('web')->id(),
                'updated_by'           => auth('web')->id(),
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
        ]);
    }

    private function sumOpeningBalance($account): float
    {
        $allAccounts = collect([$account])->merge($account->childrenRecursive);
        $ids = $allAccounts->pluck('id');

        $companyId = loggedCompanyId()['company_id'];
        $entries = DB::table('transactions')
            ->where('type', AccountingConstants::TRANSACTION_TYPE_OPENING_BALANCE)
            ->where('company_id', $companyId)
            ->whereIn('account_id', $ids)
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $total = 0.0;
        foreach ($allAccounts as $acc) {
            $entry = $entries->get($acc->id);
            if (! $entry) continue;
            $total += abs((float) $entry->total_debit - (float) $entry->total_credit);
        }

        return $total;
    }

    private function findLevel($account)
    {
        $level = 1;
        $current = $account;

        while ($current->parent) {
            $level++;
            $current = $current->parent;
        }

        return $level;
    }

    /**
     * show edit Chart of Accounts
     */

    public function edit($id)
    {
        $account = ChartOfAccount::when(\Illuminate\Support\Facades\Schema::hasColumn('chart_of_accounts', 'company_id'), fn ($query) => $query->where('company_id', loggedCompanyId()['company_id']))
            ->findOrFail($id);

        $parents = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])
            ->where('id', '!=', $id)
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('chart_of_accounts', 'company_id'), fn ($query) => $query->where('company_id', loggedCompanyId()['company_id']))
            ->orderBy('account_number')
            ->get();

        $companyId = loggedCompanyId()['company_id'];
        $openingBalance = $this->openingBalanceService->accountOpeningBalance($account, $companyId);
        $defaultOpeningDate = $account->opening_date
            ?: $openingBalance['date']
            ?: $this->openingBalanceService->defaultOpeningDate($companyId);

        return view('pages.account.organization-settings.chart-of-account.edit', compact('account', 'parents', 'openingBalance', 'defaultOpeningDate'));
    }

    /**
     * Update existing COA account
     */
    public function update(ChartOfAccountUpdateRequest $request, $id)
    {
        $account = ChartOfAccount::when(\Illuminate\Support\Facades\Schema::hasColumn('chart_of_accounts', 'company_id'), fn ($query) => $query->where('company_id', loggedCompanyId()['company_id']))
            ->findOrFail($id);

        $old = $account->toArray();

        $data = $request->validated();
        $companyId = loggedCompanyId()['company_id'];
        unset($data['opening_balance_amount'], $data['opening_balance_side'], $data['opening_date']);
        $data['initial_balance'] = 0;

        DB::beginTransaction();

        try {
            $account->update($data);
            $account->refresh();

            ChartOfAccountLog::create([
                'chart_of_account_id' => $id,
                'user_id'     => auth('web')->id(),
                'event'       => 'updated',
                'old_values'  => $old,
                'new_values'  => $account->toArray()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Account updated successfully",
                'data' => $account
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' =>  $e->getMessage(),
            ]);
        }
    }

    /**
     * Soft Delete COA
     */
    public function destroy($id)
    {
        $account = ChartOfAccount::when(\Illuminate\Support\Facades\Schema::hasColumn('chart_of_accounts', 'company_id'), fn ($query) => $query->where('company_id', loggedCompanyId()['company_id']))
            ->findOrFail($id);

        // Prevent deleting system accounts
        if ($account->is_system) {
            return response()->json([
                'success' => false,
                'message' => "This is a system account. You cannot delete it."
            ]);
        }

        DB::beginTransaction();

        try {
            $account->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Account deleted successfully"
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function logs($id)
    {
        try {
            $account = ChartOfAccount::when(\Illuminate\Support\Facades\Schema::hasColumn('chart_of_accounts', 'company_id'), fn ($query) => $query->where('company_id', loggedCompanyId()['company_id']))
                ->findOrFail($id);

            $logs = $account->chartOfAccountLogs()
                ->with('user')
                ->orderBy('id', 'DESC')
                ->paginate(20);

            return view('pages.account.organization-settings.chart-of-account.logs', compact('logs'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function allLogs(Request $request)
    {
        try {
            $query = ActivityLog::query();
            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }
            $logs = $query->where('loggable_type', 'App\Models\ChartOfAccount')
                ->paginate($request->per_page ?? 10)
                ->appends($request->query());
            return view('pages.activity_logs.index', compact('logs'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function export(Request $request)
    {
        return Excel::download(new ChartOfAccountExport($request->all()), 'chart_of_accounts.csv');
    }

    public function downloadTemplate()
    {
        return Excel::download(new ChartOfAccountTemplateExport, 'chart_of_accounts_template.csv');
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt,xlsx',
        ]);

        try {
            $import = new ChartOfAccountImport(loggedCompanyId()['company_id']);
            Excel::import($import, $request->file('csv_file'));

            $errors = $import->getErrors();

            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import completed with some errors',
                    'errors' => $errors,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Chart of Accounts imported successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function search(Request $request)
    {
        $search = $request->q;
        $accounts = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])
            ->where('name', 'LIKE', "%$search%")
            ->orWhere('account_number', 'LIKE', "%$search%")
            ->orderBy('account_number')
            ->limit(20)
            ->get(['id', 'name', 'account_number']);

        return response()->json($accounts->map(function ($account) {
            return [
                'id' => $account->id,
                'text' => $account->account_number . ' - ' . $account->name,
            ];
        }));
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No accounts selected.']);
        }

        DB::beginTransaction();
        try {
            $accounts = ChartOfAccount::withTrashed()->whereIn('id', $ids)->get();
            $deletedCount = 0;
            foreach ($accounts as $account) {
                if ($account->is_system) {
                    continue;
                }
                $account->forceDelete();
                $deletedCount++;
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => "Successfully permanently deleted {$deletedCount} accounts."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function bulkArchive(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No accounts selected.']);
        }

        DB::beginTransaction();
        try {
            $accounts = ChartOfAccount::whereIn('id', $ids)->get();
            $archivedCount = 0;
            foreach ($accounts as $account) {
                if ($account->is_system) {
                    continue;
                }
                $account->delete(); // Soft delete
                $archivedCount++;
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => "Successfully archived {$archivedCount} accounts."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function bulkRestore(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No accounts selected.']);
        }

        DB::beginTransaction();
        try {
            $accounts = ChartOfAccount::onlyTrashed()->whereIn('id', $ids)->get();
            $restoredCount = 0;
            foreach ($accounts as $account) {
                $account->restore();
                $restoredCount++;
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => "Successfully restored {$restoredCount} accounts from Archive."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function bulkChangeTaxRate(Request $request)
    {
        $ids = $request->input('ids', []);
        $taxCode = $request->input('tax_code');
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No accounts selected.']);
        }
        if (!$taxCode) {
            return response()->json(['success' => false, 'message' => 'No tax rate selected.']);
        }

        DB::beginTransaction();
        try {
            ChartOfAccount::withTrashed()->whereIn('id', $ids)->update(['tax_code' => $taxCode]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => "Successfully updated tax rate for selected accounts."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
