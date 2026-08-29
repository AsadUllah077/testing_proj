<?php

namespace App\Http\Controllers\Account\Bill;

use App\Http\Controllers\Controller;
use App\Models\BillItem;
use App\Models\ChartOfAccount;
use App\Models\Vendor;
use Illuminate\Http\Request;

class BillTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = BillItem::query()->with(['bill.vendor', 'account']);

        // Search by date range
        if ($request->filled('from_date')) {
            $query->whereHas('bill', function ($q) use ($request) {
                $q->whereDate('date', '>=', $request->from_date);
            });
        }

        if ($request->filled('to_date')) {
            $query->whereHas('bill', function ($q) use ($request) {
                $q->whereDate('date', '<=', $request->to_date);
            });
        }

        // Search by account
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Search by vendor
        if ($request->filled('vendor_id')) {
            $query->whereHas('bill', function ($q) use ($request) {
                $q->where('vendor_id', $request->vendor_id);
            });
        }

        // Search by product name
        if ($request->filled('product_name')) {
            $query->where('item', 'like', '%' . $request->product_name . '%');
        }

        // Get per page value (default 20)
        $perPage = $request->get('per_page', 20);

        // Calculate totals before pagination
        $totals = [
            'total_kg' => $query->sum('kg'),
            'total_quantity' => $query->sum('quantity'),
            'total_amount' => $query->sum('total')
        ];

        // Paginate results
        $bill_transactions = $query->sortable()->paginate($perPage);

        $data = [
            'bill_transactions' => $bill_transactions,
            'totals' => $totals,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.bill.bill-transaction.table', $data)->render(),
                'pagination' => $bill_transactions->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.account.bill.bill-transaction.index', $data);
    }
}
