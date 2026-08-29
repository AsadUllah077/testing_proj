<?php

namespace App\Http\Controllers\Logistics;

use App\Exports\Logistics\GDTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\Logistics\GDImport;
use App\Models\LogisticsBookingV2;
use App\Models\LogisticsGoodsDeclaration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class GDController extends Controller
{
    private const STATUSES = [
        'Draft',
        'Filed',
        'Approved',
        'Cleared',
        'On Hold',
        'Rejected',
    ];

    public function index(Request $request)
    {
        $query = LogisticsGoodsDeclaration::with('booking')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('exam_status')) {
            $query->where('exam_status', $request->exam_status);
        }
        if ($request->filled('declaration_type')) {
            $query->where('declaration_type', $request->declaration_type);
        }
        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('gd_number', 'ilike', "%{$search}%")
                    ->orWhere('importer', 'ilike', "%{$search}%")
                    ->orWhere('exporter', 'ilike', "%{$search}%")
                    ->orWhere('hs_code', 'ilike', "%{$search}%")
                    ->orWhere('bl_awb_number', 'ilike', "%{$search}%");
            });
        }

        $gds = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => LogisticsGoodsDeclaration::count(),
            'draft' => LogisticsGoodsDeclaration::where('status', 'Draft')->count(),
            'filed' => LogisticsGoodsDeclaration::where('status', 'Filed')->count(),
            'cleared' => LogisticsGoodsDeclaration::where('status', 'Cleared')->count(),
            'exam' => LogisticsGoodsDeclaration::whereIn('exam_status', ['doc_check', 'physical_exam'])->count(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('pages.products.logistics.gd.partials.table', compact('gds'))->render(),
                'summary' => $stats,
            ]);
        }

        return view('pages.products.logistics.gd.index', compact('gds', 'stats'));
    }

    public function downloadTemplate()
    {
        return Excel::download(new GDTemplateExport, 'gd_import_template.xlsx');
    }

    public function importGds(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
        ]);

        try {
            Excel::import(new GDImport, $request->file('file'));

            return redirect()->back()->with('success', 'GD records imported successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing GDs: '.$e->getMessage());
        }
    }

    public function create()
    {
        [$bookings, $bookingAutofill] = $this->gdBookingOptions();

        return view('pages.products.logistics.gd.create', compact('bookings', 'bookingAutofill'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'gd_number' => ['required', 'string', 'max:255', Rule::unique('logistics_goods_declarations', 'gd_number')],
            'declaration_type' => 'required|string',
            'importer' => 'required|string|max:255',
            'hs_code' => 'required|string',
            'description' => 'required|string',
            'customs_value' => 'nullable|numeric|min:0',
            'delivery_term' => 'nullable|in:cfr,fob',
            'delivery_term_value' => 'nullable|required_with:delivery_term|numeric|min:0',
            'exchange_rate' => 'required|numeric|min:0',
            'country_of_origin' => ['nullable', Rule::in(array_keys(config('countries', [])))],
            'quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|in:PCS,KG,MT,CBM,Ltr,Dozen',
            'net_weight' => 'nullable|numeric|min:0',
            'status' => ['required', Rule::in(self::STATUSES)],
            'customs_duty_rate' => 'nullable|numeric|min:0',
            'additional_duty_rate' => 'nullable|numeric|min:0',
            'sales_tax_rate' => 'nullable|numeric|min:0',
            'income_tax_rate' => 'nullable|numeric|min:0',
            'duty_payment_invoice_number' => 'nullable|string|max:255',
            'third_party_name' => 'nullable|string|max:255',
            'invoice_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
            'insurance_type' => 'nullable|in:value,percentage',
            'insurance_value' => 'nullable|numeric|min:0',
            'payment_documents' => 'nullable|array',
            'payment_documents.*.name' => 'nullable|string|max:255',
            'payment_documents.*.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);

        $n = fn ($v) => is_numeric($v) ? $v : null;
        $quantity = $n($request->quantity);
        $customsValue = $n($request->delivery_term_value) ?? $n($request->customs_value) ?? 0;
        $duties = $this->calculateDutyAmounts($request, (float) $customsValue);

        LogisticsGoodsDeclaration::create([
            'gd_number' => trim($request->gd_number),
            'booking_v2_id' => $request->booking_v2_id ?: null,
            'declaration_type' => $request->declaration_type,
            'collectorate' => $request->collectorate,
            'bl_awb_number' => $request->bl_awb_number,
            'importer' => $request->importer,
            'ntn_cnic' => $request->ntn_cnic,
            'exporter' => $request->exporter,
            'country_of_origin' => $request->country_of_origin,
            'hs_code' => $request->hs_code,
            'description' => $request->description,
            'quantity' => $quantity,
            'unit' => $request->unit,
            'gross_weight' => $request->unit === 'KG' ? $quantity : null,
            'net_weight' => $n($request->net_weight),
            'customs_value' => $customsValue,
            'delivery_term' => $request->delivery_term ?: null,
            'delivery_term_value' => $request->delivery_term ? $n($request->delivery_term_value) : null,
            'currency' => $request->currency ?? 'USD',
            'exchange_rate' => $request->exchange_rate,
            'customs_duty' => $duties['customs_duty'],
            'additional_duty' => $duties['additional_duty'],
            'sales_tax' => $duties['sales_tax'],
            'income_tax' => $duties['income_tax'],
            'total_duty' => $duties['total_duty'],
            'duty_payment_mode' => $request->duty_payment_mode,
            'duty_payment_details' => $this->buildDutyPaymentDetails($request),
            'landed_cost' => $duties['landed_cost'],
            'status' => $request->status,
            'exam_status' => 'no_exam',
            'filed_date' => $request->filed_date ?: null,
            'remarks' => $request->remarks,
            'company_id' => auth()->check() ? (int) auth()->user()->company_id ?: null : null,
        ]);

        return response()->json(['success' => true, 'message' => 'GD filed successfully.']);
    }

    public function show($id)
    {
        $gd = LogisticsGoodsDeclaration::with('booking')->findOrFail($id);

        return view('pages.products.logistics.gd.show', compact('gd'));
    }

    public function edit($id)
    {
        $gd = LogisticsGoodsDeclaration::findOrFail($id);
        [$bookings, $bookingAutofill] = $this->gdBookingOptions();

        return view('pages.products.logistics.gd.edit', compact('gd', 'bookings', 'bookingAutofill'));
    }

    public function update(Request $request, $id)
    {
        $gd = LogisticsGoodsDeclaration::findOrFail($id);

        $request->validate([
            'gd_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('logistics_goods_declarations', 'gd_number')->ignore($gd->id),
            ],
            'declaration_type' => 'required|string',
            'importer' => 'required|string|max:255',
            'hs_code' => 'required|string',
            'description' => 'required|string',
            'customs_value' => 'nullable|numeric|min:0',
            'delivery_term' => 'nullable|in:cfr,fob',
            'delivery_term_value' => 'nullable|required_with:delivery_term|numeric|min:0',
            'exchange_rate' => 'required|numeric|min:0',
            'country_of_origin' => ['nullable', Rule::in(array_keys(config('countries', [])))],
            'quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|in:PCS,KG,MT,CBM,Ltr,Dozen',
            'net_weight' => 'nullable|numeric|min:0',
            'status' => ['required', Rule::in(self::STATUSES)],
            'customs_duty_rate' => 'nullable|numeric|min:0',
            'additional_duty_rate' => 'nullable|numeric|min:0',
            'sales_tax_rate' => 'nullable|numeric|min:0',
            'income_tax_rate' => 'nullable|numeric|min:0',
        ]);

        $n = fn ($v) => is_numeric($v) ? $v : null;
        $quantity = $n($request->quantity);
        $customsValue = $n($request->delivery_term_value) ?? $n($request->customs_value) ?? 0;
        $duties = $this->calculateDutyAmounts($request, (float) $customsValue);

        $gd->update([
            'gd_number' => trim($request->gd_number),
            'booking_v2_id' => $request->booking_v2_id ?: null,
            'declaration_type' => $request->declaration_type,
            'collectorate' => $request->collectorate,
            'bl_awb_number' => $request->bl_awb_number,
            'importer' => $request->importer,
            'ntn_cnic' => $request->ntn_cnic,
            'exporter' => $request->exporter,
            'country_of_origin' => $request->country_of_origin,
            'hs_code' => $request->hs_code,
            'description' => $request->description,
            'quantity' => $quantity,
            'unit' => $request->unit,
            'gross_weight' => $request->unit === 'KG' ? $quantity : null,
            'net_weight' => $n($request->net_weight),
            'customs_value' => $customsValue,
            'delivery_term' => $request->delivery_term ?: null,
            'delivery_term_value' => $request->delivery_term ? $n($request->delivery_term_value) : null,
            'currency' => $request->currency ?? 'USD',
            'exchange_rate' => $request->exchange_rate,
            'customs_duty' => $duties['customs_duty'],
            'additional_duty' => $duties['additional_duty'],
            'sales_tax' => $duties['sales_tax'],
            'income_tax' => $duties['income_tax'],
            'total_duty' => $duties['total_duty'],
            'duty_payment_mode' => $request->duty_payment_mode,
            'landed_cost' => $duties['landed_cost'],
            'status' => $request->status,
            'exam_status' => $request->exam_status ?? $gd->exam_status,
            'filed_date' => $request->filed_date,
            'remarks' => $request->remarks,
        ]);

        return response()->json(['success' => true, 'message' => 'GD updated successfully.']);
    }

    private function calculateDutyAmounts(Request $request, float $customsValue): array
    {
        $number = static fn ($value): float => is_numeric($value) ? (float) $value : 0.0;
        $customsValuePkr = $customsValue * $number($request->exchange_rate);
        $customsDuty = $customsValuePkr * ($number($request->customs_duty_rate) / 100);
        $additionalDuty = $customsValuePkr * ($number($request->additional_duty_rate) / 100);
        $salesTaxBase = $customsValuePkr + $customsDuty + $additionalDuty;
        $salesTax = $salesTaxBase * ($number($request->sales_tax_rate) / 100);
        $incomeTax = $customsValuePkr * ($number($request->income_tax_rate) / 100);
        $totalDuty = $customsDuty + $additionalDuty + $salesTax + $incomeTax;

        return [
            'customs_duty' => round($customsDuty, 2),
            'additional_duty' => round($additionalDuty, 2),
            'sales_tax' => round($salesTax, 2),
            'income_tax' => round($incomeTax, 2),
            'total_duty' => round($totalDuty, 2),
            'landed_cost' => round($customsValuePkr + $totalDuty, 2),
        ];
    }

    private function buildDutyPaymentDetails(Request $request): ?array
    {
        $invoiceDocument = $request->file('invoice_document');
        $invoiceDocumentPath = $invoiceDocument
            ? $invoiceDocument->store('logistics/gd/payment-documents', 'public')
            : null;

        $documents = [];
        foreach ($request->input('payment_documents', []) as $index => $document) {
            $file = $request->file("payment_documents.{$index}.file");
            $name = $document['name'] ?? null;

            if (! filled($name) && ! $file) {
                continue;
            }

            $documents[] = [
                'name' => $name,
                'file_path' => $file ? $file->store('logistics/gd/payment-documents', 'public') : null,
                'original_name' => $file?->getClientOriginalName(),
            ];
        }

        $details = [
            'invoice_number' => $request->duty_payment_mode === 'third_party'
                ? null
                : $request->duty_payment_invoice_number,
            'third_party_name' => $request->duty_payment_mode === 'third_party'
                ? $request->third_party_name
                : null,
            'invoice_document_path' => $invoiceDocumentPath,
            'invoice_document_original_name' => $invoiceDocument?->getClientOriginalName(),
            'insurance_type' => $request->insurance_type ?: null,
            'insurance_value' => is_numeric($request->insurance_value) ? $request->insurance_value : null,
            'documents' => $documents,
        ];

        $hasDetails = collect($details)
            ->reject(fn ($value, $key) => $key === 'documents')
            ->contains(fn ($value) => filled($value)) || ! empty($documents);

        return $hasDetails ? $details : null;
    }

    private function gdBookingOptions(): array
    {
        $bookings = LogisticsBookingV2::with('hsCode:id,hs_code,description')
            ->orderBy('booking_number')
            ->get();

        $bookingAutofill = $bookings->mapWithKeys(function (LogisticsBookingV2 $booking) {
            return [
                $booking->id => [
                    'booking_number' => $booking->booking_number,
                    'importer' => $booking->importer,
                    'exporter' => $booking->exporter,
                    'country_of_origin' => $this->resolveGdCountryOfOrigin($booking),
                    'hs_code' => $booking->hsCode?->hs_code,
                    'hs_description' => $booking->hsCode?->description,
                    'description' => $booking->cargo_description ?: $booking->hsCode?->description,
                ],
            ];
        });

        return [$bookings, $bookingAutofill];
    }

    private function resolveGdCountryOfOrigin(LogisticsBookingV2 $booking): ?string
    {
        foreach ([$booking->country_of_origin, $booking->origin_country, $booking->origin_port, $booking->place_of_receipt] as $value) {
            $country = $this->normalizeGdCountry($value);

            if ($country) {
                return $country;
            }
        }

        return null;
    }

    private function normalizeGdCountry(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $normalized = strtolower(trim((string) $value));

        foreach (config('countries', []) as $code => $name) {
            if ($normalized === strtolower($code) || $normalized === strtolower($name)) {
                return $code;
            }
        }

        $countries = [
            'CN' => ['cn', 'china', 'shanghai', 'ningbo', 'shenzhen', 'qingdao', 'xiamen', 'tianjin', 'guangzhou', 'hong kong'],
            'AE' => ['ae', 'uae', 'united arab emirates', 'dubai', 'jebel ali', 'abu dhabi', 'sharjah'],
            'GB' => ['gb', 'uk', 'united kingdom', 'great britain', 'london', 'felixstowe', 'southampton'],
            'TR' => ['tr', 'turkey', 'turkiye', 'istanbul', 'izmir', 'mersin'],
            'DE' => ['de', 'germany', 'hamburg', 'bremerhaven'],
            'IN' => ['in', 'india', 'mumbai', 'nhava sheva', 'chennai', 'mundra'],
            'US' => ['us', 'usa', 'united states', 'new york', 'los angeles', 'savannah', 'houston'],
            'JP' => ['jp', 'japan', 'tokyo', 'yokohama', 'osaka', 'kobe'],
            'KR' => ['kr', 'korea', 'south korea', 'busan', 'incheon'],
            'IT' => ['it', 'italy', 'genoa', 'la spezia', 'trieste'],
        ];

        foreach ($countries as $code => $needles) {
            foreach ($needles as $needle) {
                if ($normalized === $needle || (strlen($needle) > 2 && str_contains($normalized, $needle))) {
                    return $code;
                }
            }
        }

        return null;
    }

    public function destroy($id)
    {
        LogisticsGoodsDeclaration::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    public function exam(Request $request)
    {
        $query = LogisticsGoodsDeclaration::with('booking')
            ->whereIn('exam_status', ['doc_check', 'physical_exam'])
            ->latest();

        if ($request->filled('exam_status')) {
            $query->where('exam_status', $request->exam_status);
        }
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('gd_number', 'ilike', "%{$search}%")
                    ->orWhere('importer', 'ilike', "%{$search}%")
                    ->orWhere('hs_code', 'ilike', "%{$search}%");
            });
        }

        $gds = $query->paginate(20)->withQueryString();

        return view('pages.products.logistics.gd.exam', compact('gds'));
    }

    public function calculator()
    {
        return view('pages.products.logistics.gd.calculator');
    }

    public function landedCost(Request $request)
    {
        $gd = null;
        if ($request->filled('q')) {
            $gd = LogisticsGoodsDeclaration::with('booking')
                ->where('gd_number', strtoupper($request->q))
                ->first();
        }

        return view('pages.products.logistics.gd.landed-cost', compact('gd'));
    }

    public function paymentTracker(Request $request)
    {
        $query = LogisticsGoodsDeclaration::query()->whereNotNull('total_duty')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $gds = $query->paginate(20)->withQueryString();

        return view('pages.products.logistics.gd.payment-tracker', compact('gds'));
    }
}
