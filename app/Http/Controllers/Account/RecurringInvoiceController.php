<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\RecurringInvoice;
use App\Models\RecurringInvoiceItem;
use App\Models\TaxRate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecurringInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = RecurringInvoice::with('customer')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('title', 'ILIKE', "%{$term}%")
                    ->orWhereHas('customer', fn($q) => $q->where('name', 'ILIKE', "%{$term}%"));
            });
        }

        $perPage = $request->per_page ?? 20;

        $recurringInvoices = $query->paginate($perPage)->withQueryString();

        $statusCounts = RecurringInvoice::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $stats = [
            'active'    => $statusCounts['active'] ?? 0,
            'paused'    => $statusCounts['paused'] ?? 0,
            'completed' => $statusCounts['completed'] ?? 0,
            'due_today' => RecurringInvoice::dueToday()->count(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'html'       => view(
                    'pages.account.recurring-invoice.table',
                    compact('recurringInvoices')
                )->render(),
                'pagination' => $recurringInvoices->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.account.recurring-invoice.index', compact('recurringInvoices', 'stats'));
    }

    public function create()
    {
        $customers = collect(); // populated via AJAX Select2
        $accounts  = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])->where('category', 'Revenue')->orderBy('name')->get();
        $taxRates  = TaxRate::where('company_id', auth()->user()->company_id)
            ->whereIn('tax_type', ['default_tax', 'default_sale_tax', 'sales_tax'])
            ->orderBy('name')
            ->get();

        return view('pages.account.recurring-invoice.create', compact('customers', 'accounts', 'taxRates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'customer_id'        => 'required|exists:customers,id',
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

            $recurring = RecurringInvoice::create([
                'customer_id'        => $validated['customer_id'],
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

                RecurringInvoiceItem::create([
                    'recurring_invoice_id' => $recurring->id,
                    'account_id'           => $item['account_id'],
                    'tax_id'               => $item['tax_id'] ?? null,
                    'description'          => $item['description'],
                    'quantity'             => $qty,
                    'price'                => $price,
                    'discount_percentage'  => $disc,
                    'sub_total'            => round($rowSub, 2),
                    'tax_amount'           => round($rowTax, 2),
                    'total'                => round($rowAfter + $rowTax, 2),
                ]);
            }
        });

        return redirect()->route('account.recurring-invoice.index')
            ->with('success', 'Recurring invoice created successfully.');
    }

    public function edit(RecurringInvoice $recurringInvoice)
    {
        $recurringInvoice->load('items');
        // Only load the pre-selected customer — the rest come via AJAX Select2
        $customers = $recurringInvoice->customer_id
            ? Customer::where('id', $recurringInvoice->customer_id)->get()
            : collect();
        $accounts  = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])->where('category', 'Revenue')->orderBy('name')->get();
        $taxRates  = TaxRate::where('company_id', auth()->user()->company_id)
            ->whereIn('tax_type', ['default_tax', 'default_sale_tax', 'sales_tax'])
            ->orderBy('name')
            ->get();

        return view(
            'pages.account.recurring-invoice.edit',
            compact('recurringInvoice', 'customers', 'accounts', 'taxRates')
        );
    }

    public function update(Request $request, RecurringInvoice $recurringInvoice)
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'customer_id'        => 'required|exists:customers,id',
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

        DB::transaction(function () use ($validated, $recurringInvoice) {
            [$subTotal, $discountTotal, $taxTotal, $total] = $this->calculateTotals($validated['items']);

            $recurringInvoice->update([
                'customer_id'        => $validated['customer_id'],
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
            $recurringInvoice->items()->delete();

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

                RecurringInvoiceItem::create([
                    'recurring_invoice_id' => $recurringInvoice->id,
                    'account_id'           => $item['account_id'],
                    'tax_id'               => $item['tax_id'] ?? null,
                    'description'          => $item['description'],
                    'quantity'             => $qty,
                    'price'                => $price,
                    'discount_percentage'  => $disc,
                    'sub_total'            => round($rowSub, 2),
                    'tax_amount'           => round($rowTax, 2),
                    'total'                => round($rowAfter + $rowTax, 2),
                ]);
            }
        });

        return redirect()->route('account.recurring-invoice.index')
            ->with('success', 'Recurring invoice updated successfully.');
    }

    public function destroy(RecurringInvoice $recurringInvoice)
    {
        $recurringInvoice->delete();

        return redirect()->route('account.recurring-invoice.index')
            ->with('success', 'Recurring invoice deleted.');
    }

    public function toggleStatus(RecurringInvoice $recurringInvoice)
    {
        $newStatus = $recurringInvoice->status === 'active' ? 'paused' : 'active';
        $recurringInvoice->update(['status' => $newStatus]);

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
