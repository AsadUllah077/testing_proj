<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreditNoteRequest;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\CreditNote;
use App\Models\CreditNoteAllocation;
use App\Models\CreditNoteAuditLog;
use App\Models\CreditNoteItem;
use App\Models\CreditNoteRefund;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\TaxRate;
use App\Models\Transaction;
use App\Services\CreditNoteAuditService;
use App\Services\CreditNoteService;
use App\Support\PdfPaperSize;
use Barryvdh\DomPDF\Facade\Pdf;
// use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class CreditNoteController extends Controller
{
    /**
     * Display a listing of the credit notes.
     */
    public function __construct(
        private CreditNoteService $creditNoteService
    ) {}
    public function index(Request $request)
    {
        try {
            DB::beginTransaction();

            $query = CreditNote::with(['customer', 'currency', 'createdBy', 'updatedBy', 'allocations:id,credit_note_id,sale_id'])
                ->withCount('refunds');

            // Search filter
            if ($request->has('search') && ! empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_no', 'like', "%{$search}%")
                        ->orWhere('ref_no', 'like', "%{$search}%")
                        ->orWhere('unique_no', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Date range filter
            if ($request->has('from_date') && ! empty($request->from_date)) {
                $query->whereDate('date', '>=', $request->from_date);
            }

            if ($request->has('to_date') && ! empty($request->to_date)) {
                $query->whereDate('date', '<=', $request->to_date);
            }

            // Customer filter
            if ($request->has('customer_id') && ! empty($request->customer_id)) {
                $query->where('customer_id', $request->customer_id);
            }

            // Get customers for filter dropdown
            $customers = [];
            if ($request->filled('customer_id')) {
                $customers = Customer::where('id', $request->customer_id)->get(['id', 'name']);
            }

            // Pagination
            $perPage = $request->per_page ?? 20;
            $creditNotes = $query->sortable()
                ->paginate($perPage)
                ->withQueryString();

            // Calculate totals
            $totalAmount = $creditNotes->sum('total_after_discount');
            $totalPaid = $creditNotes->sum('paid_amount');
            $totalAllocated = $creditNotes->sum('allocated_amount');
            $totalRemaining = $creditNotes->sum('remaining_amount');

            DB::commit();

            $data = [
                'creditNotes' => $creditNotes,
                'customers' => $customers,
                'totalAmount' => $totalAmount,
                'totalPaid' => $totalPaid,
                'totalAllocated' => $totalAllocated,
                'totalRemaining' => $totalRemaining,
            ];

            if ($request->ajax()) {
                return response()->json([
                    'html' => view('pages.account.sale.credit-note.partials.table', $data)->render(),
                    'pagination' => $creditNotes->appends(request()->query())->links()->toHtml(),
                ]);
            }

            return view('pages.account.sale.credit-note.index', $data);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error fetching credit notes: ' . $e->getMessage());

            return back()->with('error', 'Failed to load credit notes: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new credit note.
     */
    public function create()
    {
        try {
            DB::beginTransaction();
            $accounts = byOrderCoa();

            $currencies = Currency::get();

            DB::commit();

            return view('pages.account.sale.credit-note.create', compact('currencies', 'accounts'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in credit note create form: ' . $e->getMessage());

            return back()->with('error', 'Failed to load credit note form: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created credit note in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ref_no' => 'nullable|string|max:255',
                'invoice_no' => 'nullable|string|max:255',
                'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', loggedCompanyId()['company_id'])],
                'sale_id' => ['nullable', Rule::exists('sales', 'id')->where('company_id', loggedCompanyId()['company_id'])],
                'date' => 'required|date',
                'currency_id' => 'required|exists:currencies,id',
                'payment_type' => ['nullable', Rule::exists('chart_of_accounts', 'id')->where(fn($query) => $query
                    ->where('company_id', loggedCompanyId()['company_id'])->where('type', 'Account')->where('sub_type', 'BANK'))],
                'paid_amount' => 'nullable|numeric|min:0',
                'items' => 'required|array|min:1',
                'items.*.item' => 'required|string|max:255',
                'items.*.description' => 'nullable|string',
                'items.*.hs_code' => 'nullable|string|max:255',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.kg' => 'nullable|integer|min:0',
                'items.*.price' => 'required|numeric|min:0',
                'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
                'items.*.account_id' => ['required', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
                'items.*.tax_id' => ['nullable', Rule::exists('tax_rates', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            ]);

            $validator->after(function ($validator) use ($request) {
                if ((float) $request->input('paid_amount', 0) > 0 && !$request->filled('payment_type')) {
                    $validator->errors()->add('payment_type', 'A bank/cash account is required for a customer refund.');
                }
                if ($request->filled('sale_id') && $request->filled('customer_id')) {
                    $matchesCustomer = Sale::whereKey($request->input('sale_id'))
                        ->where('company_id', loggedCompanyId()['company_id'])
                        ->where('customer_id', $request->input('customer_id'))
                        ->where('approval_status', 'approved')
                        ->exists();
                    if (!$matchesCustomer) {
                        $validator->errors()->add('sale_id', 'The selected invoice does not belong to this customer.');
                    }
                }
            });

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all(),
                ], 422);
            }

            DB::beginTransaction();

            $creditNote = $this->creditNoteService->createCreditNote($request->all(), $request);
            CreditNoteAuditService::logCreation($creditNote, $request);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Credit note created successfully!',
                'credit_note_id' => $creditNote->id,
                'redirect_url' => route('account.sale.credit-notes.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating credit note: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * Get customer sales for dropdown.
     */
    public function getCustomerSales($customerId)
    {
        DB::beginTransaction();

        try {
            $sales = Sale::where('customer_id', $customerId)
                ->where('approval_status', 'approved')
                ->where('credit_note_status', 0)
                ->get(['id', 'unique_no', 'ref_no', 'invoice_no', 'total']);

            DB::commit();

            return response()->json($sales);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error fetching customer sales: ' . $e->getMessage(), ['customer_id' => $customerId]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customer sales',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get sale details including items.
     */
    public function getSaleDetails($saleId)
    {
        DB::beginTransaction();

        try {
            $sale = Sale::with(['saleItems', 'currency'])
                ->where('approval_status', 'approved')
                ->find($saleId);

            if (! $sale) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Sale not found',
                ], 404);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'sale' => $sale,
                'items' => $sale->saleItems->map(function ($item) {
                    return [
                        'item' => $item->item ?? $item->manual_item,
                        'description' => $item->description,
                        'hs_code' => $item->hs_code,
                        'quantity' => $item->quantity,
                        'kg' => $item->kg,
                        'price' => $item->price,
                        'discount' => $item->discount, // SaleItem stores it as discount
                        'account_id' => $item->account_id,
                        'tax_id' => $item->tax_id,
                        'total' => $item->total,
                        'company_id' => $item->company_id,
                        'accounting_period_id' => $item->accounting_period_id,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error fetching sale details: ' . $e->getMessage(), ['sale_id' => $saleId]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sale details',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get accounts for dropdown.
     */
    public function getAccounts()
    {
        DB::beginTransaction();

        try {
            $accounts = byOrderCoa();

            DB::commit();

            return response()->json($accounts);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error fetching accounts: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch accounts',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get taxes for dropdown.
     */
    public function getTaxes()
    {
        DB::beginTransaction();

        try {
            $taxes = TaxRate::where('company_id', auth()->user()->company_id)
                ->whereIn('tax_type', ['default_tax', 'default_sale_tax', 'sales_tax'])
                ->get(['id', 'name', 'code', 'tax_rate', 'tax_type']);

            DB::commit();

            return response()->json($taxes);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error fetching taxes: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch taxes',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get currencies for dropdown.
     */
    public function getCurrencies()
    {
        DB::beginTransaction();

        try {
            $currencies = Currency::get(['id', 'name', 'code', 'symbol']);

            DB::commit();

            return response()->json($currencies);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error fetching currencies: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch currencies',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified credit note.
     */
    public function show($id)
    {
        DB::beginTransaction();

        try {
            $creditNote = CreditNote::with([
                'customer',
                'currency',
                'creditNoteItems.account',
                'creditNoteItems.tax',
                'sale',
                'createdBy',
                'allocations.sale',
                'allocations.createdBy',
                'refunds.account',
                'refunds.createdBy',
            ])->findOrFail($id);

            $openSales = Sale::where('customer_id', $creditNote->customer_id)
                ->where('company_id', $creditNote->company_id)
                ->where('approval_status', 'approved')
                ->where('remaining_amount', '>', 0)
                ->orderByDesc('date')
                ->get(['id', 'unique_no', 'invoice_no', 'date', 'remaining_amount']);

            DB::commit();

            return view('pages.account.sale.credit-note.show', compact('creditNote', 'openSales'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error fetching credit note: ' . $e->getMessage(), ['credit_note_id' => $id]);

            return back()->with('error', 'Credit note not found: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified credit note.
     */
    public function edit($id)
    {
        DB::beginTransaction();

        try {
            $creditNote = CreditNote::with('creditNoteItems', 'customer')->findOrFail($id);

            $customers = Customer::where('id', '!=', $creditNote->customer_id)
                ->orderBy('id')
                ->limit(1000)
                ->get();

            $creditNoteCustomer = $creditNote->customer;

            $customers = $customers->prepend($creditNoteCustomer)->unique('id')->values();
            $currencies = Currency::get();

            DB::commit();

            return view('pages.account.sale.credit-note.edit', compact('creditNote', 'customers', 'currencies'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error fetching credit note for edit: ' . $e->getMessage(), ['credit_note_id' => $id]);

            return back()->with('error', 'Failed to load credit note for editing: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified credit note in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $existingCreditNote = CreditNote::findOrFail($id);
            if ($this->hasLaterSettlements($existingCreditNote)) {
                return response()->json([
                    'success' => false,
                    'message' => ['A credit note with later allocations or customer refunds cannot be edited.'],
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'ref_no' => 'nullable|string|max:255',
                'invoice_no' => 'nullable|string|max:255',
                'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', loggedCompanyId()['company_id'])],
                'sale_id' => ['nullable', Rule::exists('sales', 'id')->where('company_id', loggedCompanyId()['company_id'])],
                'date' => 'required|date',
                'currency_id' => 'required|exists:currencies,id',
                'payment_type' => ['nullable', Rule::exists('chart_of_accounts', 'id')->where(fn($query) => $query
                    ->where('company_id', loggedCompanyId()['company_id'])->where('type', 'Account')->where('sub_type', 'BANK'))],
                'paid_amount' => 'nullable|numeric|min:0',
                'items' => 'required|array|min:1',
                'items.*.item' => 'required|string|max:255',
                'items.*.description' => 'nullable|string',
                'items.*.hs_code' => 'nullable|string|max:255',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.kg' => 'nullable|integer|min:0',
                'items.*.price' => 'required|numeric|min:0',
                'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
                'items.*.account_id' => ['required', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id'])],
                'items.*.tax_id' => ['nullable', Rule::exists('tax_rates', 'id')->where('company_id', loggedCompanyId()['company_id'])],
            ]);

            $validator->after(function ($validator) use ($request) {
                if ((float) $request->input('paid_amount', 0) > 0 && !$request->filled('payment_type')) {
                    $validator->errors()->add('payment_type', 'A bank/cash account is required for a customer refund.');
                }
                if ($request->filled('sale_id') && $request->filled('customer_id')) {
                    $matchesCustomer = Sale::whereKey($request->input('sale_id'))
                        ->where('company_id', loggedCompanyId()['company_id'])
                        ->where('customer_id', $request->input('customer_id'))
                        ->where('approval_status', 'approved')
                        ->exists();
                    if (!$matchesCustomer) {
                        $validator->errors()->add('sale_id', 'The selected invoice does not belong to this customer.');
                    }
                }
            });

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->all(),
                ], 422);
            }

            DB::beginTransaction();

            $creditNote = CreditNote::findOrFail($id);
            $oldData = $creditNote->toArray();

            $creditNote = $this->creditNoteService->updateCreditNote($creditNote, $request->all(), $request);

            $newData = $creditNote->fresh()->toArray();
            CreditNoteAuditService::logUpdate($creditNote, $oldData, $newData, $request);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Credit note updated successfully!',
                'credit_note_id' => $creditNote->id,
                'redirect_url' => route('account.sale.credit-notes.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating credit note: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * Remove the specified credit note from storage.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $creditNote = CreditNote::findOrFail($id);
            if ($this->hasLaterSettlements($creditNote)) {
                DB::rollBack();
                return back()->with('error', 'A credit note with later allocations or customer refunds cannot be deleted.');
            }
            CreditNoteAuditService::logDeletion($creditNote, request());

            $this->creditNoteService->deleteCreditNote($creditNote);

            DB::commit();

            return redirect()->route('account.sale.credit-notes.index')
                ->with('success', 'Credit note deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting credit note: ' . $e->getMessage());

            return redirect()->route('account.sale.credit-notes.index')
                ->with('error', 'Failed to delete credit note: ' . $e->getMessage());
        }
    }

    public function auditLogs($id)
    {
        $creditNote = CreditNote::with(['customer'])->findOrFail($id);
        $logs = $creditNote->auditLogs()->with('user')->orderBy('created_at', 'desc')->paginate(20);

        return view('pages.account.sale.credit-note.audit-logs', compact('creditNote', 'logs'));
    }

    public function getLogDetails($logId)
    {
        try {
            // Find the specific audit log by its ID
            $log = CreditNoteAuditLog::with(['user', 'creditNote'])->find($logId);
            Log::info($log);
            if (! $log) {
                return response()->json([
                    'html' => '
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Log Not Found</h3>
                        <p class="mt-1 text-sm text-gray-500">The requested audit log could not be found.</p>
                    </div>
                ',
                ], 404);
            }

            $html = view('pages.account.sale.credit-note.partials.log-details', compact('log'))->render();

            return response()->json(['html' => $html]);
        } catch (\Exception $e) {
            Log::error('Error fetching audit log details: ' . $e->getMessage());

            return response()->json([
                'html' => '
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Error Loading Details</h3>
                    <p class="mt-1 text-sm text-gray-500">An error occurred while loading the log details. Please try again.</p>
                    <button onclick="closeLogDetails()" class="mt-4 px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors text-sm">
                        Close
                    </button>
                </div>
            ',
            ]);
        }
    }

    public function recordPayment(CreditNoteRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $creditNote = CreditNote::findOrFail($validatedData['credit_note_id']);
            $this->processCustomerRefund($creditNote, [
                'refund_date' => $validatedData['payment_date'],
                'amount' => $validatedData['amount'],
                'account_id' => $validatedData['account_id'],
                'description' => $validatedData['description'] ?? null,
            ], $request);

            $creditNote->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Customer refund recorded successfully. Available credit: ' . number_format((float) $creditNote->remaining_amount, 2),
                'data' => [
                    'remaining_amount' => $creditNote->remaining_amount,
                    'payment_amount' => $validatedData['amount'],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Customer refund recording error: ' . $e->getMessage());

            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to record customer refund: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function allocate(Request $request, CreditNote $creditNote)
    {
        $validated = $request->validate([
            'sale_id' => [
                'required',
                Rule::exists('sales', 'id')->where(fn($query) => $query
                    ->where('company_id', $creditNote->company_id)
                    ->where('customer_id', $creditNote->customer_id)
                    ->where('approval_status', 'approved')),
            ],
            'allocation_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($validated, $creditNote) {
            $note = CreditNote::whereKey($creditNote->id)->lockForUpdate()->firstOrFail();
            $sale = Sale::whereKey($validated['sale_id'])
                ->where('company_id', $note->company_id)
                ->where('customer_id', $note->customer_id)
                ->where('approval_status', 'approved')
                ->lockForUpdate()
                ->firstOrFail();

            $amount = round((float) $validated['amount'], 2);
            $available = round((float) $note->remaining_amount, 2);
            $invoiceRemaining = round((float) $sale->remaining_amount, 2);

            if ($amount > $available) {
                throw ValidationException::withMessages(['amount' => 'The allocation cannot exceed available credit of ' . number_format($available, 2) . '.']);
            }
            if ($amount > $invoiceRemaining) {
                throw ValidationException::withMessages(['amount' => 'The allocation cannot exceed the invoice balance of ' . number_format($invoiceRemaining, 2) . '.']);
            }

            CreditNoteAllocation::create([
                'company_id' => $note->company_id,
                'credit_note_id' => $note->id,
                'sale_id' => $sale->id,
                'allocation_date' => $validated['allocation_date'],
                'amount' => $amount,
                'created_by' => Auth::id(),
            ]);

            $note->update([
                'allocated_amount' => round((float) $note->allocated_amount + $amount, 2),
                'remaining_amount' => max(0, round($available - $amount, 2)),
                'updated_by' => Auth::id(),
            ]);

            $sale->remaining_amount = max(0, round($invoiceRemaining - $amount, 2));
            $sale->credit_note_status = 1;
            $sale->save();

            CreditNoteAuditLog::create([
                'credit_note_id' => $note->id,
                'user_id' => Auth::id(),
                'action' => 'invoice_allocation_recorded',
                'old_data' => json_encode(['remaining_amount' => $available]),
                'new_data' => json_encode(['sale_id' => $sale->id, 'allocation_amount' => $amount, 'remaining_amount' => $note->remaining_amount]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        return back()->with('success', 'Customer credit applied to the invoice successfully.');
    }

    public function refund(Request $request, CreditNote $creditNote)
    {
        $validated = $request->validate([
            'refund_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->where(fn($query) => $query
                    ->where('company_id', $creditNote->company_id)
                    ->where('type', 'Account')
                    ->where('sub_type', 'BANK')),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $this->processCustomerRefund($creditNote, $validated, $request);

        return back()->with('success', 'Customer refund recorded successfully.');
    }

    private function processCustomerRefund(CreditNote $creditNote, array $data, Request $request): void
    {
        DB::transaction(function () use ($creditNote, $data, $request) {
            $note = CreditNote::whereKey($creditNote->id)->lockForUpdate()->firstOrFail();
            $amount = round((float) $data['amount'], 2);
            $available = round((float) $note->remaining_amount, 2);

            if ($amount > $available) {
                throw ValidationException::withMessages(['amount' => 'The refund cannot exceed available credit of ' . number_format($available, 2) . '.']);
            }

            $bankAccount = ChartOfAccount::forCompany($note->company_id)
                ->whereKey($data['account_id'])
                ->where('type', 'Account')
                ->where('sub_type', 'BANK')
                ->firstOrFail();
            $period = $this->creditNoteService->validateAccountingPeriod($data['refund_date'], $note->company_id);
            $accountsReceivableId = ChartOfAccount::getAccountsReceivableId($note->company_id);
            if (!$accountsReceivableId) {
                throw new \RuntimeException('Accounts Receivable account is not configured for this company.');
            }

            $refund = CreditNoteRefund::create([
                'company_id' => $note->company_id,
                'credit_note_id' => $note->id,
                'account_id' => $bankAccount->id,
                'accounting_period_id' => $period->id,
                'refund_no' => 'PENDING-' . bin2hex(random_bytes(8)),
                'refund_date' => $data['refund_date'],
                'amount' => $amount,
                'description' => $data['description'] ?? null,
                'created_by' => Auth::id(),
            ]);
            $refundNo = 'CRF' . now()->format('Ym') . str_pad((string) $refund->id, 6, '0', STR_PAD_LEFT);
            $refund->update(['refund_no' => $refundNo]);

            $base = [
                'company_id' => $note->company_id,
                'accounting_period_id' => $period->id,
                'customer_id' => $note->customer_id,
                'payable_type' => 'customer',
                'payable_id' => $note->customer_id,
                'reference_id' => $note->id,
                'unique_no' => $refundNo,
                'invoice_no' => $note->invoice_no,
                'date' => $data['refund_date'],
                'type' => 'CreditNoteRefund',
                'description' => $data['description'] ?? "Customer refund for credit note {$note->unique_no}",
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ];

            Transaction::create(array_merge($base, ['account_id' => $accountsReceivableId, 'debit' => $amount, 'credit' => 0]));
            Transaction::create(array_merge($base, ['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => $amount]));

            $note->update([
                'paid_amount' => round((float) $note->paid_amount + $amount, 2),
                'remaining_amount' => max(0, round($available - $amount, 2)),
                'updated_by' => Auth::id(),
            ]);

            CreditNoteAuditLog::create([
                'credit_note_id' => $note->id,
                'user_id' => Auth::id(),
                'action' => 'refund_recorded',
                'old_data' => json_encode(['remaining_amount' => $available]),
                'new_data' => json_encode(['refund_amount' => $amount, 'remaining_amount' => $note->remaining_amount, 'refund_date' => $data['refund_date'], 'refund_account' => $bankAccount->id]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });
    }

    private function hasLaterSettlements(CreditNote $creditNote): bool
    {
        if ((float) $creditNote->paid_amount > 0 || $creditNote->refunds()->exists()) {
            return true;
        }

        $allocations = $creditNote->allocations()->get(['sale_id']);
        return $allocations->count() > 1
            || ($allocations->count() === 1 && (int) $allocations->first()->sale_id !== (int) $creditNote->sale_id);
    }

    public function updateTransaction(Request $request, $transactionId)
    {
        DB::beginTransaction();

        try {
            // Validate request
            $companyId = loggedCompanyId()['company_id'];
            $validator = Validator::make($request->all(), [
                'account_id' => ['required', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId)],
                'date' => 'required',
                'description' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validatedData = $validator->validated();

            // Get the transaction
            $transaction = Transaction::where('company_id', $companyId)->findOrFail($transactionId);

            if ($transaction->type === 'CreditNoteRefund'
                || str_starts_with((string) $transaction->unique_no, 'REF-')
                || str_starts_with((string) $transaction->unique_no, 'CRF')) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Posted customer refund transactions are locked.',
                ], 422);
            }

            // Store old data for audit
            $oldAccountId = $transaction->account_id;
            $oldAccount = ChartOfAccount::forCompany($companyId)->find($oldAccountId);
            $newAccount = ChartOfAccount::forCompany($companyId)->find($validatedData['account_id']);

            if (! $newAccount) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid account selected',
                ], 422);
            }

            $userId = Auth::id();
            $transaction->account_id = $validatedData['account_id'];
            $transaction->date = $validatedData['date'];
            $transaction->description = $validatedData['description'] ?? $transaction->description;
            $transaction->updated_by = $userId;
            $transaction->save();

            // Log the update in sale audit if it's related to a sale
            if ($transaction->type === 'Credit Note') {
                $creditNote = CreditNote::find($transaction->reference_id);
                if ($creditNote) {
                        CreditNoteAuditLog::create([
                            'credit_note_id' => $creditNote->id,
                            'user_id' => $userId,
                            'action' => 'transaction_updated',
                            'old_data' => json_encode([
                                'transaction_id' => $transaction->id,
                                'old_account_id' => $oldAccountId,
                                'old_account_name' => $oldAccount->account_name ?? null,
                            ]),
                            'new_data' => json_encode([
                                'transaction_id' => $transaction->id,
                                'new_account_id' => $validatedData['account_id'],
                                'new_account_name' => $newAccount->account_name,
                                'description' => $validatedData['description'] ?? null,
                            ]),
                            'ip_address' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                        ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction updated successfully!',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'account_name' => $newAccount->account_name,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Transaction update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export credit note PDF
     */
    public function exportPdf($id, Request $request)
    {
        try {
            $columns = $request->query('columns') ? explode(',', $request->query('columns')) : [];

            $creditNote = CreditNote::with([
                'customer',
                'currency',
                'creditNoteItems.account',
                'creditNoteItems.tax',
                'company',
            ])->findOrFail($id);

            $company = $creditNote->company;
            $companyName = $company ? $company->name : 'Company Name';
            $companyAddress = $company ? ($company->address ?? '') : '';
            $companyPhone = $company ? ($company->phone ?? '') : '';
            $companyEmail = $company ? ($company->email ?? '') : '';

            $companyId = loggedCompanyId()['company_id'];
            $invoiceTemplate = \App\Models\InvoiceTemplate::where('company_id', $companyId)
                ->where('is_default', true)
                ->first() ?? new \App\Models\InvoiceTemplate();
            $paperSize = PdfPaperSize::normalize($request->query('paper_size'));
            $paperCssSize = PdfPaperSize::cssSize($paperSize);

            // If columns are selected, use column-based PDF view
            if (!empty($columns)) {
                $pdfData = [
                    'creditNote' => $creditNote,
                    'companyName' => $companyName,
                    'companyAddress' => $companyAddress,
                    'companyPhone' => $companyPhone,
                    'companyEmail' => $companyEmail,
                    'invoiceTemplate' => $invoiceTemplate,
                    'selectedColumns' => $columns,
                    'paperCssSize' => $paperCssSize,
                ];

                $pdf = Pdf::loadView('pdf.export-columns-credit-note', $pdfData);
            } else {
                $pdf = Pdf::loadView('pdf.credit-note-single-pdf', compact(
                    'creditNote',
                    'companyName',
                    'companyAddress',
                    'companyPhone',
                    'companyEmail',
                    'invoiceTemplate',
                    'paperCssSize'
                ));
            }

            PdfPaperSize::apply($pdf, $paperSize);

            return $pdf->download('credit_note_' . $creditNote->invoice_no . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error exporting credit note PDF: ' . $e->getMessage());

            return back()->with('error', 'Failed to export credit note: ' . $e->getMessage());
        }
    }

    /**
     * Export credit note to Excel
     */
    public function exportExcel($id, Request $request)
    {
        try {
            $columns = $request->query('columns') ? explode(',', $request->query('columns')) : [];

            $creditNote = CreditNote::with([
                'customer',
                'currency',
                'creditNoteItems.account',
                'creditNoteItems.tax',
                'company',
            ])->findOrFail($id);

            return Excel::download(new \App\Exports\CreditNoteSingleExport($creditNote, $columns), 'credit_note_' . $creditNote->invoice_no . '.xlsx');
        } catch (\Exception $e) {
            Log::error('Error exporting credit note Excel: ' . $e->getMessage());
            return back()->with('error', 'Failed to export credit note: ' . $e->getMessage());
        }
    }

    /**
     * Get available columns for export
     */
    public function getExportColumns(Request $request)
    {
        // Get actual columns from database
        $dbColumns = Schema::getColumnListing('credit_notes');
        $formattedColumns = [];

        foreach ($dbColumns as $col) {
            if (!in_array($col, ['id', 'company_id', 'customer_id', 'currency_id', 'deleted_at', 'updated_at'])) {
                $label = ucwords(str_replace(['_', 'id'], [' ', ' ID'], $col));
                $formattedColumns[] = [
                    'key' => $col,
                    'label' => $label,
                    'checked' => in_array($col, ['invoice_no', 'date', 'ref_no', 'sub_total', 'tax', 'total', 'total_after_discount', 'status'])
                ];
            }
        }

        // Add related columns
        $formattedColumns[] = ['key' => 'customer', 'label' => 'Customer', 'checked' => true];
        $formattedColumns[] = ['key' => 'account', 'label' => 'Account', 'checked' => false];

        return response()->json($formattedColumns);
    }
}
