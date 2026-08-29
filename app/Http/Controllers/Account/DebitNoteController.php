<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\Bill;
use App\Models\ChartOfAccount;
use App\Models\DebitNote;
use App\Models\DebitNoteAllocation;
use App\Models\DebitNoteItem;
use App\Models\DebitNoteRefund;
use App\Models\TaxRate;
use App\Models\Transaction;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\PdfPaperSize;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class DebitNoteController extends Controller
{
    // ── Index ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = DebitNote::with(['vendor', 'allocations:id,debit_note_id,bill_id'])
            ->withCount('refunds')
            ->latest('date');

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('unique_no', 'ILIKE', "%{$term}%")
                    ->orWhere('ref_no', 'ILIKE', "%{$term}%")
                    ->orWhereHas('vendor', fn($v) => $v->where('name', 'ILIKE', "%{$term}%"));
            });
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $perPage = $request->per_page ?? 20;

        $debitNotes = $query->paginate($perPage)->withQueryString();

        $counts = DebitNote::selectRaw('
            COUNT(*) as total,
            SUM(total) as total_value,
            SUM(allocated_amount) as allocated_value,
            SUM(refunded_amount) as refunded_value,
            SUM(remaining_amount) as available_value,
            SUM(CASE 
                WHEN date BETWEEN ? AND ? 
                THEN 1 ELSE 0 
            END) as this_month
        ', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ])->first();

        $stats = [
            'total'       => $counts->total ?? 0,
            'this_month'  => $counts->this_month ?? 0,
            'total_value' => $counts->total_value ?? 0,
            'allocated_value' => $counts->allocated_value ?? 0,
            'refunded_value' => $counts->refunded_value ?? 0,
            'available_value' => $counts->available_value ?? 0,
        ];

        $vendors  = collect();

        if ($request->ajax()) {
            return response()->json([
                'html'       => view(
                    'pages.account.debit-note.table',
                    compact('debitNotes')
                )->render(),
                'pagination' => $debitNotes->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.account.debit-note.index', compact('debitNotes', 'stats', 'vendors'));
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function create()
    {
        $vendors  = collect(); // populated via AJAX Select2
        $accounts = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])->where('category', 'Expenses')->orderBy('name')->get();
        $taxRates = TaxRate::where('company_id', auth()->user()->company_id)
            ->whereIn('tax_type', ['default_tax', 'default_purchase_tax', 'purchase_tax'])
            ->orderBy('name')
            ->get();
        $bills    = collect(); // loaded via AJAX after vendor selection

        return view('pages.account.debit-note.create', compact('vendors', 'accounts', 'taxRates', 'bills'));
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id'                   => ['required', Rule::exists('vendors', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'account_id'                  => ['required', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'bill_id'                     => ['nullable', Rule::exists('bills', 'id')->where(fn ($q) => $q->where('company_id', loggedCompanyId()['company_id'])->where('approval_status', 'approved'))],
            'date'                        => 'required|date',
            'ref_no'                      => 'nullable|string|max:100',
            'note'                        => 'nullable|string',
            'items'                       => 'required|array|min:1',
            'items.*.account_id'          => ['required', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'items.*.tax_id'              => ['nullable', Rule::exists('tax_rates', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'items.*.description'         => 'required|string|max:255',
            'items.*.quantity'            => 'required|numeric|min:0.01',
            'items.*.price'               => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated) {
            [$subTotal, $discountTotal, $taxTotal, $total] = $this->calculateTotals($validated['items']);

            $period = AccountingPeriod::findPeriodForDate($validated['date'], loggedCompanyId()['company_id']);
            $usage = $this->calculateDebitNoteUsage($validated, $total);

            $debitNote = DebitNote::create([
                'company_id'           => auth('web')->user()->company_id,
                'accounting_period_id' => $period?->id,
                'vendor_id'            => $validated['vendor_id'],
                'account_id'           => $validated['account_id'],
                'bill_id'              => $validated['bill_id'] ?? null,
                'bill_unique_no'       => $usage['bill_unique_no'],
                'unique_no'            => DebitNote::generateUniqueNo(),
                'ref_no'               => $validated['ref_no'] ?? null,
                'date'                 => $validated['date'],
                'note'                 => $validated['note'] ?? null,
                'sub_total'            => $subTotal,
                'discount'             => $discountTotal,
                'tax'                  => $taxTotal,
                'total'                => $total,
                'allocated_amount'     => $usage['allocated_amount'],
                'remaining_amount'     => $usage['remaining_amount'],
                'status'               => 1,
                'created_by'           => Auth::id(),
                'updated_by'           => Auth::id(),
            ]);

            $this->createItems($debitNote, $validated['items']);
            $this->createJournalEntries($debitNote);
            $this->syncWithLinkedBill($debitNote);
        });

        return redirect()->route('account.debit-note.index')
            ->with('success', 'Debit note created successfully.');
    }

    // ── Show ─────────────────────────────────────────────────────────────────

    public function show(DebitNote $debitNote)
    {
        $debitNote->load([
            'vendor',
            'items.account',
            'items.taxRate',
            'bill',
            'createdBy',
            'allocations.bill',
            'allocations.createdBy',
            'refunds.account',
            'refunds.createdBy',
        ]);

        $openBills = Bill::where('vendor_id', $debitNote->vendor_id)
            ->where('company_id', $debitNote->company_id)
            ->where('approval_status', 'approved')
            ->where('remaining_amount', '>', 0)
            ->orderByDesc('date')
            ->get(['id', 'unique_no', 'invoice_no', 'date', 'remaining_amount']);

        return view('pages.account.debit-note.show', compact('debitNote', 'openBills'));
    }

    // ── Edit ─────────────────────────────────────────────────────────────────

    public function edit(DebitNote $debitNote)
    {
        $debitNote->load('items');
        // Only load the pre-selected vendor — the rest come via AJAX Select2
        $vendors  = $debitNote->vendor_id
            ? Vendor::where('id', $debitNote->vendor_id)->get()
            : collect();
        $accounts = ChartOfAccount::forCompany(loggedCompanyId()['company_id'])->where('category', 'Expenses')->orderBy('name')->get();
        $taxRates = TaxRate::where('company_id', auth()->user()->company_id)
            ->whereIn('tax_type', ['default_tax', 'default_purchase_tax', 'purchase_tax'])
            ->orderBy('name')
            ->get();
        $bills    = $debitNote->vendor_id
            ? Bill::where('vendor_id', $debitNote->vendor_id)->where('approval_status', 'approved')->orderByDesc('date')->get()
            : collect();

        return view(
            'pages.account.debit-note.edit',
            compact('debitNote', 'vendors', 'accounts', 'taxRates', 'bills')
        );
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, DebitNote $debitNote)
    {
        if ($this->hasLaterSettlements($debitNote)) {
            return back()->with('error', 'A debit note with later allocations or supplier refunds cannot be edited. Reverse those settlements first.');
        }

        $validated = $request->validate([
            'vendor_id'                   => ['required', Rule::exists('vendors', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'account_id'                  => ['required', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'bill_id'                     => ['nullable', Rule::exists('bills', 'id')->where(fn ($q) => $q->where('company_id', loggedCompanyId()['company_id'])->where('approval_status', 'approved'))],
            'date'                        => 'required|date',
            'ref_no'                      => 'nullable|string|max:100',
            'note'                        => 'nullable|string',
            'items'                       => 'required|array|min:1',
            'items.*.account_id'          => ['required', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'items.*.tax_id'              => ['nullable', Rule::exists('tax_rates', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            'items.*.description'         => 'required|string|max:255',
            'items.*.quantity'            => 'required|numeric|min:0.01',
            'items.*.price'               => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated, $debitNote) {
            [$subTotal, $discountTotal, $taxTotal, $total] = $this->calculateTotals($validated['items']);

            $period = AccountingPeriod::findPeriodForDate($validated['date'], $debitNote->company_id);

            $this->revertBillImpact($debitNote);
            $usage = $this->calculateDebitNoteUsage($validated, $total);

            $debitNote->update([
                'accounting_period_id' => $period?->id,
                'vendor_id'            => $validated['vendor_id'],
                'account_id'           => $validated['account_id'],
                'bill_id'              => $validated['bill_id'] ?? null,
                'bill_unique_no'       => $usage['bill_unique_no'],
                'ref_no'               => $validated['ref_no'] ?? null,
                'date'                 => $validated['date'],
                'note'                 => $validated['note'] ?? null,
                'sub_total'            => $subTotal,
                'discount'             => $discountTotal,
                'tax'                  => $taxTotal,
                'total'                => $total,
                'allocated_amount'     => $usage['allocated_amount'],
                'remaining_amount'     => $usage['remaining_amount'],
                'updated_by'           => Auth::id(),
            ]);

            // Reverse old journal entries, then recreate
            $debitNote->transactions()->delete();
            $debitNote->items()->delete();

            $this->createItems($debitNote, $validated['items']);
            $this->createJournalEntries($debitNote);
            $this->syncWithLinkedBill($debitNote);
        });

        return redirect()->route('account.debit-note.index')
            ->with('success', 'Debit note updated successfully.');
    }

    // ── Destroy ──────────────────────────────────────────────────────────────

    public function destroy(DebitNote $debitNote)
    {
        if ($this->hasLaterSettlements($debitNote)) {
            return back()->with('error', 'A debit note with later allocations or supplier refunds cannot be deleted.');
        }

        DB::transaction(function () use ($debitNote) {
            $this->revertBillImpact($debitNote);
            $debitNote->transactions()->delete();
            $debitNote->items()->delete();
            $debitNote->delete();
        });

        return redirect()->route('account.debit-note.index')
            ->with('success', 'Debit note deleted.');
    }

    // ── AJAX: Get vendor bills ────────────────────────────────────────────────

    public function getVendorBills(Vendor $vendor)
    {
        abort_unless((int) $vendor->company_id === (int) loggedCompanyId()['company_id'], 404);

        $bills = Bill::where('vendor_id', $vendor->id)
            ->where('company_id', loggedCompanyId()['company_id'])
            ->where('approval_status', 'approved')
            ->where('remaining_amount', '>', 0)
            ->orderByDesc('date')
            ->get(['id', 'unique_no', 'date', 'total', 'remaining_amount']);

        return response()->json($bills);
    }

    // ── AJAX: Get bill line items (for auto-fill when bill is selected) ─────────

    public function getBillItems(Bill $bill)
    {
        abort_unless((int) $bill->company_id === (int) loggedCompanyId()['company_id'], 404);

        $bill->load('billItems.tax', 'billItems.account');

        $items = $bill->billItems->map(fn($item) => [
            'description'         => $item->description,
            'account_id'          => $item->account_id,
            'account_name'        => $item->account?->name,
            'quantity'            => (float) $item->quantity,
            'price'               => (float) $item->price,
            'discount_percentage' => (float) ($item->discount ?? 0),
            'tax_id'              => $item->tax_id,
            'tax_rate'            => (float) ($item->tax?->tax_rate ?? 0),
            'tax_name'            => $item->tax ? "{$item->tax->name} ({$item->tax->tax_rate}%)" : null,
        ]);

        return response()->json($items);
    }

    public function allocate(Request $request, DebitNote $debitNote)
    {
        $validated = $request->validate([
            'bill_id' => [
                'required',
                Rule::exists('bills', 'id')->where(fn($query) => $query
                    ->where('company_id', $debitNote->company_id)
                    ->where('vendor_id', $debitNote->vendor_id)
                    ->where('approval_status', 'approved')),
            ],
            'allocation_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($validated, $debitNote) {
            $note = DebitNote::whereKey($debitNote->id)->lockForUpdate()->firstOrFail();
            $bill = Bill::whereKey($validated['bill_id'])
                ->where('company_id', $note->company_id)
                ->where('vendor_id', $note->vendor_id)
                ->where('approval_status', 'approved')
                ->lockForUpdate()
                ->firstOrFail();

            $amount = round((float) $validated['amount'], 2);
            $available = round((float) $note->remaining_amount, 2);
            $billRemaining = round((float) $bill->remaining_amount, 2);

            if ($amount > $available) {
                throw ValidationException::withMessages([
                    'amount' => 'The allocation cannot exceed the available supplier credit of ' . number_format($available, 2) . '.',
                ]);
            }

            if ($amount > $billRemaining) {
                throw ValidationException::withMessages([
                    'amount' => 'The allocation cannot exceed the bill balance of ' . number_format($billRemaining, 2) . '.',
                ]);
            }

            DebitNoteAllocation::create([
                'company_id' => $note->company_id,
                'debit_note_id' => $note->id,
                'bill_id' => $bill->id,
                'allocation_date' => $validated['allocation_date'],
                'amount' => $amount,
                'created_by' => Auth::id(),
            ]);

            $note->update([
                'allocated_amount' => round((float) $note->allocated_amount + $amount, 2),
                'remaining_amount' => max(0, round($available - $amount, 2)),
                'updated_by' => Auth::id(),
            ]);

            $this->reduceBillBalance($bill, $amount);
        });

        return back()->with('success', 'Supplier credit applied to the bill successfully.');
    }

    public function receiveRefund(Request $request, DebitNote $debitNote)
    {
        $validated = $request->validate([
            'refund_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->where(fn($query) => $query
                    ->where('company_id', $debitNote->company_id)
                    ->where('type', 'Account')
                    ->where('sub_type', 'BANK')),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated, $debitNote) {
            $note = DebitNote::whereKey($debitNote->id)->lockForUpdate()->firstOrFail();
            $amount = round((float) $validated['amount'], 2);
            $available = round((float) $note->remaining_amount, 2);

            if ($amount > $available) {
                throw ValidationException::withMessages([
                    'amount' => 'The refund cannot exceed the available supplier credit of ' . number_format($available, 2) . '.',
                ]);
            }

            $period = AccountingPeriod::findPeriodForDate($validated['refund_date'], $note->company_id);

            if (!$period) {
                throw ValidationException::withMessages([
                    'refund_date' => 'No active accounting period contains the refund date.',
                ]);
            }

            $refund = DebitNoteRefund::create([
                'company_id' => $note->company_id,
                'debit_note_id' => $note->id,
                'account_id' => $validated['account_id'],
                'accounting_period_id' => $period->id,
                'refund_no' => 'PENDING-' . bin2hex(random_bytes(8)),
                'refund_date' => $validated['refund_date'],
                'amount' => $amount,
                'description' => $validated['description'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $refundNo = 'DRF' . now()->format('Ym') . str_pad((string) $refund->id, 6, '0', STR_PAD_LEFT);
            $refund->update(['refund_no' => $refundNo]);

            $base = [
                'company_id' => $note->company_id,
                'accounting_period_id' => $period->id,
                'vendor_id' => $note->vendor_id,
                'payable_type' => 'vendor',
                'payable_id' => $note->vendor_id,
                'reference_id' => $note->id,
                'unique_no' => $refundNo,
                'invoice_no' => $note->unique_no,
                'date' => $validated['refund_date'],
                'type' => 'DebitNoteRefund',
                'description' => $validated['description'] ?? "Supplier refund for debit note {$note->unique_no}",
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ];

            Transaction::create(array_merge($base, [
                'account_id' => $validated['account_id'],
                'debit' => $amount,
                'credit' => 0,
            ]));

            $accountsPayableId = ChartOfAccount::getAccountsPayableId($note->company_id);
            if (!$accountsPayableId) {
                throw new \RuntimeException('Accounts Payable account is not configured for this company.');
            }

            Transaction::create(array_merge($base, [
                'account_id' => $accountsPayableId,
                'debit' => 0,
                'credit' => $amount,
            ]));

            $note->update([
                'refunded_amount' => round((float) $note->refunded_amount + $amount, 2),
                'remaining_amount' => max(0, round($available - $amount, 2)),
                'updated_by' => Auth::id(),
            ]);
        });

        return back()->with('success', 'Supplier refund received successfully.');
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function calculateDebitNoteUsage(array $data, float $total): array
    {
        $total = round($total, 2);
        $allocatedAmount = 0.0;
        $billUniqueNo = null;

        if (!empty($data['bill_id'])) {
            $bill = Bill::whereKey($data['bill_id'])
                ->where('company_id', loggedCompanyId()['company_id'])
                ->where('vendor_id', $data['vendor_id'])
                ->where('approval_status', 'approved')
                ->lockForUpdate()
                ->firstOrFail();
            $billUniqueNo = $bill->unique_no;
            $allocatedAmount = min($total, round((float) $bill->remaining_amount, 2));
        }

        return [
            'bill_unique_no' => $billUniqueNo,
            'allocated_amount' => $allocatedAmount,
            'remaining_amount' => max(0, round($total - $allocatedAmount, 2)),
        ];
    }

    private function syncWithLinkedBill(DebitNote $debitNote): void
    {
        if (!$debitNote->bill_id || (float) $debitNote->allocated_amount <= 0) {
            return;
        }

        $bill = Bill::whereKey($debitNote->bill_id)
            ->where('company_id', $debitNote->company_id)
            ->where('vendor_id', $debitNote->vendor_id)
            ->first();
        if (!$bill) {
            return;
        }

        DebitNoteAllocation::create([
            'company_id' => $debitNote->company_id,
            'debit_note_id' => $debitNote->id,
            'bill_id' => $bill->id,
            'allocation_date' => $debitNote->date,
            'amount' => $debitNote->allocated_amount,
            'created_by' => Auth::id(),
        ]);

        $this->reduceBillBalance($bill, (float) $debitNote->allocated_amount);
    }

    private function revertBillImpact(DebitNote $debitNote): void
    {
        $allocations = $debitNote->allocations()->get();

        if ($allocations->isNotEmpty()) {
            foreach ($allocations as $allocation) {
                $bill = Bill::find($allocation->bill_id);
                if ($bill) {
                    $this->restoreBillBalance($bill, (float) $allocation->amount);
                }
            }

            $debitNote->allocations()->delete();
            return;
        }

        if (!$debitNote->bill_id || (float) $debitNote->allocated_amount <= 0) {
            return;
        }

        $bill = Bill::find($debitNote->bill_id);
        if (!$bill) {
            return;
        }

        $bill->remaining_amount = round((float) $bill->remaining_amount + (float) $debitNote->allocated_amount, 2);

        $bill->payment_status = app(\App\Services\BillService::class)->paymentStatus((float) $bill->paid_amount, (float) $bill->remaining_amount);

        $bill->save();
    }

    private function reduceBillBalance(Bill $bill, float $amount): void
    {
        $bill->remaining_amount = max(0, round((float) $bill->remaining_amount - $amount, 2));
        $bill->payment_status = app(\App\Services\BillService::class)->paymentStatus((float) $bill->paid_amount, (float) $bill->remaining_amount);
        $bill->save();
    }

    private function restoreBillBalance(Bill $bill, float $amount): void
    {
        $bill->remaining_amount = round((float) $bill->remaining_amount + $amount, 2);
        $bill->payment_status = app(\App\Services\BillService::class)->paymentStatus((float) $bill->paid_amount, (float) $bill->remaining_amount);
        $bill->save();
    }

    private function hasLaterSettlements(DebitNote $debitNote): bool
    {
        if ($debitNote->refunds()->exists()) {
            return true;
        }

        $allocations = $debitNote->allocations()->get(['bill_id']);

        return $allocations->count() > 1
            || ($allocations->count() === 1 && (int) $allocations->first()->bill_id !== (int) $debitNote->bill_id);
    }

    private function calculateTotals(array $items): array
    {
        $subTotal = 0;
        $discountTotal = 0;
        $afterDiscountTotal = 0;
        $taxTotal = 0;

        foreach ($items as $item) {
            $qty      = (float) $item['quantity'];
            $price    = (float) $item['price'];
            $disc     = (float) ($item['discount_percentage'] ?? 0);
            $rowSub   = $qty * $price;
            $rowDisc  = $rowSub * $disc / 100;
            $rowAfter = $rowSub - $rowDisc;
            $subTotal += $rowSub;
            $discountTotal += $rowDisc;
            $afterDiscountTotal += $rowAfter;

            if (!empty($item['tax_id'])) {
                $tax = TaxRate::find($item['tax_id']);
                $taxTotal += $tax ? $rowAfter * $tax->tax_rate / 100 : 0;
            }
        }

        $total = $afterDiscountTotal + $taxTotal;

        return [round($subTotal, 2), round($discountTotal, 2), round($taxTotal, 2), round($total, 2)];
    }

    private function createItems(DebitNote $debitNote, array $items): void
    {
        foreach ($items as $item) {
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

            DebitNoteItem::create([
                'debit_note_id'       => $debitNote->id,
                'account_id'          => $item['account_id'],
                'tax_id'              => $item['tax_id'] ?? null,
                'description'         => $item['description'],
                'quantity'            => $qty,
                'price'               => $price,
                'discount_percentage' => $disc,
                'sub_total'           => round($rowSub, 2),
                'tax_amount'          => round($rowTax, 2),
                'total'               => round($rowAfter + $rowTax, 2),
            ]);
        }
    }

    /**
     * Debit Note journal entries (purchase return):
     *   DR  Accounts Payable       (reduce the AP liability)
     *   CR  Expense/Purchase Acct  (reduce the cost per item)
     *   CR  Input Tax Account      (reverse the input tax, if any)
     */
    private function createJournalEntries(DebitNote $debitNote): void
    {
        $debitNote->load('items');

        $companyId  = $debitNote->company_id;
        $periodId   = $debitNote->accounting_period_id;
        $vendorId   = $debitNote->vendor_id;
        $refId      = $debitNote->id;
        $date       = $debitNote->date;
        $uniqueNo   = $debitNote->unique_no;
        $userId     = Auth::id();

        $apAccountId  = ChartOfAccount::getAccountsPayableId($companyId);
        $taxAccountId = ChartOfAccount::getPurchaseTaxAccountId($companyId);

        $base = [
            'company_id'           => $companyId,
            'accounting_period_id' => $periodId,
            'vendor_id'            => $vendorId,
            'payable_type'         => 'vendor',
            'payable_id'           => $vendorId,
            'reference_id'         => $refId,
            'unique_no'            => $uniqueNo,
            'invoice_no'           => $uniqueNo,
            'date'                 => $date,
            'type'                 => 'DebitNote',
            'created_by'           => $userId,
            'updated_by'           => $userId,
        ];

        // DR Accounts Payable (total amount — reduces what we owe the vendor)
        Transaction::create(array_merge($base, [
            'account_id'  => $apAccountId,
            'debit'       => $debitNote->total,
            'credit'      => 0,
            'description' => "Debit Note {$uniqueNo} — AP reduction",
        ]));

        // CR each expense account per line item
        foreach ($debitNote->items as $item) {
            Transaction::create(array_merge($base, [
                'account_id'  => $item->account_id,
                'debit'       => 0,
                'credit'      => round((float) $item->sub_total - ($item->sub_total * (float) $item->discount_percentage / 100), 2),
                'description' => "Debit Note {$uniqueNo} — {$item->description}",
            ]));

            // CR Input Tax if applicable
            if ($item->tax_id && $item->tax_amount > 0 && $taxAccountId) {
                Transaction::create(array_merge($base, [
                    'account_id'  => $taxAccountId,
                    'debit'       => 0,
                    'credit'      => (float) $item->tax_amount,
                    'description' => "Debit Note {$uniqueNo} — input tax reversal",
                ]));
            }
        }
    }

    // ── Export PDF ────────────────────────────────────────────────────────────

    public function exportPdf($id, Request $request)
    {
        try {
            $columns = $request->query('columns') ? explode(',', $request->query('columns')) : [];

            $debitNote = DebitNote::with([
                'vendor',
                'items.account',
                'items.taxRate',
                'bill',
                'company',
            ])->findOrFail($id);

            $companyId = loggedCompanyId()['company_id'];
            $invoiceTemplate = \App\Models\InvoiceTemplate::where('company_id', $companyId)
                ->where('is_default', true)
                ->first() ?? new \App\Models\InvoiceTemplate();
            $paperSize = PdfPaperSize::normalize($request->query('paper_size'));
            $paperCssSize = PdfPaperSize::cssSize($paperSize);

            if (!empty($columns)) {
                $pdfData = [
                    'debitNote' => $debitNote,
                    'invoiceTemplate' => $invoiceTemplate,
                    'selectedColumns' => $columns,
                    'paperCssSize' => $paperCssSize,
                ];

                $pdf = Pdf::loadView('pdf.export-columns-debit-note', $pdfData);
            } else {
                $pdf = Pdf::loadView('pdf.debit-note-single-pdf', compact(
                    'debitNote',
                    'invoiceTemplate',
                    'paperCssSize'
                ));
            }

            PdfPaperSize::apply($pdf, $paperSize);

            return $pdf->download('debit_note_' . $debitNote->unique_no . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error exporting debit note PDF: ' . $e->getMessage());

            return back()->with('error', 'Failed to export debit note: ' . $e->getMessage());
        }
    }

    // ── Export Excel ─────────────────────────────────────────────────────────

    public function exportExcel($id, Request $request)
    {
        try {
            $columns = $request->query('columns') ? explode(',', $request->query('columns')) : [];

            $debitNote = DebitNote::with([
                'vendor',
                'items.account',
                'items.taxRate',
                'bill',
                'company',
            ])->findOrFail($id);

            return Excel::download(new \App\Exports\DebitNoteSingleExport($debitNote, $columns), 'debit_note_' . $debitNote->unique_no . '.xlsx');
        } catch (\Exception $e) {
            Log::error('Error exporting debit note Excel: ' . $e->getMessage());
            return back()->with('error', 'Failed to export debit note: ' . $e->getMessage());
        }
    }

    // ── Export Columns ───────────────────────────────────────────────────────

    public function getExportColumns(Request $request)
    {
        $dbColumns = Schema::getColumnListing('debit_notes');
        $formattedColumns = [];

        foreach ($dbColumns as $col) {
            if (!in_array($col, ['id', 'company_id', 'vendor_id', 'bill_id', 'account_id', 'accounting_period_id', 'deleted_at', 'updated_at', 'created_by', 'updated_by'])) {
                $label = ucwords(str_replace(['_', 'id'], [' ', ' ID'], $col));
                $formattedColumns[] = [
                    'key' => $col,
                    'label' => $label,
                    'checked' => in_array($col, ['unique_no', 'date', 'ref_no', 'sub_total', 'tax', 'total', 'allocated_amount', 'remaining_amount', 'status'])
                ];
            }
        }

        // Add related columns
        $formattedColumns[] = ['key' => 'vendor', 'label' => 'Vendor', 'checked' => true];
        $formattedColumns[] = ['key' => 'bill_unique_no', 'label' => 'Linked Bill', 'checked' => true];

        return response()->json($formattedColumns);
    }
}
