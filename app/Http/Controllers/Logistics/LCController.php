<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsBookingV2;
use App\Models\LogisticsLcPayment;
use App\Models\LogisticsLetterOfCredit;
use Illuminate\Http\Request;
use App\Exports\Logistics\LCTemplateExport;
use App\Imports\Logistics\LCImport;
use Maatwebsite\Excel\Facades\Excel;

class LCController extends Controller
{
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('lc_type')) {
            $query->where('lc_type', $request->lc_type);
        }
        if ($request->filled('start_date')) {
            $query->where('issue_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('expiry_date', '<=', $request->end_date);
        }
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('lc_number', 'ilike', "%{$search}%")
                  ->orWhere('applicant', 'ilike', "%{$search}%")
                  ->orWhere('beneficiary', 'ilike', "%{$search}%")
                  ->orWhere('issuing_bank', 'ilike', "%{$search}%");
            });
        }
    }

    public function index(Request $request)
    {
        $query = LogisticsLetterOfCredit::where('record_type', 'lc')->latest();
        $this->applyFilters($query, $request);
        $lcs = $query->paginate(20)->withQueryString();

        $stats = [
            'total'         => LogisticsLetterOfCredit::where('record_type', 'lc')->count(),
            'active'        => LogisticsLetterOfCredit::where('record_type', 'lc')->where('status', 'Active')->count(),
            'expiring_soon' => LogisticsLetterOfCredit::where('record_type', 'lc')->where('status', 'Expiring Soon')->count(),
            'settled'       => LogisticsLetterOfCredit::where('record_type', 'lc')->where('status', 'Settled')->count(),
            'total_amount'  => LogisticsLetterOfCredit::where('record_type', 'lc')->whereIn('status', ['Active', 'Expiring Soon'])->sum('amount'),
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.products.logistics.lc.partials.table', ['records' => $lcs, 'type' => 'lc'])->render(),
                'summary' => $stats
            ]);
        }

        return view('pages.products.logistics.lc.index', compact('lcs', 'stats'));
    }

    public function create()
    {
        return view('pages.products.logistics.lc.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'lc_type'      => 'required|string',
            'issuing_bank' => 'required|string',
            'applicant'    => 'required|string',
            'beneficiary'  => 'required|string',
            'currency'     => 'required|string',
            'amount'       => 'required|numeric|min:0',
            'issue_date'   => 'required|date',
            'expiry_date'  => 'required|date|after:issue_date',
        ]);

        LogisticsLetterOfCredit::create([
            'lc_number'            => LogisticsLetterOfCredit::generateLcNumber(),
            'record_type'          => 'lc',
            'lc_type'              => $request->lc_type,
            'issuing_bank'         => $request->issuing_bank,
            'advising_bank'        => $request->advising_bank,
            'reimbursing_bank'     => $request->reimbursing_bank,
            'applicant'            => $request->applicant,
            'beneficiary'          => $request->beneficiary,
            'currency'             => $request->currency,
            'amount'               => $request->amount,
            'tolerance_pct'        => $request->tolerance_pct ?: null,
            'payment_terms'        => $request->payment_terms,
            'issue_date'           => $request->issue_date,
            'expiry_date'          => $request->expiry_date,
            'place_of_expiry'      => $request->place_of_expiry,
            'latest_shipment_date' => $request->latest_shipment_date ?: null,
            'incoterms'            => $request->incoterms,
            'port_of_loading'      => $request->port_of_loading,
            'port_of_discharge'    => $request->port_of_discharge,
            'goods_description'    => $request->goods_description,
            'documents_required'   => $request->documents_required ? implode(',', (array) $request->documents_required) : null,
            'special_conditions'   => $request->special_conditions,
            'status'               => 'Draft',
            'company_id'           => auth()->check() ? (int) auth()->user()->company_id ?: null : null,
        ]);

        return response()->json(['success' => true, 'message' => 'Letter of Credit opened successfully.']);
    }

    public function show($id)
    {
        $lc = LogisticsLetterOfCredit::with('bankCharges')->findOrFail($id);
        return view('pages.products.logistics.lc.show', compact('lc'));
    }

    public function edit($id)
    {
        $lc = LogisticsLetterOfCredit::findOrFail($id);
        return view('pages.products.logistics.lc.edit', compact('lc'));
    }

    public function update(Request $request, $id)
    {
        $lc = LogisticsLetterOfCredit::findOrFail($id);

        $request->validate([
            'lc_type'      => 'required|string',
            'issuing_bank' => 'required|string',
            'applicant'    => 'required|string',
            'beneficiary'  => 'required|string',
            'currency'     => 'required|string',
            'amount'       => 'required|numeric|min:0',
            'issue_date'   => 'required|date',
            'expiry_date'  => 'required|date|after:issue_date',
        ]);

        $lc->update([
            'lc_type'              => $request->lc_type,
            'issuing_bank'         => $request->issuing_bank,
            'advising_bank'        => $request->advising_bank,
            'reimbursing_bank'     => $request->reimbursing_bank,
            'applicant'            => $request->applicant,
            'beneficiary'          => $request->beneficiary,
            'currency'             => $request->currency,
            'amount'               => $request->amount,
            'tolerance_pct'        => $request->tolerance_pct ?: null,
            'payment_terms'        => $request->payment_terms,
            'issue_date'           => $request->issue_date,
            'expiry_date'          => $request->expiry_date,
            'place_of_expiry'      => $request->place_of_expiry,
            'latest_shipment_date' => $request->latest_shipment_date ?: null,
            'incoterms'            => $request->incoterms,
            'port_of_loading'      => $request->port_of_loading,
            'port_of_discharge'    => $request->port_of_discharge,
            'goods_description'    => $request->goods_description,
            'documents_required'   => $request->documents_required ? implode(',', (array) $request->documents_required) : null,
            'special_conditions'   => $request->special_conditions,
            'status'               => $request->status ?? $lc->status,
        ]);

        return response()->json(['success' => true, 'message' => 'LC updated successfully.']);
    }

    public function destroy($id)
    {
        LogisticsLetterOfCredit::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function landedCost(Request $request)
    {
        $bookingsQuery = LogisticsBookingV2::query();

        if ($request->filled('q')) {
            $search = trim($request->q);

            $bookingsQuery->where(function ($query) use ($search) {
                $query->where('booking_number', 'ilike', "%{$search}%")
                    ->orWhere('origin_port', 'ilike', "%{$search}%")
                    ->orWhere('destination_port', 'ilike', "%{$search}%")
                    ->orWhereHas('lcPayments', function ($paymentQuery) use ($search) {
                        $paymentQuery->where('receipt_no', 'ilike', "%{$search}%")
                            ->orWhere('details', 'ilike', "%{$search}%")
                            ->orWhere('remarks', 'ilike', "%{$search}%");
                    });
            });
        }

        $filteredBookingIds = (clone $bookingsQuery)
            ->select('logistics_bookings.id');

        $paymentSummary = LogisticsLcPayment::query()
            ->whereIn('booking_id', $filteredBookingIds)
            ->selectRaw('COUNT(*) as total_payments')
            ->selectRaw("
                COALESCE(SUM(
                    CASE
                        WHEN UPPER(COALESCE(currency, 'PKR')) = 'PKR' THEN amount
                        WHEN currency_value > 0 THEN amount * currency_value
                        ELSE 0
                    END
                ), 0) as total_pkr
            ")
            ->selectRaw("
                COALESCE(SUM(
                    CASE
                        WHEN UPPER(currency) = 'USD' THEN amount
                        WHEN currency_value > 0 THEN amount / NULLIF(currency_value, 0)
                        ELSE 0
                    END
                ), 0) as total_usd
            ")
            ->first();

        $stats = [
            'bookings' => (clone $bookingsQuery)->count(),
            'payments' => (int) ($paymentSummary->total_payments ?? 0),
            'total_pkr' => (float) ($paymentSummary->total_pkr ?? 0),
            'total_usd' => (float) ($paymentSummary->total_usd ?? 0),
        ];

        $bookings = $bookingsQuery
            ->with(['lcPayments' => fn ($query) => $query->latest('date')->latest('id')])
            ->withCount('lcPayments')
            ->orderBy('booking_number')
            ->paginate(20)
            ->withQueryString();

        $bookings->getCollection()->each(function ($booking) {
            $booking->setAttribute(
                'total_pkr',
                $booking->lcPayments->sum(fn ($payment) => $payment->amountInPkr() ?? 0)
            );
            $booking->setAttribute(
                'total_usd',
                $booking->lcPayments->sum(fn ($payment) => $payment->amountInUsd() ?? 0)
            );
        });

        return view('pages.products.logistics.lc.landed-cost', compact('bookings', 'stats'));
    }

    public function paymentTracker(Request $request)
    {
        $query = LogisticsLetterOfCredit::where('record_type', 'lc')->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('lc_number', 'ilike', "%{$search}%")
                  ->orWhere('beneficiary', 'ilike', "%{$search}%");
            });
        }
        $lcs = $query->paginate(20)->withQueryString();
        return view('pages.products.logistics.lc.payment-tracker', compact('lcs'));
    }

    public function downloadTemplate()
    {
        return Excel::download(new LCTemplateExport, 'lc_import_template.xlsx');
    }

    public function importLC(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
        ]);

        try {
            Excel::import(new LCImport, $request->file('file'));
            return redirect()->back()->with('success', 'Letter of Credit records imported successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing LC: ' . $e->getMessage());
        }
    }
}
