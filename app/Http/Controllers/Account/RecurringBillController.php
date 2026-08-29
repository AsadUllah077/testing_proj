<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\RecurringBill;
use App\Models\RecurringBillItem;
use App\Models\TaxRate;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecurringBillController extends Controller
{
    public function index(Request $request)
    {
        $query = RecurringBill::with('vendor')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('title', 'ILIKE', "%{$term}%")
                    ->orWhereHas('vendor', fn($q) => $q->where('name', 'ILIKE', "%{$term}%"));
            });
        }

        $perPage = $request->per_page ?? 20;

        $recurringBills = $query->paginate($perPage)->withQueryString();

        $statusCounts = RecurringBill::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $stats = [
            'active'    => $statusCounts['active'] ?? 0,
            'paused'    => $statusCounts['paused'] ?? 0,
            'completed' => $statusCounts['completed'] ?? 0,
            'due_today' => RecurringBill::dueToday()->count(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'html'       => view(
                    'pages.account.recurring-bill.table',
                    compact('recurringBills')
                )->render(),
                'pagination' => $recurringBills->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.account.recurring-bill.index', compact('recurringBills', 'stats'));
    }

    public function create()
    {
        $vendors   = collect(); // populated via AJAX Select2
        $accounts  = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])->where('category', 'Expenses')->orderBy('name')->get();
        $taxRates  = TaxRate::where('company_id', auth()->user()->company_id)
            ->whereIn('tax_type', ['default_tax', 'default_purchase_tax', 'purchase_tax'])
            ->orderBy('name')
            ->get();

        return view('pages.account.recurring-bill.create', compact('vendors', 'accounts', 'taxRates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'vendor_id'          => 'required|exists:vendors,id',
            'account_id'         => ['required', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'frequency'          => 'required|in:weekly,monthly,quarterly,yearly',
            'interval'           => 'required|integer|min:1|max:12',
            'start_date'         => 'required|date',
            'end_date'           => 'nullable|date|after:start_date',
            'max_occurrences'    => 'nullable|integer|min:1',
            'payment_terms_days' => 'required|integer|min:0|max:365',
            'note'               => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.account_id' => ['required', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'items.*.tax_id'     => ['nullable', \Illuminate\Validation\Rule::exists('tax_rates', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.price'      => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated) {
            [$subTotal, $discountTotal, $taxTotal, $total] = $this->calculateTotals($validated['items']);

            $recurring = RecurringBill::create([
                'vendor_id'          => $validated['vendor_id'],
                'account_id'         => $validated['account_id'],
                'title'              => $validated['title'],
                'description'        => $validated['description'] ?? null,
                'frequency'          => $validated['frequency'],
                'interval'           => $validated['interval'],
                'start_date'         => $validated['start_date'],
                'end_date'           => $validated['end_date'] ?? null,
                'next_run_date'      => $validated['start_date'],
                'max_occurrences'    => $validated['max_occurrences'] ?? null,
                'payment_terms_days' => $validated['payment_terms_days'],
                'note'               => $validated['note'] ?? null,
                'status'             => 'active',
                'sub_total'          => $subTotal,
                'discount'           => $discountTotal,
                'tax'                => $taxTotal,
                'total'              => $total,
                'created_by'         => Auth::id(),
                'updated_by'         => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $qty      = (float) $item['quantity'];
                $price    = (float) $item['price'];
                $disc     = (float) ($item['discount_percentage'] ?? 0);
                $rowSub   = $qty * $price;
                $rowDisc  = $rowSub * $disc / 100;
                $rowAfter = $rowSub - $rowDisc;
                $rowTax   = 0;

                if (!empty($item['tax_id'])) {
                    $tax    = TaxRate::find($item['tax_id']);
                    $rowTax = $tax ? $rowAfter * $tax->tax_rate / 100 : 0;
                }

                RecurringBillItem::create([
                    'recurring_bill_id' => $recurring->id,
                    'account_id'        => $item['account_id'],
                    'tax_id'            => $item['tax_id'] ?? null,
                    'description'       => $item['description'],
                    'quantity'          => $qty,
                    'price'             => $price,
                    'discount_percentage' => $disc,
                    'sub_total'         => round($rowSub, 2),
                    'tax_amount'        => round($rowTax, 2),
                    'total'             => round($rowAfter + $rowTax, 2),
                ]);
            }
        });

        return redirect()->route('account.recurring-bill.index')
            ->with('success', 'Recurring bill created successfully.');
    }

    public function edit(RecurringBill $recurringBill)
    {
        $recurringBill->load('items');
        // Only load the pre-selected vendor — the rest come via AJAX Select2
        $vendors = $recurringBill->vendor_id
            ? Vendor::where('id', $recurringBill->vendor_id)->get()
            : collect();
        $accounts  = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])->where('category', 'Expenses')->orderBy('name')->get();
        $taxRates  = TaxRate::where('company_id', auth()->user()->company_id)
            ->whereIn('tax_type', ['default_tax', 'default_purchase_tax', 'purchase_tax'])
            ->orderBy('name')
            ->get();

        return view(
            'pages.account.recurring-bill.edit',
            compact('recurringBill', 'vendors', 'accounts', 'taxRates')
        );
    }

    public function update(Request $request, RecurringBill $recurringBill)
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'vendor_id'          => 'required|exists:vendors,id',
            'account_id'         => ['required', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'frequency'          => 'required|in:weekly,monthly,quarterly,yearly',
            'interval'           => 'required|integer|min:1|max:12',
            'end_date'           => 'nullable|date',
            'max_occurrences'    => 'nullable|integer|min:1',
            'payment_terms_days' => 'required|integer|min:0|max:365',
            'note'               => 'nullable|string',
            'status'             => 'required|in:active,paused,cancelled',
            'items'              => 'required|array|min:1',
            'items.*.account_id' => ['required', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'items.*.tax_id'     => ['nullable', \Illuminate\Validation\Rule::exists('tax_rates', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.price'      => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated, $recurringBill) {
            [$subTotal, $discountTotal, $taxTotal, $total] = $this->calculateTotals($validated['items']);

            $recurringBill->update([
                'vendor_id'          => $validated['vendor_id'],
                'account_id'         => $validated['account_id'],
                'title'              => $validated['title'],
                'description'        => $validated['description'] ?? null,
                'frequency'          => $validated['frequency'],
                'interval'           => $validated['interval'],
                'end_date'           => $validated['end_date'] ?? null,
                'max_occurrences'    => $validated['max_occurrences'] ?? null,
                'payment_terms_days' => $validated['payment_terms_days'],
                'note'               => $validated['note'] ?? null,
                'status'             => $validated['status'],
                'sub_total'          => $subTotal,
                'discount'           => $discountTotal,
                'tax'                => $taxTotal,
                'total'              => $total,
                'updated_by'         => Auth::id(),
            ]);

            // Replace items
            $recurringBill->items()->delete();

            foreach ($validated['items'] as $item) {
                $qty      = (float) $item['quantity'];
                $price    = (float) $item['price'];
                $disc     = (float) ($item['discount_percentage'] ?? 0);
                $rowSub   = $qty * $price;
                $rowDisc  = $rowSub * $disc / 100;
                $rowAfter = $rowSub - $rowDisc;
                $rowTax   = 0;

                if (!empty($item['tax_id'])) {
                    $tax    = TaxRate::find($item['tax_id']);
                    $rowTax = $tax ? $rowAfter * $tax->tax_rate / 100 : 0;
                }

                RecurringBillItem::create([
                    'recurring_bill_id' => $recurringBill->id,
                    'account_id'        => $item['account_id'],
                    'tax_id'            => $item['tax_id'] ?? null,
                    'description'       => $item['description'],
                    'quantity'          => $qty,
                    'price'             => $price,
                    'discount_percentage' => $disc,
                    'sub_total'         => round($rowSub, 2),
                    'tax_amount'        => round($rowTax, 2),
                    'total'             => round($rowAfter + $rowTax, 2),
                ]);
            }
        });

        return redirect()->route('account.recurring-bill.index')
            ->with('success', 'Recurring bill updated successfully.');
    }

    public function destroy(RecurringBill $recurringBill)
    {
        $recurringBill->delete();

        return redirect()->route('account.recurring-bill.index')
            ->with('success', 'Recurring bill deleted.');
    }

    public function toggleStatus(RecurringBill $recurringBill)
    {
        $newStatus = $recurringBill->status === 'active' ? 'paused' : 'active';
        $recurringBill->update(['status' => $newStatus]);

        return back()->with('success', "Schedule {$newStatus}.");
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function calculateTotals(array $items): array
    {
        $subTotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;
        $total = 0;

        foreach ($items as $item) {
            $qty     = (float) $item['quantity'];
            $price   = (float) $item['price'];
            $disc    = (float) ($item['discount_percentage'] ?? 0);
            $rowSub  = $qty * $price;
            $rowDiscount = $rowSub * $disc / 100;
            $rowAfter = $rowSub - $rowDiscount;
            $subTotal += $rowSub;
            $discountTotal += $rowDiscount;

            $rowTax = 0;
            if (!empty($item['tax_id'])) {
                $tax     = TaxRate::find($item['tax_id']);
                $rowTax = $tax ? $rowAfter * $tax->tax_rate / 100 : 0;
                $taxTotal += $rowTax;
            }

            $total += $rowAfter + $rowTax;
        }

        return [round($subTotal, 2), round($discountTotal, 2), round($taxTotal, 2), round($total, 2)];
    }
}
