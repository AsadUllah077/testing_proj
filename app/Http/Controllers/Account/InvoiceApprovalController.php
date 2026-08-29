<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Sale;
use App\Services\Account\ApprovalRiskService;
use App\Services\SaleService;
use App\Services\BillService;
use App\Support\SelectedRecordOrdering;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceApprovalController extends Controller
{
    // ── Sales Invoice Approvals ────────────────────────────────────────────────

    public function salesIndex(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];
        $status    = $request->get('status', 'submitted');

        $query = Sale::with([
            'customer:id,name',
            'createdBy' => function ($q) {
                $q->select('id', 'first_name', 'last_name')->without('company');
            },
        ])
            ->where('company_id', $companyId)
            ->where('approval_status', $status);

        $query = $this->applyFilters($query, $request, 'sale');

        $selectedSaleIds = SelectedRecordOrdering::ids($request, 'selected_sale_ids');

        $invoices = SelectedRecordOrdering::apply($query, $selectedSaleIds)->latest()
            ->paginate($request->per_page ?? 20);

        $counts = Sale::where('company_id', $companyId)
            ->selectRaw("approval_status, COUNT(*) as total")
            ->groupBy('approval_status')
            ->pluck('total', 'approval_status');

        // AI risk scores for the current page of sales.
        $riskScores = app(ApprovalRiskService::class)->scoreSales($invoices);

        $data = [
            'invoices' => $invoices,
            'counts' => $counts,
            'status' => $status,
            'riskScores' => $riskScores,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.approval.sale-table', $data)->render(),
                'pagination' => $invoices->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.account.approval.sales-index', $data);
    }

    public function submitSale(Sale $sale)
    {
        try {
            return $this->submitForApproval($sale);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'The invoice could not be submitted or auto-posted: ' . $e->getMessage());
        }
    }

    public function approveSale(Sale $sale)
    {
        if ($sale->approval_status !== 'submitted') {
            return back()->with('error', 'Only submitted records can be approved.');
        }

        try {
            DB::transaction(function () use ($sale) {
                $locked = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
                $locked->update([
                    'approval_status' => 'approved',
                    'approved_by' => auth('web')->id(),
                    'approved_at' => now(),
                    'rejection_reason' => null,
                ]);
                app(SaleService::class)->postApprovedSale($locked);
            });

            return back()->with('success', "Sale #{$sale->invoice_no} approved and posted.");
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Approval failed because the invoice journal could not be posted: ' . $e->getMessage());
        }
    }

    public function rejectSale(Request $request, Sale $sale)
    {
        return $this->rejectRecord($request, $sale, [
            'rejection_reason' => $request->reason,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function bulkSubmitSales(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:sales,id'
        ]);

        $companyId = loggedCompanyId()['company_id'];
        $sales = Sale::where('company_id', $companyId)
            ->whereIn('id', $request->ids)
            ->whereIn('approval_status', ['draft', 'rejected'])
            ->get();

        $submittedCount = 0;
        foreach ($sales as $sale) {
            $sale->update(['approval_status' => 'submitted']);
            $submittedCount++;
        }

        return back()->with('success', "{$submittedCount} invoice(s) submitted for approval successfully.");
    }

    public function bulkApproveSales(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:sales,id'
        ]);

        $companyId = loggedCompanyId()['company_id'];
        $sales = Sale::where('company_id', $companyId)
            ->whereIn('id', $request->ids)
            ->where('approval_status', 'submitted')
            ->get();

        $approvedCount = 0;
        try {
            DB::transaction(function () use ($sales, &$approvedCount) {
                foreach ($sales as $sale) {
                    $locked = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
                    $locked->update([
                        'approval_status' => 'approved',
                        'approved_by' => auth('web')->id(),
                        'approved_at' => now(),
                        'rejection_reason' => null,
                    ]);
                    app(SaleService::class)->postApprovedSale($locked);
                    $approvedCount++;
                }
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'No invoices were approved because one or more journals could not be posted: ' . $e->getMessage());
        }

        return back()->with('success', "{$approvedCount} invoice(s) approved successfully.");
    }

    public function bulkRejectSales(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:sales,id',
            'reason' => 'required|string|max:500'
        ]);

        $companyId = loggedCompanyId()['company_id'];
        $sales = Sale::where('company_id', $companyId)
            ->whereIn('id', $request->ids)
            ->where('approval_status', 'submitted')
            ->get();

        $rejectedCount = 0;
        foreach ($sales as $sale) {
            $sale->update([
                'approval_status' => 'rejected',
                'rejection_reason' => $request->reason,
                'approved_by' => null,
                'approved_at' => null,
            ]);
            $rejectedCount++;
        }

        return back()->with('success', "{$rejectedCount} invoice(s) rejected successfully.");
    }

    // ── Bill Approvals ─────────────────────────────────────────────────────────

    public function billsIndex(Request $request)
    {
        $companyId = loggedCompanyId()['company_id'];
        $status    = $request->get('status', 'submitted');

        $query = Bill::with([
            'vendor:id,name',
            'creator' => function ($q) {
                $q->select('id', 'first_name', 'last_name')->without('company');
            },
        ])
            ->where('company_id', $companyId)
            ->where('approval_status', $status);

        $query = $this->applyFilters($query, $request, 'bill');

        $selectedBillIds = SelectedRecordOrdering::ids($request, 'selected_bill_ids');

        $bills = SelectedRecordOrdering::apply($query, $selectedBillIds)->latest()
            ->paginate($request->per_page ?? 20);

        $counts = Bill::where('company_id', $companyId)
            ->selectRaw("approval_status, COUNT(*) as total")
            ->groupBy('approval_status')
            ->pluck('total', 'approval_status');

        // AI risk scores for the current page of bills.
        $riskScores = app(ApprovalRiskService::class)->scoreBills($bills);

        $data = [
            'bills' => $bills,
            'counts' => $counts,
            'status' => $status,
            'riskScores' => $riskScores,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.approval.bill-table', $data)->render(),
                'pagination' => $bills->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.account.approval.bills-index', $data);
    }

    public function submitBill(Bill $bill)
    {
        try {
            return $this->submitForApproval($bill);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approveBill(Bill $bill)
    {
        try {
            return $this->approveRecord($bill, [
                'bill_approved_by' => auth('web')->id(),
                'bill_approved_at' => now(),
                'bill_rejection_reason' => null,
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function rejectBill(Request $request, Bill $bill)
    {
        return $this->rejectRecord($request, $bill, [
            'bill_rejection_reason' => $request->reason,
            'bill_approved_by' => null,
            'bill_approved_at' => null,
        ]);
    }

    public function bulkSubmitBills(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:bills,id'
        ]);

        $companyId = loggedCompanyId()['company_id'];
        $bills = Bill::where('company_id', $companyId)
            ->whereIn('id', $request->ids)
            ->whereIn('approval_status', ['draft', 'rejected'])
            ->get();

        $submittedCount = 0;
        foreach ($bills as $bill) {
            $bill->update(['approval_status' => 'submitted']);
            $submittedCount++;
        }

        return back()->with('success', "{$submittedCount} bill(s) submitted for approval successfully.");
    }

    public function bulkApproveBills(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:bills,id'
        ]);

        $companyId = loggedCompanyId()['company_id'];
        $bills = Bill::where('company_id', $companyId)
            ->whereIn('id', $request->ids)
            ->where('approval_status', 'submitted')
            ->get();

        $approvedCount = 0;
        foreach ($bills as $bill) {
            try {
                DB::transaction(function () use ($bill) {
                    $locked = Bill::whereKey($bill->id)->lockForUpdate()->firstOrFail();
                    $locked->update([
                        'approval_status' => 'approved',
                        'bill_approved_by' => auth('web')->id(),
                        'bill_approved_at' => now(),
                        'bill_rejection_reason' => null,
                    ]);
                    app(BillService::class)->postApprovedBill($locked);
                });
                $approvedCount++;
            } catch (\Throwable $e) {
                return back()->with('error', "Bill #{$bill->invoice_no} was not approved: {$e->getMessage()}");
            }
        }

        return back()->with('success', "{$approvedCount} bill(s) approved successfully.");
    }

    public function bulkRejectBills(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:bills,id',
            'reason' => 'required|string|max:500'
        ]);

        $companyId = loggedCompanyId()['company_id'];
        $bills = Bill::where('company_id', $companyId)
            ->whereIn('id', $request->ids)
            ->where('approval_status', 'submitted')
            ->get();

        $rejectedCount = 0;
        foreach ($bills as $bill) {
            $bill->update([
                'approval_status' => 'rejected',
                'bill_rejection_reason' => $request->reason,
                'bill_approved_by' => null,
                'bill_approved_at' => null,
            ]);
            $rejectedCount++;
        }

        return back()->with('success', "{$rejectedCount} bill(s) rejected successfully.");
    }

    private function applyFilters($query, Request $request, $type = 'sale')
    {
        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }

        if ($type === 'sale' && $request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($type === 'bill' && $request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('invoice_no')) {
            $query->where('invoice_no', 'like', "%{$request->invoice_no}%");
        }

        return $query;
    }

    private function submitForApproval($model)
    {
        if (!in_array($model->approval_status, ['draft', 'rejected'])) {
            return back()->with('error', 'Only draft or rejected records can be submitted.');
        }

        // AI risk scoring — auto-approve low-risk items.
        $riskService = app(ApprovalRiskService::class);
        $risk = $model instanceof Bill
            ? $riskService->scoreBill($model)
            : $riskService->scoreSale($model);

        if ($risk['tier'] === 'low') {
            $approvalFields = $model instanceof Bill
                ? ['approval_status' => 'approved', 'bill_approved_by' => auth('web')->id(), 'bill_approved_at' => now(), 'bill_rejection_reason' => null]
                : ['approval_status' => 'approved', 'approved_by' => auth('web')->id(), 'approved_at' => now(), 'rejection_reason' => null];

            if ($model instanceof Sale) {
                DB::transaction(function () use ($model, $approvalFields) {
                    $locked = Sale::whereKey($model->id)->lockForUpdate()->firstOrFail();
                    $locked->update($approvalFields);
                    app(SaleService::class)->postApprovedSale($locked);
                });
            } else {
                DB::transaction(function () use ($model, $approvalFields) {
                    $locked = Bill::whereKey($model->id)->lockForUpdate()->firstOrFail();
                    $locked->update($approvalFields);
                    app(BillService::class)->postApprovedBill($locked);
                });
            }

            return back()->with(
                'success',
                class_basename($model) . ' #' . $model->invoice_no . ' auto-approved (low risk, score ' . $risk['score'] . ').'
            );
        }

        $model->update(['approval_status' => 'submitted']);

        $msg = class_basename($model) . ' #' . $model->invoice_no . ' submitted for approval';
        if ($risk['tier'] === 'high') {
            $msg .= ' — flagged as high risk (score ' . $risk['score'] . ': ' . implode(', ', $risk['reasons']) . ')';
        }
        $msg .= '.';

        return back()->with('success', $msg);
    }

    private function approveRecord($model, $fields = [])
    {
        if ($model->approval_status !== 'submitted') {
            return back()->with('error', 'Only submitted records can be approved.');
        }

        $approvalFields = array_merge(['approval_status' => 'approved'], $fields);
        if ($model instanceof Bill) {
            DB::transaction(function () use ($model, $approvalFields) {
                $locked = Bill::whereKey($model->id)->lockForUpdate()->firstOrFail();
                $locked->update($approvalFields);
                app(BillService::class)->postApprovedBill($locked);
            });
        } else {
            $model->update($approvalFields);
        }

        return back()->with('success', class_basename($model) . ' #' . $model->invoice_no . ' approved.');
    }

    private function rejectRecord(Request $request, $model, $fields = [])
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if ($model->approval_status !== 'submitted') {
            return back()->with('error', 'Only submitted records can be rejected.');
        }

        $model->update(array_merge([
            'approval_status' => 'rejected',
        ], $fields));

        return back()->with('success', class_basename($model) . ' #' . $model->invoice_no . ' rejected.');
    }
}
