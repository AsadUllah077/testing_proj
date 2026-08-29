<?php

namespace App\Http\Controllers\Account\Report;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ReportAuditTrail;
use App\Models\User;
use Illuminate\Http\Request;

class ReportAuditTrailController extends Controller
{
    /* =========================
     * AUDIT TRAIL
     * ========================= */

    private const ACCOUNTING_MODELS = [
        'App\\Models\\Transaction',
        'App\\Models\\JournalEntry',
        'App\\Models\\Sale',
        'App\\Models\\SaleItem',
        'App\\Models\\Bill',
        'App\\Models\\BillItem',
        'App\\Models\\CreditNote',
        'App\\Models\\CreditNoteItem',
        'App\\Models\\ChartOfAccount',
        'App\\Models\\TaxRate',
        'App\\Models\\TaxPeriod',
        'App\\Models\\AccountingPeriod',
        'App\\Models\\PurchaseOrder',
        'App\\Models\\PurchaseOrderItem',
        'App\\Models\\PurchaseOrderPayment',
    ];

    private const MODEL_LABELS = [
        'App\\Models\\Transaction'          => 'Transaction',
        'App\\Models\\JournalEntry'         => 'Journal Entry',
        'App\\Models\\Sale'                 => 'Sale',
        'App\\Models\\SaleItem'             => 'Sale Item',
        'App\\Models\\Bill'                 => 'Bill',
        'App\\Models\\BillItem'             => 'Bill Item',
        'App\\Models\\CreditNote'           => 'Credit Note',
        'App\\Models\\CreditNoteItem'       => 'Credit Note Item',
        'App\\Models\\ChartOfAccount'       => 'Chart of Account',
        'App\\Models\\TaxRate'              => 'Tax Rate',
        'App\\Models\\TaxPeriod'            => 'Tax Period',
        'App\\Models\\AccountingPeriod'     => 'Accounting Period',
        'App\\Models\\PurchaseOrder'        => 'Purchase Order',
        'App\\Models\\PurchaseOrderItem'    => 'Purchase Order Item',
        'App\\Models\\PurchaseOrderPayment' => 'Purchase Order Payment',
    ];

    private const REPORT_NAMES = [
        'Journal', 'Ledger', 'Trial Balance', 'Balance Sheet',
        'Profit & Loss', 'Cash Flow', 'Cash Flow Direct',
        'Aged Payable', 'Aged Receivable',
    ];

    public function auditTrail(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];
        $source = $request->input('source', 'all'); // all, transactions, reports

        // --- Activity Logs (transaction changes) ---
        $activityQuery = null;
        if ($source === 'all' || $source === 'transactions') {
            $activityQuery = ActivityLog::with('user')
                ->where('company_id', $companyId)
                ->whereIn('loggable_type', self::ACCOUNTING_MODELS)
                ->latest('created_at');

            if ($request->filled('user_id')) {
                $activityQuery->where('user_id', $request->user_id);
            }
            if ($request->filled('model_type')) {
                $activityQuery->where('loggable_type', $request->model_type);
            }
            if ($request->filled('action')) {
                $activityQuery->where('action', $request->action);
            }
            $this->applyDateFilters($activityQuery, $request);
        }

        // --- Report Access Logs ---
        $reportQuery = null;
        if ($source === 'all' || $source === 'reports') {
            $reportQuery = ReportAuditTrail::with('user')
                ->where('company_id', $companyId)
                ->latest('created_at');

            if ($request->filled('user_id')) {
                $reportQuery->where('user_id', $request->user_id);
            }
            if ($request->filled('report_name')) {
                $reportQuery->where('report_name', $request->report_name);
            }
            if ($request->filled('action')) {
                $reportQuery->where('action', $request->action);
            }
            $this->applyDateFilters($reportQuery, $request);
        }

        // Merge both sources into a unified collection
        $perPage = $request->per_page ?? 20;
        $page = $request->input('page', 1);

        if ($source === 'transactions') {
            $logs = $activityQuery->paginate($perPage)->withQueryString();
        } elseif ($source === 'reports') {
            $logs = $reportQuery->paginate($perPage)->withQueryString();
        } else {
            // Merge both: get all, sort by created_at desc, paginate manually
            $activityLogs = $activityQuery->get()->map(fn($log) => (object) [
                'source'       => 'transaction',
                'created_at'   => $log->created_at,
                'user'         => $log->user,
                'type_label'   => self::MODEL_LABELS[$log->loggable_type] ?? class_basename($log->loggable_type),
                'action'       => $log->action,
                'changes'      => $log->changes,
                'filters'      => null,
                'export_format' => null,
                'report_name'  => null,
                'ip_address'   => null,
            ])->toBase();

            $reportLogs = $reportQuery->get()->map(fn($log) => (object) [
                'source'       => 'report',
                'created_at'   => $log->created_at,
                'user'         => $log->user,
                'type_label'   => $log->report_name,
                'action'       => $log->action,
                'changes'      => null,
                'filters'      => $log->filters,
                'export_format' => $log->export_format,
                'report_name'  => $log->report_name,
                'ip_address'   => $log->ip_address,
            ])->toBase();

            $merged = $activityLogs->merge($reportLogs)
                ->sortByDesc('created_at')
                ->values();

            $total = $merged->count();
            $items = $merged->forPage($page, $perPage);

            $logs = new \Illuminate\Pagination\LengthAwarePaginator(
                $items, $total, $perPage, $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        // Get users for filter dropdown
        $users = User::where('company_id', $companyId)
            ->orderBy('first_name')
            ->get();

        $modelLabels = self::MODEL_LABELS;
        $reportNames = self::REPORT_NAMES;

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.reports.partials.audit-trail-table', compact('logs', 'modelLabels', 'source'))->render(),
                'pagination' => $logs->hasPages() ? $logs->links()->toHtml() : '',
                'summary' => [
                    'total_logs' => $logs->total(),
                ],
            ]);
        }

        return view('pages.account.reports.audit-trail', compact('logs', 'users', 'modelLabels', 'reportNames', 'source'));
    }

    private function applyDateFilters($query, Request $request): void
    {
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->filled('date_range')) {
            [$from, $to] = resolveDateRange($request, 'month');
            $query->whereDate('created_at', '>=', $from)
                  ->whereDate('created_at', '<=', $to);
        }
    }
}
