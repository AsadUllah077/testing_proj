<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AccountingCloseTask;
use App\Models\AccountingBalanceSyncRun;
use App\Models\AccountingPeriod;
use App\Models\AccountingPeriodLogs;
use App\Models\BankStatementLine;
use App\Models\Bill;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\PayRun;
use App\Models\Sale;
use App\Models\Transaction;
use App\Services\Account\AccountingHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccountingWorkspaceController extends Controller
{
    private const CLOSE_TASKS = [
        'bank_reconciliation' => 'Review and complete bank reconciliations',
        'accounts_receivable' => 'Review aged receivables and customer credits',
        'accounts_payable' => 'Review aged payables and vendor credits',
        'payroll_liabilities' => 'Confirm payroll and statutory liabilities',
        'tax_review' => 'Review tax control accounts and filing position',
        'adjustments' => 'Post accruals, prepayments, depreciation, and corrections',
        'management_review' => 'Complete management review and sign-off',
    ];

    public function __construct(private readonly AccountingHealthService $healthService)
    {
    }

    public function commandCenter(Request $request)
    {
        $companyId = $this->companyId();
        $health = $this->healthService->inspect($companyId, $request->start_date, $request->end_date);
        $start = $health['start'];
        $end = $health['end'];
        $metrics = [
            'sales' => (float) Sale::where('company_id', $companyId)->whereBetween('date', [$start, $end])->where('approval_status', 'approved')->sum('total_after_discount'),
            'purchases' => (float) Bill::where('company_id', $companyId)->whereBetween('date', [$start, $end])->where('approval_status', 'approved')->sum('total_after_discount'),
            'receivables' => (float) Sale::where('company_id', $companyId)->where('approval_status', 'approved')->sum('remaining_amount'),
            'payables' => (float) Bill::where('company_id', $companyId)->where('approval_status', 'approved')->sum('remaining_amount'),
            'unreconciled' => BankStatementLine::where('company_id', $companyId)->whereIn('status', ['Unreconciled', 'Partially Reconciled'])->count(),
            'open_payroll' => PayRun::where('company_id', $companyId)->whereIn('status', ['draft', 'posted'])->count(),
        ];
        $recentPostings = Transaction::with('account')->where('company_id', $companyId)->whereBetween('date', [$start, $end])->latest('id')->limit(12)->get();

        return view('pages.account.control-center.command-center', compact('health', 'metrics', 'recentPostings'));
    }

    public function health(Request $request)
    {
        $health = $this->healthService->inspect($this->companyId(), $request->start_date, $request->end_date);

        return view('pages.account.control-center.health', compact('health'));
    }

    public function balanceSyncHistory()
    {
        $company = Company::query()->findOrFail($this->companyId());
        $runs = AccountingBalanceSyncRun::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->with('initiatedBy')
            ->latest('started_at')
            ->paginate(20);

        return view('pages.account.control-center.balance-sync-history', compact('company', 'runs'));
    }

    public function balanceSyncShow(int $balanceSyncRun)
    {
        $balanceSyncRun = AccountingBalanceSyncRun::withoutGlobalScopes()
            ->where('company_id', $this->companyId())
            ->with(['company', 'initiatedBy'])
            ->findOrFail($balanceSyncRun);

        return view('pages.account.control-center.balance-sync-show', compact('balanceSyncRun'));
    }

    public function periodClose(Request $request)
    {
        $periods = AccountingPeriod::latest('start_date')->get();
        $period = $request->filled('period_id') ? AccountingPeriod::findOrFail($request->integer('period_id')) : $periods->first();
        $health = $period ? $this->healthService->inspect($this->companyId(), $period->start_date->toDateString(), $period->end_date->toDateString()) : null;
        $savedTasks = $period
            ? AccountingCloseTask::with('completedBy')->where('accounting_period_id', $period->id)->get()->keyBy('task_key')
            : collect();

        return view('pages.account.control-center.period-close', [
            'periods' => $periods,
            'period' => $period,
            'health' => $health,
            'taskDefinitions' => self::CLOSE_TASKS,
            'savedTasks' => $savedTasks,
        ]);
    }

    public function updateCloseTask(Request $request, AccountingPeriod $period)
    {
        abort_if((int) $period->company_id !== $this->companyId(), 404);
        if ($period->is_locked) {
            return back()->with('error', 'A locked period checklist cannot be changed.');
        }
        $validated = $request->validate([
            'task_key' => ['required', Rule::in(array_keys(self::CLOSE_TASKS))],
            'is_completed' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);
        $isCompleted = $request->boolean('is_completed');

        AccountingCloseTask::updateOrCreate(
            ['company_id' => $this->companyId(), 'accounting_period_id' => $period->id, 'task_key' => $validated['task_key']],
            [
                'is_completed' => $isCompleted,
                'notes' => $validated['notes'] ?? null,
                'completed_by' => $isCompleted ? auth('web')->id() : null,
                'completed_at' => $isCompleted ? now() : null,
            ]
        );

        return back()->with('success', 'Close checklist updated.');
    }

    public function closePeriod(AccountingPeriod $period)
    {
        abort_if((int) $period->company_id !== $this->companyId(), 404);
        if ($period->is_locked) {
            return back()->with('error', 'This accounting period is already locked.');
        }

        $completed = AccountingCloseTask::where('accounting_period_id', $period->id)
            ->where('is_completed', true)
            ->whereIn('task_key', array_keys(self::CLOSE_TASKS))
            ->count();
        $health = $this->healthService->inspect($this->companyId(), $period->start_date->toDateString(), $period->end_date->toDateString());

        if ($completed !== count(self::CLOSE_TASKS) || $health['critical'] > 0) {
            return back()->with('error', 'Complete every checklist item and resolve all critical checks before locking the period.');
        }

        DB::transaction(function () use ($period) {
            $old = $period->toArray();
            $period->update(['is_locked' => true, 'updated_by' => auth('web')->id()]);
            AccountingPeriodLogs::create([
                'accounting_period_id' => $period->id,
                'user_id' => auth('web')->id(),
                'event' => 'closed from period close checklist',
                'old_values' => $old,
                'new_values' => $period->fresh()->toArray(),
            ]);
        });

        return back()->with('success', 'Accounting period locked successfully.');
    }

    public function documentTrace(Request $request)
    {
        $validated = $request->validate([
            'type' => ['nullable', Rule::in(['sale', 'bill', 'payroll', 'journal', 'reference'])],
            'reference' => 'nullable|string|max:100',
        ]);
        $type = $validated['type'] ?? 'reference';
        $reference = trim($validated['reference'] ?? '');
        $document = null;
        $uniqueNo = null;

        if ($reference !== '') {
            if ($type === 'sale') {
                $document = Sale::where(fn ($query) => $query->where('unique_no', $reference)->orWhere('invoice_no', $reference))->first();
            } elseif ($type === 'bill') {
                $document = Bill::where(fn ($query) => $query->where('unique_no', $reference)->orWhere('invoice_no', $reference))->first();
            } elseif ($type === 'payroll') {
                $document = PayRun::where('unique_no', $reference)->first();
            } elseif ($type === 'journal') {
                $document = JournalEntry::where('unique_no', $reference)->first();
            }
            $uniqueNo = $document?->unique_no ?: $reference;
        }

        $transactions = collect();
        if ($uniqueNo) {
            $query = Transaction::with(['account', 'createdBy']);
            if ($document instanceof Sale) {
                $query->where(fn ($builder) => $builder->where('unique_no', $uniqueNo)
                    ->orWhere(fn ($related) => $related->where('reference_id', $document->id)
                        ->whereIn('type', ['sale', 'Sale', 'sale_payment', 'receipt', 'Credit_Note'])));
            } elseif ($document instanceof Bill) {
                $query->where(fn ($builder) => $builder->where('unique_no', $uniqueNo)
                    ->orWhere(fn ($related) => $related->where('reference_id', $document->id)
                        ->whereIn('type', ['bill', 'Bill', 'bill_payment', 'payment', 'Debit_Note'])));
            } elseif ($document instanceof PayRun) {
                $query->where(fn ($builder) => $builder->where('unique_no', $uniqueNo)
                    ->orWhere(fn ($related) => $related->where('reference_id', $document->id)
                        ->where(fn ($payroll) => $payroll->where('type', 'Payroll')
                            ->orWhere('unique_no', 'like', 'PR-PAY-%')
                            ->orWhere('unique_no', 'like', 'PR-DED-%'))));
            } else {
                $query->where('unique_no', $uniqueNo);
            }
            $transactions = $query->orderBy('date')->orderBy('id')->get();
        }
        $totals = ['debit' => (float) $transactions->sum('debit'), 'credit' => (float) $transactions->sum('credit')];

        return view('pages.account.control-center.document-trace', compact('type', 'reference', 'document', 'uniqueNo', 'transactions', 'totals'));
    }

    private function companyId(): int
    {
        return (int) (loggedCompanyId()['company_id'] ?? auth('web')->user()->company_id);
    }
}
