<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Exports\Logistics\LcPaymentsTemplateExport;
use App\Imports\Logistics\LcPaymentsImport;
use App\Models\LogisticsBookingV2;
use App\Models\LogisticsLcPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class LcPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = LogisticsLcPayment::with('booking')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }
        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('receipt_no', 'ilike', "%{$search}%")
                  ->orWhere('details', 'ilike', "%{$search}%")
                  ->orWhere('remarks', 'ilike', "%{$search}%")
                  ->orWhereHas('booking', function ($bookingQuery) use ($search) {
                      $bookingQuery->where('booking_number', 'ilike', "%{$search}%");
                  });
            });
        }

        $payments = $query->paginate(20)->withQueryString();
        $summary = LogisticsLcPayment::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN LOWER(status) = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN LOWER(status) = 'completed' THEN 1 ELSE 0 END) as completed")
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
            'total' => (int) ($summary->total ?? 0),
            'pending' => (int) ($summary->pending ?? 0),
            'completed' => (int) ($summary->completed ?? 0),
            'total_usd' => (float) ($summary->total_usd ?? 0),
            'total_pkr' => (float) ($summary->total_pkr ?? 0),
        ];
        $bookings = $this->bookingOptions();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.products.logistics.lc.payments.partials.table', compact('payments'))->render(),
                'summary' => $stats,
            ]);
        }

        return view('pages.products.logistics.lc.payments.index', compact('payments', 'stats', 'bookings'));
    }

    public function create()
    {
        $bookings = $this->bookingOptions();

        return view('pages.products.logistics.lc.payments.create', compact('bookings'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('upload_doc')) {
            $data['upload_doc'] = $request->file('upload_doc')->store('logistics/lc-payments', 'public');
            $data['original_filename'] = $request->file('upload_doc')->getClientOriginalName();
        }

        $data['company_id'] = auth()->check() ? (int) auth()->user()->company_id ?: null : null;
        $data['entry_source'] = 'lc_payment';

        LogisticsLcPayment::create($data);

        return response()->json(['success' => true, 'message' => 'Payment created successfully.']);
    }

    public function downloadTemplate()
    {
        return Excel::download(new LcPaymentsTemplateExport, 'lc_payment_import_template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $companyId = auth()->check() ? ((int) auth()->user()->company_id ?: null) : null;
        $import = new LcPaymentsImport($companyId);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return $this->importResponse(
                $request,
                false,
                'Import failed. Please check the file and try again.',
                [$e->getMessage()],
                500
            );
        }

        if ($import->errors()) {
            return $this->importResponse(
                $request,
                false,
                'Import failed. Please fix the highlighted rows and upload again.',
                $import->errors(),
                422
            );
        }

        $count = $import->importedCount();
        $message = "{$count} LC payment " . ($count === 1 ? 'record was' : 'records were') . ' imported successfully.';

        return $this->importResponse($request, true, $message);
    }

    public function edit($id)
    {
        $payment = LogisticsLcPayment::findOrFail($id);
        $bookings = $this->bookingOptions();

        return view('pages.products.logistics.lc.payments.edit', compact('payment', 'bookings'));
    }

    public function update(Request $request, $id)
    {
        $payment = LogisticsLcPayment::findOrFail($id);
        $data = $this->validatedData($request);

        if ($request->hasFile('upload_doc')) {
            if ($payment->upload_doc) {
                Storage::disk('public')->delete($payment->upload_doc);
            }

            $data['upload_doc'] = $request->file('upload_doc')->store('logistics/lc-payments', 'public');
            $data['original_filename'] = $request->file('upload_doc')->getClientOriginalName();
        }

        $payment->update($data);

        return response()->json(['success' => true, 'message' => 'Payment updated successfully.']);
    }

    public function destroy($id)
    {
        $payment = LogisticsLcPayment::findOrFail($id);

        if ($payment->upload_doc) {
            Storage::disk('public')->delete($payment->upload_doc);
        }

        $payment->delete();

        return response()->json(['success' => true]);
    }

    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'details' => 'nullable|string',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|in:PKR,USD',
            'currency_value' => 'required|numeric|gt:0',
            'payment_type' => 'required|in:freight_payment,product_payment',
            'remarks' => 'nullable|string',
            'status' => 'nullable|in:pending,completed',
            'receipt_no' => 'nullable|string|max:255',
            'upload_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,csv|max:10240',
            'booking_id' => 'nullable|exists:logistics_bookings,id',
        ]);

        $validated['booking_id'] = $request->filled('booking_id') ? $request->booking_id : null;
        $validated['amount'] = is_numeric($request->amount) ? $request->amount : null;
        $validated['currency'] = $request->input('currency', 'PKR');
        $validated['currency_value'] = $request->currency_value;
        $validated['payment_type'] = $request->payment_type;
        $validated['status'] = $request->filled('status') ? $request->status : null;

        unset($validated['upload_doc']);

        return $validated;
    }

    private function bookingOptions()
    {
        return LogisticsBookingV2::orderBy('booking_number')
            ->get(['id', 'booking_number', 'origin_port', 'destination_port']);
    }

    private function importResponse(Request $request, bool $success, string $message, array $errors = [], int $status = 200)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $message,
                'errors' => $errors,
            ], $status);
        }

        $flashKey = $success ? 'success' : 'error';
        $redirect = redirect()->back()->with($flashKey, $message);

        return $errors ? $redirect->with('import_errors', $errors) : $redirect;
    }
}
