<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Account\DuplicateDetectionService;
use Illuminate\Http\Request;

class DuplicateDetectionController extends Controller
{
    /**
     * Check for near-duplicate records before creation.
     *
     * GET /account/check-duplicates?type=bill&vendor_id=1&amount=5000&date=2026-04-01&invoice_no=INV-001
     * GET /account/check-duplicates?type=sale&customer_id=1&amount=5000&date=2026-04-01
     * GET /account/check-duplicates?type=journal_entry&total_debit=5000&date=2026-04-01&narration=...
     */
    public function check(Request $request, DuplicateDetectionService $service)
    {
        $type = $request->input('type', 'bill');

        $result = match ($type) {
            'bill' => $service->checkBill(
                vendorId: $request->integer('vendor_id') ?: null,
                amount: (float) $request->input('amount', 0),
                date: $request->input('date', now()->toDateString()),
                invoiceNo: $request->input('invoice_no'),
                excludeId: $request->integer('exclude_id') ?: null,
            ),
            'sale' => $service->checkSale(
                customerId: $request->integer('customer_id') ?: null,
                amount: (float) $request->input('amount', 0),
                date: $request->input('date', now()->toDateString()),
                invoiceNo: $request->input('invoice_no'),
                excludeId: $request->integer('exclude_id') ?: null,
            ),
            'journal_entry' => $service->checkJournalEntry(
                totalDebit: (float) $request->input('total_debit', 0),
                date: $request->input('date', now()->toDateString()),
                narration: $request->input('narration'),
                excludeUniqueNo: $request->input('exclude_unique_no'),
            ),
            default => ['duplicates' => [], 'count' => 0],
        };

        return response()->json($result);
    }
}
