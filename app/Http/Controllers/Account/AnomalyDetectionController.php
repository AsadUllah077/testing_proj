<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AuditAction;
use App\Services\Account\AnomalyDetectionService;
use Illuminate\Http\Request;

class AnomalyDetectionController extends Controller
{
    public function index(Request $request, AnomalyDetectionService $service)
    {
        $companyId = auth('web')->user()?->company_id;

        $result = $service->scan(
            fromDate: $request->input('from_date'),
            toDate: $request->input('to_date'),
            companyId: $companyId,
        );

        // Manual pagination for the merged collection
        $page = max(1, (int) $request->get('page', 1));
        $perPage = min(100, max(1, (int) $request->get('per_page', 20)));
        if ($request->severity) {
            $result['anomalies'] = collect($result['anomalies'])->where('severity', $request->severity);
        }
        $items = collect($result['anomalies']);

        $paginatedAnomalies = new \Illuminate\Pagination\LengthAwarePaginator(
            $items->forPage($page, $perPage),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        if ($request->ajax()) {
            return response()->json([
                'anomalies' => $paginatedAnomalies,
                'summary'   => $result['summary']
            ]);
        }

        return view('pages.account.anomaly-detection.index', [
            'anomalies' => $paginatedAnomalies,
            'summary'   => $result['summary']
        ]);
    }

    /**
     * Lightweight summary for dashboard widget (returns counts only).
     */
    public function summary(AnomalyDetectionService $service)
    {
        $result = $service->scan(companyId: auth('web')->user()?->company_id);
        return response()->json($result['summary']);
    }

    /**
     * View history of audit actions.
     */
    public function history(Request $request)
    {
        $companyId = auth('web')->user()?->company_id;

        $query = AuditAction::with(['transaction.account', 'user'])
            ->when($companyId, function ($query) use ($companyId) {
                $query->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)
                        ->orWhereHas('transaction', fn($txnQuery) => $txnQuery->where('company_id', $companyId));
                });
            })
            ->latest();

        // Filters
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $actions = $query->paginate($request->per_page ?? 20)->withQueryString();

        return view('pages.account.anomaly-detection.history', compact('actions'));
    }

    /**
     * Execute an audit action on a transaction.
     */
    public function executeAction(Request $request, \App\Services\Account\AuditActionService $auditService)
    {
        $request->validate([
            'transaction_id' => 'required|integer',
            'action'         => 'required|string|in:freeze,reverse,monitor,dismiss',
            'notes'          => 'nullable|string',
        ]);

        $action = $request->input('action');
        $transactionId = $request->input('transaction_id');
        $notes = $request->input('notes');

        try {
            $result = match ($action) {
                'freeze'  => $auditService->freeze($transactionId, $notes),
                'reverse' => $auditService->reverse($transactionId, $notes),
                'monitor' => $auditService->monitor($transactionId, $notes),
                'dismiss' => $auditService->dismiss($transactionId, $notes),
            };

            $pastTense = [
                'freeze' => 'frozen',
                'reverse' => 'reversed',
                'monitor' => 'marked for monitoring',
                'dismiss' => 'dismissed',
            ][$action];

            return response()->json([
                'success' => true,
                'message' => "Transaction successfully {$pastTense}.",
                'data'    => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
