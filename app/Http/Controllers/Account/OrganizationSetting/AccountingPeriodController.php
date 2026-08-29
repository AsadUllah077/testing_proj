<?php

namespace App\Http\Controllers\Account\OrganizationSetting;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationSetting\AccountingPeriodCreateRequest;
use App\Http\Requests\OrganizationSetting\AccountingPeriodUpdateRequest;
use App\Models\AccountingPeriod;
use App\Models\AccountingPeriodLockUser;
use App\Models\AccountingPeriodLogs;
use App\Models\ActivityLog;
use App\Models\TaxRate;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccountingPeriodController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->from;
        $to = $request->to;
        $status = $request->status;
        $perPage = $request->per_page ?? 20;
        $canManageAllowedUsers = Auth::user()->hasPermission('account.accounting-period.edit');

        $query = AccountingPeriod::query()->with('allowedUsers');

        // Date filters
        if ($from) {
            $query->whereDate('start_date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('end_date', '<=', $to);
        }

        // Status
        if ($status !== null && $status !== '') {
            $query->where('is_active', $status);
        }

        $accountingPeriods = $query
            ->sortable()
            ->orderBy('id', 'DESC')
            ->paginate($perPage)
            ->appends($request->query());

        $data = [
            'accountingPeriods' => $accountingPeriods,
            'perPage' => $perPage,
            'from' => $from,
            'to' => $to,
            'status' => $status,
            'canManageAllowedUsers' => $canManageAllowedUsers,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.organization-settings.accounting-period.table', $data)->render(),
                'pagination' => $accountingPeriods->appends(request()->query())->links()->toHtml(),
            ]);
        }

        $data['users'] = $canManageAllowedUsers
            ? User::where('company_id', loggedCompanyId()['company_id'])
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'company_id', 'first_name', 'last_name', 'email'])
            : collect();

        return view(
            'pages.account.organization-settings.accounting-period.index',
            $data
        );
    }

    public function create()
    {
        $taxRate = TaxRate::all()->groupBy('tax_type');
        $users = User::where('company_id', loggedCompanyId()['company_id'])->get();

        return view('pages.account.organization-settings.accounting-period.create', compact('taxRate', 'users'));
    }

    public function store(AccountingPeriodCreateRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $createdBy = Auth::id();
            $data['created_by'] = $createdBy;

            $isUnlockedPeriod = AccountingPeriod::where('is_active', true)
                ->where('is_locked', false)
                ->first();
            if ($isUnlockedPeriod) {
                return response()->json([
                    'message' => 'The Accounting Period from '.dateFormat($isUnlockedPeriod->start_date).' to '.dateFormat($isUnlockedPeriod->end_date).' is already open. Please lock it first.',
                    'success' => false,
                ], 403);
            }

            $periodId = AccountingPeriod::create($data);

            $userIds = $request->user_id ?? [];

            foreach ($userIds as $userId) {
                AccountingPeriodLockUser::create([
                    'accounting_period_id' => $periodId->id,
                    'user_id' => $userId,
                    'created_by' => Auth::id(),
                ]);
            }

            AccountingPeriodLogs::create([
                'accounting_period_id' => $periodId->id,
                'user_id' => auth('web')->id(),
                'event' => 'created',
                'old_values' => null,
                'new_values' => $periodId->toArray(),
            ]);

            DB::commit();

            return response()->json(['message' => 'Accounting period created successfully.', 'success' => true], 200);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['message' => 'Failed to create accounting period: '.$e->getMessage(), 'success' => false], 401);
        }
    }

    public function show($id)
    {
        $period = AccountingPeriod::with([
            'taxRate',
            'lockUsers',
        ])->findOrFail($id);

        return view('pages.account.organization-settings.accounting-period.detail', compact(
            'period'
        ));
    }

    public function edit(AccountingPeriod $accounting_period)
    {
        $taxRate = TaxRate::all()->groupBy('tax_type');

        $users = User::where('company_id', loggedCompanyId()['company_id'])->get();

        $period_lock_users = AccountingPeriodLockUser::where('accounting_period_id', $accounting_period->id)
            ->pluck('user_id')
            ->toArray();

        return view(
            'pages.account.organization-settings.accounting-period.edit',
            compact('accounting_period', 'users', 'taxRate', 'period_lock_users')
        );
    }

    public function update(AccountingPeriodUpdateRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            $createdBy = Auth::id();

            $data['updated_by'] = $createdBy;

            $period = AccountingPeriod::findOrFail($id);

            $old = $period->toArray();

            $period->update($data);

            $userIds = $request->user_id ?? [];

            AccountingPeriodLockUser::where('accounting_period_id', $id)
                ->delete();

            foreach ($userIds as $userId) {
                AccountingPeriodLockUser::create([
                    'accounting_period_id' => $id,
                    'user_id' => $userId,
                    'created_by' => Auth::id(),
                ]);
            }

            AccountingPeriodLogs::create([
                'accounting_period_id' => $id,
                'user_id' => auth('web')->id(),
                'event' => 'updated',
                'old_values' => $old,
                'new_values' => $period->toArray(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Accounting period updated successfully.',
                'success' => true,
            ], 200);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'Failed to update accounting period: '.$e->getMessage(),
                'success' => false,
            ], 401);
        }
    }

    /**
     * Update only the users allowed to work in a locked accounting period.
     */
    public function updateAllowedUsers(Request $request, AccountingPeriod $accounting_period)
    {
        $companyId = loggedCompanyId()['company_id'];

        $validated = $request->validate([
            'user_id' => ['nullable', 'array'],
            'user_id.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')),
            ],
        ]);

        $userIds = collect($validated['user_id'] ?? [])
            ->map(fn ($userId) => (int) $userId)
            ->values();

        DB::transaction(function () use ($accounting_period, $userIds) {
            $oldUserIds = $accounting_period->allowedUsers()
                ->pluck('user_id')
                ->map(fn ($userId) => (int) $userId)
                ->values()
                ->all();

            $accounting_period->allowedUsers()->delete();

            foreach ($userIds as $userId) {
                $accounting_period->allowedUsers()->create([
                    'user_id' => $userId,
                    'created_by' => Auth::id(),
                ]);
            }

            AccountingPeriodLogs::create([
                'accounting_period_id' => $accounting_period->id,
                'user_id' => Auth::id(),
                'event' => 'updated allowed advisors',
                'old_values' => ['allowed_user_ids' => $oldUserIds],
                'new_values' => ['allowed_user_ids' => $userIds->all()],
            ]);
        });

        return response()->json([
            'message' => 'Allowed advisors updated successfully.',
            'success' => true,
            'user_ids' => $userIds,
        ]);
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $period = AccountingPeriod::findOrFail($id);
            $period->delete();

            DB::commit();

            return response()->json(['message' => 'Accounting period deleted successfully.', 'success' => true], 200);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['message' => 'Delete failed: '.$e->getMessage(), 'success' => false], 401);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $period = AccountingPeriod::findOrFail($id);

            $old = $period->toArray();

            $isLocked = $request->is_locked == 1 ? true : false;

            $period->update([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_locked' => $isLocked,
                'is_active' => true,
            ]);

            AccountingPeriodLogs::create([
                'accounting_period_id' => $id,
                'user_id' => auth('web')->id(),
                'event' => 'change the lock option',
                'old_values' => $old,
                'new_values' => $period->toArray(),
            ]);

            DB::commit();

            return back()->with('success', 'Accounting period status updated successfully.');
        } catch (\Exception $e) {

            DB::rollBack();

            return back()->with('error', 'Something went wrong while updating the status.');
        }
    }

    public function logs($id)
    {
        try {
            $period = AccountingPeriod::findOrFail($id);

            $logs = $period->accountingPeriodLogs()
                ->with('user')
                ->orderBy('id', 'DESC')
                ->paginate(20);

            return view('pages.account.organization-settings.accounting-period.logs', compact('logs'));
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
            $logs = $query->where('loggable_type', 'App\Models\AccountingPeriod')
                ->paginate($request->per_page ?? 10)
                ->appends($request->query());

            return view('pages.activity_logs.index', compact('logs'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }
}
