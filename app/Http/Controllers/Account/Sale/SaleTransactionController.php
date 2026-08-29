<?php

namespace App\Http\Controllers\Account\Sale;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\SaleItem;
use App\Models\Transaction;
use Illuminate\Http\Request;

class SaleTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = SaleItem::query()->with(['sale.customer', 'account']);

        // Search by date range
        if ($request->filled('from_date')) {
            $query->whereHas('sale', function ($q) use ($request) {
                $q->whereDate('date', '>=', $request->from_date);
            });
        }

        if ($request->filled('to_date')) {
            $query->whereHas('sale', function ($q) use ($request) {
                $q->whereDate('date', '<=', $request->to_date);
            });
        }

        // Search by account
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Search by customer
        if ($request->filled('customer_id')) {
            $query->whereHas('sale', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
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
        $sale_transactions = $query->sortable()->paginate($perPage);

        $data = [
            'sale_transactions' => $sale_transactions,
            'totals' => $totals,
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.sale.sale-transaction.table', $data)->render(),
                'pagination' => $sale_transactions->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.account.sale.sale-transaction.index', $data);
    }
}
