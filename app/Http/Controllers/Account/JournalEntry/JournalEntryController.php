<?php

namespace App\Http\Controllers\Account\JournalEntry;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLog;
use App\Models\OpeningBalanceBatch;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\JournelEntriesExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Services\Account\JournalCorrectionService;
use App\Support\DecimalAmount;

class JournalEntryController extends Controller
{
    /**
     * Base query for journal entries
     */
    private function baseJournalEntryQuery()
    {
        return JournalEntry::with('account')->where('unique_no', 'LIKE', '%JV-%');
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, Request $request)
    {
        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('unique_no', 'like', "%{$search}%")
                    ->orWhere('narration', 'like', "%{$search}%")
                    ->orWhere('item', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('account', function ($q) use ($search) {
                        $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
                            ->orWhere('account_number', 'like', "%{$search}%");
                    });
            });
        }

        // COA filter
        if ($request->filled('coa')) {
            $query->where('account_id', $request->coa);
        }

        // Date range filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $range = [$request->start_date, $request->end_date];
            sort($range);
            $query->whereBetween('date', $range);
        }

        return $query;
    }

    /**
     * Validate journal entry rows
     */
    private function validateJournalEntry(Request $request, $isUpdate = false)
    {
        $validator = Validator::make($request->all(), [
            'narration' => 'nullable|string|max:255',
            'date' => 'required|date',
            'rows' => 'required|array|min:1',
            'rows.*.item' => 'nullable|string|max:255',
            'rows.*.description' => 'nullable|string|max:255',
            'rows.*.account_id' => [
                'required',
                'integer',
                \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', loggedCompanyId()['company_id']),
            ],
            'rows.*.debit' => 'nullable|numeric|min:0',
            'rows.*.credit' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->all()
            ]);
        }

        // Validate Debit == Credit
        $totalDebit = DecimalAmount::sum(collect($request->rows)->pluck('debit'), 2);
        $totalCredit = DecimalAmount::sum(collect($request->rows)->pluck('credit'), 2);

        if (! DecimalAmount::equals($totalDebit, $totalCredit, 2)) {
            return response()->json([
                'success' => false,
                'message' => $isUpdate
                    ? 'Total Debit and Total Credit must be EQUAL!'
                    : ['Total Debit and Total Credit must be EQUAL before saving!']
            ]);
        }

        // Validate Accounting Period
        $period = AccountingPeriod::findPeriodForDate($request->date, loggedCompanyId()['company_id']);
        if (!$period) {
            return response()->json([
                'success' => false,
                'message' => $isUpdate
                    ? 'The selected date does not fall within any unlocked accounting period.'
                    : ['The Current Date Does not match with the current unlock accounting period']
            ]);
        }

        return $period;
    }

    /**
     * Create journal entry data array
     */
    private function prepareJournalEntryData($row, $commonData)
    {
        return [
            'company_id' => $commonData['company_id'],
            'accounting_period_id' => $commonData['period']->id,
            'account_id' => $row['account_id'],
            'unique_no' => $commonData['unique_no'],
            'narration' => $commonData['narration'],
            'date' => $commonData['date'],
            'item' => $row['item'] ?? null,
            'description' => $row['description'] ?? null,
            'debit' => DecimalAmount::normalize($row['debit'] ?? 0, 2),
            'credit' => DecimalAmount::normalize($row['credit'] ?? 0, 2),
            'posting_status' => 'posted',
            'correction_type' => $commonData['correction_type'] ?? null,
            'posted_by' => $commonData['user_id'],
            'posted_at' => $commonData['now'],
            'created_by' => $commonData['user_id'],
            'updated_by' => $commonData['user_id'] ?? null,
            'created_at' => $commonData['now'],
            'updated_at' => $commonData['now'],
        ];
    }

    /**
     * Create transaction data array
     */
    private function prepareTransactionData($row, $commonData)
    {
        return [
            'company_id' => $commonData['company_id'],
            'accounting_period_id' => $commonData['period']->id,
            'account_id' => $row['account_id'],
            'unique_no' => $commonData['unique_no'],
            'description' => $commonData['narration'],
            'date' => $commonData['date'],
            'type' => 'Journal_Entry',
            'debit' => DecimalAmount::normalize($row['debit'] ?? 0, 2),
            'credit' => DecimalAmount::normalize($row['credit'] ?? 0, 2),
            'created_by' => $commonData['user_id'],
            'updated_by' => $commonData['user_id'] ?? null,
            'posted_by' => $commonData['user_id'],
            'posted_at' => $commonData['now'],
            'source_system' => $commonData['source_system'] ?? null,
            'source_reference' => $commonData['source_reference'] ?? null,
            'correction_type' => $commonData['correction_type'] ?? null,
            'created_at' => $commonData['now'],
            'updated_at' => $commonData['now'],
        ];
    }

    /**
     * Log journal entry changes
     */
    private function logJournalEntryChanges($oldEntries, $newEntries, $userId, $event)
    {
        foreach ($oldEntries as $oldEntry) {
            JournalEntryLog::create([
                'journal_entry_id' => $oldEntry->unique_no,
                'company_id' => $oldEntry->company_id,
                'user_id' => $userId,
                'event' => $event,
                'old_values' => $oldEntry->toArray(),
                'new_values' => null,
            ]);
        }

        foreach ($newEntries as $newEntry) {
            JournalEntryLog::create([
                'journal_entry_id' => $newEntry->unique_no,
                'company_id' => $newEntry->company_id,
                'user_id' => $userId,
                'event' => $event,
                'old_values' => null,
                'new_values' => $newEntry->toArray(),
            ]);
        }
    }

    /**
     * Generate unique number
     */
    private function generateUniqueNo()
    {
        $nextID = (JournalEntry::max('id') ?? 0) + 1;
        return 'JV-' . str_pad($nextID, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Handle export common logic
     */
    private function getExportData(Request $request, $withRelations = [])
    {
        $query = $this->baseJournalEntryQuery();

        if (!empty($withRelations)) {
            $query->with($withRelations);
        }

        $query = $this->applyFilters($query, $request);

        return $query->orderBy('date', 'desc')->get();
    }

    // ==================== PUBLIC METHODS ====================

    public function index(Request $request)
    {
        $sortBy = match ($request->input('sort_by')) {
            'j_unique_no', 'unique_no' => 'unique_no',
            default => 'journal_date',
        };
        $sortDirection = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        $journalQuery = $this->applyFilters($this->baseJournalEntryQuery(), $request)
            ->select('unique_no')
            ->selectRaw('MAX(date) AS journal_date')
            ->selectRaw('MAX(created_at) AS journal_created_at')
            ->groupBy('unique_no');

        if ($sortBy === 'unique_no') {
            $journalQuery->orderBy('unique_no', $sortDirection)
                ->orderByDesc('journal_date')
                ->orderByDesc('journal_created_at');
        } else {
            $journalQuery->orderBy('journal_date', $sortDirection)
                ->orderBy('journal_created_at', $sortDirection)
                ->orderBy('unique_no', $sortDirection);
        }

        $journalPaginator = $journalQuery->paginate($request->per_page ?? 20);
        $references = $journalPaginator->getCollection()->pluck('unique_no')->values();
        $referenceOrder = $references->flip();
        $lines = $references->isEmpty()
            ? collect()
            : $this->baseJournalEntryQuery()
                ->whereIn('unique_no', $references)
                ->get()
                ->sortBy(fn ($entry) => sprintf('%08d:%020d', $referenceOrder[$entry->unique_no], $entry->id))
                ->values();
        $entries = $lines;

        $data = compact('entries', 'journalPaginator');

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.account.journal-entries.table', $data)->render(),
                'pagination' => $journalPaginator->appends(request()->query())->links()->toHtml(),
            ]);
        }

        return view('pages.account.journal-entries.index', $data);
    }

    public function create()
    {
        return view('pages.account.journal-entries.create', [
            'accounts' => byOrderCoa(),
            'copiedEntries' => [],
            'replacesUniqueNo' => null,
            'reversalAlreadyPosted' => false,
            'copiedNarration' => null,
            'copiedDate' => null,
        ]);
    }

    public function correct(string $unique_no)
    {
        $entries = JournalEntry::where('unique_no', $unique_no)->with('account')->orderBy('id')->get();
        abort_if($entries->isEmpty(), 404);
        if (JournalEntry::where('reversal_of_unique_no', $unique_no)->where('posting_status', 'posted')->exists()) {
            return redirect()->route('account.journal-entries.show', $unique_no)
                ->with('error', 'This journal has already been reversed and cannot create another replacement.');
        }

        return view('pages.account.journal-entries.create', [
            'accounts' => byOrderCoa(),
            'copiedEntries' => $entries->map(fn ($entry) => [
                'item' => $entry->item,
                'description' => $entry->description,
                'account_id' => $entry->account_id,
                'debit' => $entry->debit,
                'credit' => $entry->credit,
            ])->values(),
            'replacesUniqueNo' => $unique_no,
            'reversalAlreadyPosted' => false,
            'copiedNarration' => 'Correction of ' . $unique_no . ' - ' . $entries->first()->narration,
            'copiedDate' => $entries->first()->date->format('Y-m-d'),
        ]);
    }

    public function copyReplacement(string $unique_no)
    {
        $entries = JournalEntry::where('unique_no', $unique_no)->with('account')->orderBy('id')->get();
        abort_if($entries->isEmpty(), 404);
        if (! JournalEntry::where('reversal_of_unique_no', $unique_no)->where('posting_status', 'posted')->exists()) {
            return redirect()->route('account.journal-entries.show', $unique_no)
                ->with('error', 'This journal has not been reversed. Use Reverse & Copy for Correction instead.');
        }
        if (Transaction::where('source_system', 'CONTROLLED_CORRECTION')
            ->where('source_reference', $unique_no)
            ->where('correction_type', 'replacement')
            ->where('status', 1)
            ->exists()) {
            return redirect()->route('account.journal-entries.show', $unique_no)
                ->with('error', 'A replacement journal has already been posted for this original.');
        }

        return view('pages.account.journal-entries.create', [
            'accounts' => byOrderCoa(),
            'copiedEntries' => $entries->map(fn ($entry) => [
                'item' => $entry->item,
                'description' => $entry->description,
                'account_id' => $entry->account_id,
                'debit' => $entry->debit,
                'credit' => $entry->credit,
            ])->values(),
            'replacesUniqueNo' => $unique_no,
            'reversalAlreadyPosted' => true,
            'copiedNarration' => 'Replacement for reversed journal ' . $unique_no . ' - ' . $entries->first()->narration,
            'copiedDate' => $entries->first()->date->format('Y-m-d'),
        ]);
    }

    public function store(Request $request, JournalCorrectionService $correctionService)
    {
        try {
            $period = $this->validateJournalEntry($request);
            if ($period instanceof \Illuminate\Http\JsonResponse) {
                return $period;
            }

            $replacesUniqueNo = trim((string) $request->input('replaces_unique_no'));
            $reversalAlreadyPosted = $request->boolean('reversal_already_posted');
            $correctionReason = trim((string) $request->input('correction_reason'));
            $originalDate = null;
            if ($replacesUniqueNo !== '') {
                abort_unless(Auth::user()?->hasPermission('account.journal-entries.reverse'), 403);
                $request->validate([
                    'replaces_unique_no' => ['required', 'string', 'max:255'],
                    'reversal_already_posted' => ['nullable', 'boolean'],
                    'correction_reason' => ['required', 'string', 'max:1000'],
                ]);
                $original = JournalEntry::where('unique_no', $replacesUniqueNo)->first();
                if (! $original) {
                    throw ValidationException::withMessages(['journal' => 'The original journal was not found for this company.']);
                }
                $hasReversal = JournalEntry::where('reversal_of_unique_no', $replacesUniqueNo)->where('posting_status', 'posted')->exists();
                if ($reversalAlreadyPosted && ! $hasReversal) {
                    throw ValidationException::withMessages(['journal' => 'The original journal has not been reversed.']);
                }
                if (! $reversalAlreadyPosted && $hasReversal) {
                    throw ValidationException::withMessages(['journal' => 'The original journal has already been reversed. Use Copy to Replacement instead.']);
                }
                if (Transaction::where('source_system', 'CONTROLLED_CORRECTION')
                    ->where('source_reference', $replacesUniqueNo)
                    ->where('correction_type', 'replacement')
                    ->where('status', 1)
                    ->exists()) {
                    throw ValidationException::withMessages(['journal' => 'A replacement journal has already been posted for this original.']);
                }
                $originalDate = $original->date->format('Y-m-d');
            }

            DB::beginTransaction();

            if ($replacesUniqueNo !== '' && ! $reversalAlreadyPosted) {
                $correctionService->reverse(
                    (int) loggedCompanyId()['company_id'],
                    $replacesUniqueNo,
                    $originalDate,
                    $correctionReason,
                    (int) Auth::id()
                );
            }

            $commonData = [
                'company_id' => loggedCompanyId()['company_id'],
                'user_id' => Auth::id(),
                'period' => $period,
                'date' => $request->date,
                'narration' => $request->narration,
                'unique_no' => $this->generateUniqueNo(),
                'now' => now(),
                'source_system' => $replacesUniqueNo !== '' ? 'CONTROLLED_CORRECTION' : null,
                'source_reference' => $replacesUniqueNo !== '' ? $replacesUniqueNo : null,
                'correction_type' => $replacesUniqueNo !== '' ? 'replacement' : null,
            ];

            $journalEntries = [];
            $transactions = [];

            foreach ($request->rows as $row) {
                $journalEntries[] = $this->prepareJournalEntryData($row, $commonData);
                $transactions[] = $this->prepareTransactionData($row, $commonData);
            }

            JournalEntry::insert($journalEntries);
            Transaction::insert($transactions);

            $insertedEntries = JournalEntry::where('unique_no', $commonData['unique_no'])->get();

            foreach ($insertedEntries as $entry) {
                $entry->logChange(event: 'created', old: null, new: $entry->toArray());
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => [$replacesUniqueNo !== '' ? 'Original journal reversed and corrected replacement posted successfully!' : 'Journal Entry saved successfully!'],
                'unique_no' => $commonData['unique_no'],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->handleError($e);
        }
    }

    public function show($unique_no)
    {
        $entries = JournalEntry::where('unique_no', $unique_no)
            ->with(['account', 'accountingPeriod', 'createdBy'])
            ->get();

        if ($entries->isEmpty()) {
            abort(404);
        }

        $reversal = JournalEntry::where('reversal_of_unique_no', $unique_no)
            ->where('posting_status', 'posted')
            ->orderByDesc('id')
            ->first();

        $openingBalanceBatch = OpeningBalanceBatch::where('journal_unique_no', $unique_no)->first();

        return view('pages.account.journal-entries.show', [
            'entries' => $entries,
            'journal_entry' => $entries->first(),
            'reversal' => $reversal,
            'opening_balance_batch' => $openingBalanceBatch,
        ]);
    }

    public function edit($unique_no)
    {
        $entries = JournalEntry::where('unique_no', $unique_no)
            ->with('account')
            ->get();

        if ($entries->isEmpty()) {
            abort(404);
        }

        return view('pages.account.journal-entries.edit', [
            'entries' => $entries,
            'journal_entry' => $entries->first(),
            'accounts' => byOrderCoa()
        ]);
    }

    public function update(Request $request, $unique_no)
    {
        return response()->json([
            'success' => false,
            'message' => 'Posted journal entries are immutable. Create a controlled reversal and replacement instead.',
        ], 422);
    }

    public function destroy($unique_no)
    {
        return redirect()->route('account.journal-entries.index')
            ->with('error', 'Posted journal entries cannot be deleted. Use a controlled reversal.');
    }

    public function reverse(Request $request, string $unique_no, JournalCorrectionService $service)
    {
        $data = $request->validate([
            'reversal_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $reference = $service->reverse((int) loggedCompanyId()['company_id'], $unique_no, $data['reversal_date'], $data['reason'], (int) Auth::id());

        return redirect()->route('account.journal-entries.show', $reference)->with('success', 'Controlled reversal posted.');
    }

    public function logs($unique_no)
    {
        try {
            $logs = JournalEntryLog::where('journal_entry_id', $unique_no)
                ->with('user')
                ->orderBy('id', 'DESC')
                ->paginate(20);

            return view('pages.account.journal-entries.logs', compact('logs'));
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function getExportColumns(Request $request)
    {
        try {
            $dbColumns = DB::getSchemaBuilder()->getColumnListing('journal_entries');
            $formattedColumns = [];

            foreach ($dbColumns as $col) {
                if (!in_array($col, ['id', 'company_id', 'accounting_period_id', 'currency_id', 'deleted_at', 'updated_at'])) {
                    $label = ucwords(str_replace(['_', 'id'], [' ', ' ID'], $col));
                    $formattedColumns[] = [
                        'key' => $col,
                        'label' => $label,
                        'checked' => in_array($col, ['date', 'unique_no', 'narration', 'item', 'description', 'debit', 'credit', 'status'])
                    ];
                }
            }

            $formattedColumns[] = ['key' => 'account', 'label' => 'Account', 'checked' => true];
            $formattedColumns[] = ['key' => 'created_by_user', 'label' => 'Created By', 'checked' => false];

            return response()->json($formattedColumns);
        } catch (\Throwable $e) {
            Log::error('Error in getExportColumns: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $selectedColumns = explode(',', $request->query('columns', ''));
            $entries = $this->getExportData($request, ['account', 'createdBy']);

            $pdf = Pdf::loadView('pdf.journal-entries-pdf', [
                'journalEntries' => $entries,
                'columns' => $selectedColumns
            ]);

            return $pdf->download('journal-entries.pdf');
        } catch (\Throwable $e) {
            Log::error('Error in exportPdf: ' . $e->getMessage());
            return back()->with('error', 'Failed to export PDF: ' . $e->getMessage());
        }
    }

    public function exportExcel(Request $request)
    {
        try {
            $selectedColumns = explode(',', $request->query('columns', ''));
            $entries = $this->getExportData($request, ['account']);

            return Excel::download(new JournelEntriesExport($selectedColumns), 'journal-entries.xlsx');
        } catch (\Throwable $e) {
            Log::error('Error in exportExcel: ' . $e->getMessage());
            return back()->with('error', 'Failed to export Excel: ' . $e->getMessage());
        }
    }

    /**
     * Handle error responses
     */
    private function handleError(\Throwable $e)
    {
        return response()->json([
            'success' => false,
            'message' => [$e->getMessage()]
        ], 500);
    }
}
