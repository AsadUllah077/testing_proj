<?php

namespace App\Http\Controllers\Logistics;

use App\Exports\Logistics\BookingsTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\Logistics\BookingsImport;
use App\Models\Company;
use App\Models\HsCode;
use App\Models\LogisticDocument;
use App\Models\LogisticFolder;
use App\Models\LogisticsBookingItem;
use App\Models\LogisticsBookingLeg;
use App\Models\LogisticsBookingStatusHistory;
use App\Models\LogisticsBookingV2;
use App\Models\LogisticsBookingV2Stage;
use App\Models\LogisticsFreightAgent;
use App\Models\LogisticsLetterOfCredit;
use App\Models\LogisticsReason;
use App\Models\LogisticsTradeDocument;
use App\Models\LogisticsTransitStop;
use App\Models\MaerskSetting;
use App\Models\Vendor;
use App\Services\MaerskApiService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ManageBookingV2Controller extends Controller
{
    private const MIRROR_COMPANY_NAME = 'Lumsden Trading LLC';

    private const DEFAULT_FREIGHT_AGENTS = [
        ['id' => 'logitrack', 'text' => 'LogiTrack (Self)'],
        ['id' => 'agility', 'text' => 'Agility Logistics'],
        ['id' => 'kn', 'text' => 'Kuehne+Nagel'],
        ['id' => 'dhl', 'text' => 'DHL Global'],
    ];

    private const DEFAULT_TRANSIT_STOPS = [
        ['id' => 'peshawar', 'text' => 'Peshawar Dry Port'],
        ['id' => 'lahore', 'text' => 'Lahore Dry Port'],
    ];

    public function syncApis(Request $request)
    {
        $companyId = session('company_id') ?? 1;
        $bookings = LogisticsBookingV2::whereNotNull('booking_id')
            ->whereNotIn('status', ['Delivered', 'Cancelled'])
            ->where(function ($q) {
                $q->where('carrier', 'ilike', '%maersk%')
                    ->orWhere('carrier', 'ilike', '%fedex%');
            })
            ->get(['id', 'booking_number', 'booking_id', 'carrier']);

        $tasks = [];
        foreach ($bookings as $b) {
            $tasks[] = [
                'id' => $b->id,
                'booking_number' => $b->booking_number,
                'booking_id' => $b->booking_id,
                'carrier' => $b->carrier,
                'provider' => Str::contains(strtolower($b->carrier), 'maersk') ? 'maersk' : 'fedex',
            ];
        }

        return response()->json(['success' => true, 'tasks' => $tasks]);
    }

    public function processSyncTask(Request $request)
    {
        $bookingId = $request->id;
        $booking = LogisticsBookingV2::findOrFail($bookingId);
        $companyId = session('company_id') ?? 1;

        if (Str::contains(strtolower($booking->carrier), 'maersk')) {
            $setting = MaerskSetting::where('company_id', $companyId)->first();
            if (! $setting || empty($setting->consumer_key)) {
                return response()->json(['success' => false, 'message' => 'Maersk API not configured for this company.']);
            }

            $api = new MaerskApiService($setting);
            $res = $api->trackEvents(['carrierBookingReference' => $booking->booking_id]);

            return response()->json([
                'success' => $res['success'],
                'data' => $res['data'] ?? $res['raw'],
                'message' => $res['success'] ? 'Maersk data synced.' : 'Maersk API Error',
            ]);
        }

        if (Str::contains(strtolower($booking->carrier), 'fedex')) {
            return response()->json([
                'success' => false,
                'message' => 'FedEx API integration is coming soon.',
                'data' => ['status' => 'pending'],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Unknown carrier API.']);
    }

    public function lookupVehicle(Request $request)
    {
        $regNo = $request->query('reg_no');
        if (empty($regNo)) {
            return response()->json(['success' => false, 'message' => 'Vehicle Registration Number is required.']);
        }

        $booking = LogisticsBookingV2::where('freight_details->inland_details->vehicle_reg', $regNo)
            ->whereNotNull('freight_details->inland_details->vehicle_model')
            ->latest('id')
            ->first();

        if ($booking) {
            $inland = $booking->getInlandInfo();

            return response()->json([
                'success' => true,
                'vehicle_model' => $inland['vehicle_model'],
                'truck_capacity' => $inland['truck_capacity'],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Vehicle details not found.']);
    }

    public function lookupDriver(Request $request)
    {
        $cnic = $request->query('cnic');
        if (empty($cnic)) {
            return response()->json(['success' => false, 'message' => 'Driver CNIC is required.']);
        }

        $booking = LogisticsBookingV2::where('freight_details->inland_details->driver_cnic', $cnic)
            ->whereNotNull('freight_details->inland_details->driver_name')
            ->latest('id')
            ->first();

        if ($booking) {
            $inland = $booking->getInlandInfo();

            return response()->json([
                'success' => true,
                'driver_name' => $inland['driver_name'],
                'driver_mobile' => $inland['driver_mobile'],
                'co_driver' => $inland['co_driver'],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Driver details not found.']);
    }

    public function index(Request $request)
    {
        // $this->autoTransitionStatuses();

        $query = LogisticsBookingV2::query()->latest();

        // Apply filters
        if ($request->filled('type')) {
            $query->where('booking_type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('etd_from')) {
            $query->whereDate('estimated_time_departure', '>=', $request->etd_from);
        }
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'ilike', "%{$search}%")
                    ->orWhere('exporter', 'ilike', "%{$search}%")
                    ->orWhere('importer', 'ilike', "%{$search}%");
            });
        }

        $bookings = $query->paginate(20)->withQueryString();

        // Load relationships for efficiency
        $bookings->load('carrier', 'hsCode', 'bookingStatusHistory');

        $reasons = LogisticsReason::all()->groupBy('type');
        $cancelReasons = $reasons->get('cancel', collect());
        $confirmReasons = $reasons->get('confirm', collect());
        $rescheduleReasons = $reasons->get('reschedule', collect());

        return view('pages.products.logistics.manage-booking.v2.index', compact(
            'bookings',
            'cancelReasons',
            'confirmReasons',
            'rescheduleReasons'
        ));
    }

    public function create()
    {
        $parentBookings = LogisticsBookingV2::whereNotIn('status', ['Cancelled'])
            ->get(['id', 'booking_number']);

        return view('pages.products.logistics.manage-booking.v2.create', compact('parentBookings'));
    }

    public function childBookings($id)
    {
        $booking = LogisticsBookingV2::findOrFail($id);
        $childBookings = collect(is_array($booking->inland_details) ? $booking->inland_details : []);
        $reasons = LogisticsReason::all()->groupBy('type');
        $confirmReasons = $reasons->get('confirm', collect());
        $rescheduleReasons = $reasons->get('reschedule', collect());

        return view(
            'pages.products.logistics.manage-booking.v2.child-booking',
            compact('booking', 'childBookings', 'confirmReasons', 'rescheduleReasons')
        );
    }

    public function store(Request $request)
    {
        if ($request->is_child == 'on' && $request->parent_id) {
            $request->validate([
                'parent_id' => 'required|exists:logistics_bookings,id',
                'booking_number' => 'required|string|max:255',
                'freight_mode' => 'required|string|max:255',
                'carrier' => 'required|string|max:255',
                'container_nos' => 'nullable|array|max:100',
                'container_nos.*' => 'nullable|string|max:255',
                'transit_stop' => 'nullable|string|max:255',
            ]);

            $parentBooking = LogisticsBookingV2::findOrFail($request->parent_id);
            $childBookingNumber = trim((string) $request->booking_number);
            $containerNumbers = $this->normalizeChildContainerNumbers($request);

            $childData = [
                'booking_identity' => [
                    'parent_id' => $request->parent_id,
                    'parent_booking_number' => $parentBooking->booking_number,
                    'booking_number' => $childBookingNumber,
                    'freight_mode' => $request->freight_mode,
                    'container_no' => $containerNumbers[0] ?? null,
                    'container_nos' => $containerNumbers,
                    'do_number' => $request->do_number,
                    'customs_channel' => $request->customs_channel,
                    'status' => 'Booked',
                    'arrival_confirmed_at' => null,
                    'delivery_confirmed_at' => null,
                ],

                'inland_carrier' => [
                    'carrier' => $request->carrier,
                    'carrier_contact' => $request->carrier_contact,
                    'carrier_contract' => $request->carrier_contract,
                    'etd' => $request->actual_etd,
                    'eta' => $request->intransit_point_eta,
                    'delivery_date' => $request->delivery_date ?: $request->child_delivery_date,
                    'return_deadline' => $request->return_deadline,
                    'pickup_point' => $request->pickup_point,
                    'transit_stop' => $request->transit_stop,
                    'final_destination' => $request->final_destination,
                    'freight_rate_pkr' => $request->freight_rate_pkr,
                    'oversize_permit' => $request->oversize_permit,
                ],

                'vehicle_driver_assignment' => [
                    'truck_type' => $request->truck_type,
                    'vehicle_reg' => $request->vehicle_reg,
                    'vehicle_model' => $request->vehicle_model,
                    'truck_capacity' => $request->truck_capacity,
                    'driver_cnic' => $request->driver_cnic,
                    'driver_name' => $request->driver_name,
                    'driver_mobile' => $request->driver_mobile,
                    'co_driver' => $request->co_driver,
                    'gps_unit_id' => $request->gps_unit_id,
                    'gps_portal' => $request->gps_portal,
                    'vehicle_inspection' => $request->vehicle_inspection,
                    'container_return_type' => $request->container_return_type,
                ],

                'route_plan_waypoints' => [
                    'route_option' => $request->route_option,
                    'total_distance' => $request->total_distance,
                    'est_transit_time' => $request->est_transit_time,
                ],

                'final_delivery_details' => [
                    'delivery_address' => $request->delivery_address,
                    'receiving_contact' => $request->receiving_contact,
                    'delivery_window' => $request->delivery_window,
                    'unloading_method' => $request->unloading_method,
                    'delivery_receipt_type' => $request->delivery_receipt_type,
                    'post_delivery_inspection' => $request->post_delivery_inspection,
                    'delivery_instructions' => $request->delivery_instructions,
                ],
            ];
            $inlandDetails = $parentBooking->inland_details ?? [];

            if (! is_array($inlandDetails)) {
                $inlandDetails = [];
            }

            if (isset($inlandDetails[$childBookingNumber])) {
                throw ValidationException::withMessages([
                    'booking_number' => 'This child booking number already exists under the selected parent booking.',
                ]);
            }

            $inlandDetails[$childBookingNumber] = $childData;

            $parentBooking->update([
                'inland_details' => $inlandDetails,
            ]);

            if ($request->has('documents')) {

                foreach ($request->documents as $index => $doc) {

                    $filePath = null;
                    $originalName = null;

                    if (
                        isset($doc['file']) &&
                        $doc['file'] instanceof UploadedFile
                    ) {

                        $filePath = $doc['file']->store(
                            'logistics/documents',
                            'public'
                        );

                        // $bookingNumber = $booking->booking_number ?? $childBookingNumber ?? $parentBooking->booking_number;
                        // $bookingId = $booking->id ?? $parentBooking->id;

                        // $documentType = $doc['document_type'] ?? 'other';
                        // $documentType = Str::slug($documentType);

                        // $originalName = $doc['file']->getClientOriginalName();
                        // $fileName = time().'_'.$originalName;

                        // $folderPath = $bookingNumber.'/'.$bookingId.'/docs/'.$documentType;

                        // $filePath = $doc['file']->storeAs(
                        //     $folderPath,
                        //     $fileName,
                        //     'azure'
                        // );

                        $originalName = $doc['file']->getClientOriginalName();
                    }

                    LogisticsTradeDocument::create([

                        'booking_id' => $parentBooking->id,

                        'child_booking_number' => $childBookingNumber,

                        'document_type' => $doc['document_type'] ?? ' ',

                        'description' => $doc['description'] ?? ' ',

                        'required_for' => $doc['required_for'] ?? ' ',

                        'status' => $doc['status'] ?? 'Pending',

                        'file_path' => $filePath,

                        'original_filename' => $originalName,

                        'company_id' => auth()->user()->company_id ?? null,

                    ]);
                }
            }

            return to_route('logistics.manage-booking.v2.child-booking', $request->parent_id)
                ->with('success', 'Child Booking create successfully');
        }

        $isDraft = $request->boolean('save_as_draft');
        $mainFieldRequirement = $isDraft ? 'nullable' : 'required';

        $validator = Validator::make($request->all(), [

            // Main Booking
            'booking_type' => $mainFieldRequirement.'|string',
            'freight_mode' => $mainFieldRequirement.'|string',

            'origin_port' => $mainFieldRequirement.'|string|max:255',
            'dest_port' => $mainFieldRequirement.'|string|max:255',

            'shipper' => $mainFieldRequirement.'|string|max:255',
            'consignee' => $mainFieldRequirement.'|string|max:255',
            'freight_forwarder' => 'nullable|string|max:255',

            'carrier_id' => $isDraft ? 'nullable|exists:logistics_v2_carriers,id' : 'required|exists:logistics_v2_carriers,id',
            'hs_code' => $mainFieldRequirement.'|string',
            'cargo_description' => $mainFieldRequirement.'|string',

            'gross_weight' => 'nullable|numeric',
            'volume_cbm' => 'nullable|numeric',
            'freight_rate' => 'nullable|numeric|min:0',
            'currency_value' => 'nullable|required_with:freight_rate|numeric|gt:0',

            'etd' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'bank_instrument_type' => 'nullable|in:bank_contract,lc,open_account,documentary_collection,po_reference_number,po_invoice_number',
            'po_reference_number' => 'nullable|string|max:255',
            'po_invoice_number' => 'nullable|string|max:255',
            'lc_number' => 'nullable|string|max:255|unique:logistics_letters_of_credit,lc_number',
            'bank_contract_number' => 'nullable|string|max:255|unique:logistics_letters_of_credit,lc_number',
            'documentary_number' => $isDraft
                ? 'nullable|string|max:255'
                : 'required_if:bank_instrument_type,documentary_collection|nullable|string|max:255',

            // Documents
            'documents' => 'nullable|array',
            'documents.*.document_type' => $isDraft ? 'nullable|string' : 'required_with:documents|string',
            'documents.*.status' => 'nullable|string',
            'documents.*.description' => 'nullable|string',
            'documents.*.required_for' => 'nullable|string',
            // 'documents.*.file' => 'nullable|file|max:500000',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                "data"=>"testing data",
                'message' => $validator->errors()->all(),
            ]);
        }

        try {
            $shipperName = trim((string) $request->shipper);
            $mirrorCompany = $isDraft ? null : $this->resolveMirrorCompany();

            if (! $isDraft && ! $mirrorCompany && ! $request->boolean('allow_missing_mirror_company')) {
                return response()->json([
                    'success' => false,
                    'requires_confirmation' => true,
                    'message' => 'Company "'.self::MIRROR_COMPANY_NAME.'" was not found. Continue creating the booking without creating the mirror entry from the shipper field?',
                ], 409);
            }

            /*
            |--------------------------------------------------------------------------
            | HS CODE
            |--------------------------------------------------------------------------
            */
            $hsCode = filled($request->hs_code)
                ? HsCode::where('hs_code', $request->hs_code)->first()
                : null;

            if (filled($request->hs_code) && ! $hsCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'HS Code not found',
                ]);
            }

            DB::beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | CREATE BOOKING
            |--------------------------------------------------------------------------
            */
            $booking = LogisticsBookingV2::create(array_merge([

                'booking_number' => $request->booking_id,

                'booking_type' => $request->booking_type,

                'freight_mode' => $request->freight_mode,

                'freight_sub_mode' => $request->multimodal_sub_mode,

                'origin_port' => $request->origin_port,

                'destination_port' => $request->dest_port,

                'place_of_receipt' => $request->place_of_receipt,

                'final_delivery_place' => $request->final_delivery_place,

                'exporter' => $request->shipper,

                'importer' => $request->consignee,

                'notify_party' => $request->notify_party,

                'agent' => $request->freight_forwarder,

                'estimated_time_departure' => $request->etd,

                'estimated_time_delivery' => $request->delivery_date,

                'service_level' => $request->service_level,

                'hs_code_id' => $hsCode?->id,

                // you need carrier select field in form
                'carrier_id' => $request->filled('carrier_id') ? $request->carrier_id : null,

                'gross_weight' => $request->gross_weight,

                'volume_cbm' => $request->volume_cbm,

                'declared_cargo_value' => $request->cargo_value,

                'dangerous_good' => $request->dangerous_goods,

                'remarks' => $request->special_instructions,

                'incoterms' => $request->incoterms,

                'freight_terms' => $request->freight_terms,

                'quoted_freight_rate' => $request->freight_rate,
                'company_id' => auth()->user()->company_id ?? null,
                'status' => $isDraft ? 'Draft' : 'Booked',

            ], $this->bankInstrumentData($request)));
            $this->createBankInstrumentRecord($booking, $request);

            $booking2 = null;
            $mirrorCompany = null;

            if ($request->shipper == 'Lumsden Trading LLC') {

                $mirrorCompany = Company::where('name', 'Lumsden Trading LLC')->firstOrFail();
                $booking1 = $booking->replicate();
                $booking1->company_id = $mirrorCompany->id;
                $booking1->save();
                $booking2 = $booking1;
            }
            /*
            |--------------------------------------------------------------------------
            | BOOKING ITEMS
            |--------------------------------------------------------------------------
            */

            if ($request->has('fcl_details')) {

                foreach ($request->fcl_details as $item) {
                    if ($isDraft && ! filled($item['container_number'] ?? null) && ! filled($item['package_type'] ?? null)) {
                        continue;
                    }

                    $isContainerPackage = ($item['package_type'] ?? null) === 'container';

                    $bookingItem = LogisticsBookingItem::create([

                        'booking_id' => $booking->id,

                        'carrier_id' => $request->carrier_id,

                        'freight_mode' => $request->freight_mode,

                        'freight_sub_mode' => $request->multimodal_sub_mode,

                        'container_type' => $request->container_type ?? null,

                        'package_type' => $item['package_type'] ?? null,

                        'container_number' => $isContainerPackage ? ($item['container_number'] ?? null) : null,

                        'quantity' => $item['qty'] ?? null,

                        'pallet_count' => $isContainerPackage ? ($item['pallet_count'] ?? null) : null,

                        'pallets' => $isContainerPackage ? ($item['pallets'] ?? null) : null,

                    ]);
                    if ($booking2 && $mirrorCompany) {
                        $bookingItem1 = $bookingItem->replicate();
                        $bookingItem1->booking_id = $booking2->id;
                        $bookingItem1->company_id = $mirrorCompany->id;
                        $bookingItem1->save();
                    }
                }
            }

            if ($request->has('lcl_details')) {

                foreach ($request->lcl_details as $item) {
                    if ($isDraft && ! filled($item['item_description'] ?? null) && ! filled($item['description'] ?? null) && ! filled($item['weight'] ?? null)) {
                        continue;
                    }

                    $bookingItem = LogisticsBookingItem::create([

                        'booking_id' => $booking->id,

                        'carrier_id' => $request->carrier_id,

                        'freight_mode' => $request->freight_mode,

                        'freight_sub_mode' => $request->multimodal_sub_mode,

                        'package_type' => $request->package_type ?? null,

                        'item_description' => $item['item_description'] ?? null,

                        'package' => $item['package'] ?? null,

                        'quantity' => $item['quantity'] ?? null,

                        'length' => $item['length'] ?? null,

                        'width' => $item['width'] ?? null,

                        'height' => $item['height'] ?? null,

                        'dimension_unit' => $item['dimension_unit'] ?? null,

                        'weight' => $item['weight'] ?? null,

                        'image_path' => null,
                    ]);

                    if ($booking2 && $mirrorCompany) {
                        $bookingItem1 = $bookingItem->replicate();
                        $bookingItem1->booking_id = $booking2->id;
                        $bookingItem1->company_id = $mirrorCompany->id;
                        $bookingItem1->save();
                    }
                }
            }

            if ($request->has('air_details')) {

                foreach ($request->air_details as $item) {
                    if ($isDraft && ! filled($item['item_description'] ?? null) && ! filled($item['description'] ?? null) && ! filled($item['weight'] ?? null)) {
                        continue;
                    }

                    $bookingItem = LogisticsBookingItem::create([

                        'booking_id' => $booking->id,

                        'carrier_id' => $request->carrier_id,

                        'freight_mode' => $request->freight_mode,

                        'freight_sub_mode' => $request->multimodal_sub_mode,

                        'package_type' => $request->package_type ?? null,

                        'item_description' => $item['item_description'] ?? null,

                        'package' => $item['package'] ?? null,

                        'quantity' => $item['quantity'] ?? null,

                        'length' => $item['length'] ?? null,

                        'width' => $item['width'] ?? null,

                        'height' => $item['height'] ?? null,

                        'dimension_unit' => $item['dimension_unit'] ?? null,

                        'weight' => $item['weight'] ?? null,

                        'image_path' => null,
                    ]);
                    if ($booking2 && $mirrorCompany) {
                        $bookingItem1 = $bookingItem->replicate();
                        $bookingItem1->booking_id = $booking2->id;
                        $bookingItem1->company_id = $mirrorCompany->id;
                        $bookingItem1->save();
                    }
                }
            }

            if ($request->has('truck_details')) {

                foreach ($request->truck_details as $item) {
                    if ($isDraft && ! filled($item['item_description'] ?? null) && ! filled($item['description'] ?? null) && ! filled($item['weight'] ?? null)) {
                        continue;
                    }

                    $bookingItem = LogisticsBookingItem::create([

                        'booking_id' => $booking->id,

                        'carrier_id' => $request->carrier_id,

                        'freight_mode' => $request->freight_mode,

                        'freight_sub_mode' => $request->multimodal_sub_mode,

                        'package_type' => $request->package_type ?? null,

                        'item_description' => $item['item_description'] ?? null,

                        'package' => $item['package'] ?? null,

                        'quantity' => $item['quantity'] ?? null,

                        'length' => $item['length'] ?? null,

                        'width' => $item['width'] ?? null,

                        'height' => $item['height'] ?? null,

                        'dimension_unit' => $item['dimension_unit'] ?? null,

                        'weight' => $item['weight'] ?? null,

                        'image_path' => null,
                    ]);

                    if ($booking2 && $mirrorCompany) {
                        $bookingItem1 = $bookingItem->replicate();
                        $bookingItem1->booking_id = $booking2->id;
                        $bookingItem1->company_id = $mirrorCompany->id;
                        $bookingItem1->save();
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | DOCUMENTS
            |--------------------------------------------------------------------------
            */
            
            if ($request->has('documents')) {

                foreach ($request->documents as $index => $doc) {
                    if (
                        $isDraft &&
                        ! filled($doc['document_type'] ?? null) &&
                        ! filled($doc['description'] ?? null) &&
                        ! filled($doc['required_for'] ?? null) &&
                        ! isset($doc['file'])
                    ) {
                        continue;
                    }

                    $filePath = null;
                    $originalName = null;

                    if (
                        isset($doc['file']) &&
                        $doc['file'] instanceof UploadedFile
                    ) {
                    // if (
                    //     isset($doc['file']) &&
                    //     $doc['file'] instanceof UploadedFile &&
                    //     $doc['file']->isValid()
                    // ) {

                        // $bookingNumber = $booking->booking_number;
                        // $bookingId = $booking->id;

                        // $documentType = $doc['document_type'] ?? 'other';
                        // $documentType = Str::slug($documentType);

                        // $originalName = $doc['file']->getClientOriginalName();
                        // $fileName = time().'_'.$originalName;

                        // $folderPath = $bookingNumber.'/'.$bookingId.'/docs/'.$documentType;

                        // $filePath = $doc['file']->storeAs(
                        //     $folderPath,
                        //     $fileName,
                        //     'azure'
                        // );
                        $filePath = $doc['file']->store(
                            'logistics/documents',
                            'public'
                        );

                        $originalName = $doc['file']->getClientOriginalName();
                    } 
                    $tradeDocument = LogisticsTradeDocument::create([

                        'booking_id' => $booking->id,

                        'document_type' => $doc['document_type'],

                        'description' => $doc['description'] ?? null,

                        'required_for' => $doc['required_for'] ?? null,

                        'status' => $doc['status'] ?? 'Pending',

                        'file_path' => $filePath,

                        'original_filename' => $originalName,

                        'company_id' => auth()->user()->company_id ?? null,

                    ]);

                    // if ($request->shipper == 'Lumsden Trading LLC') {

                    //     $company = Company::where('name', 'Lumsden Trading LLC')->firstOrFail();
                    //     $bookingItem1 = $tradeDocument->replicate();
                    //     $bookingItem1->company_id = $company->id;
                    //     $bookingItem1->save();
                    // }
                }
            }

            $history = LogisticsBookingStatusHistory::create([
                'booking_id' => $booking->id,
                'event_type' => 'Created',
                'new_date' => $booking->created_at,
                'status_changed_to' => $isDraft ? 'Draft' : 'Booked',
                'reason' => $isDraft ? 'Booking saved as draft' : 'Booking created',
                'action_by' => auth()->user()->id,
            ]);

            if ($booking2 && $mirrorCompany) {
                $history1 = $history->replicate();
                $history1->booking_id = $booking2->id;
                $history1->company_id = $mirrorCompany->id;
                $history1->save();
            }

            if ($request->filled('freight_rate')) {
                \App\Models\LogisticsLcPayment::create([
                    'booking_id' => $booking->id,
                    'date' => now(),
                    'details' => "Booking No: {$booking->booking_number}" . ($request->incoterms ? ", Incoterm: {$request->incoterms}" : ""),
                    'amount' => $request->freight_rate,
                    'currency' => 'USD',
                    'currency_value' => $request->currency_value,
                    'payment_type' => 'freight_payment',
                    'entry_source' => 'manage_booking',
                    'remarks' => "Freight Terms: " . ($request->freight_terms ?? ''),
                    'status' => 'pending',
                    'company_id' => auth()->user()->company_id ?? null,
                ]);

                if ($booking2 && $mirrorCompany) {
                    \App\Models\LogisticsLcPayment::create([
                        'booking_id' => $booking2->id,
                        'date' => now(),
                        'details' => "Booking No: {$booking2->booking_number}" . ($request->incoterms ? ", Incoterm: {$request->incoterms}" : ""),
                        'amount' => $request->freight_rate,
                        'currency' => 'USD',
                        'currency_value' => $request->currency_value,
                        'payment_type' => 'freight_payment',
                        'entry_source' => 'manage_booking',
                        'remarks' => "Freight Terms: " . ($request->freight_terms ?? ''),
                        'status' => 'pending',
                        'company_id' => $mirrorCompany->id,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $isDraft ? 'Booking draft saved successfully' : 'Manage Booking create successfully',
                'redirect_to' => route('logistics.manage-booking.v2.index'),
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'data' => 'no data',
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function resolveMirrorCompany(): ?Company
    {
        return Company::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower(self::MIRROR_COMPANY_NAME)])
            ->first();
    }

    private function mirrorShipperToCompany(string $shipperName, int $companyId): void
    {
        $existingVendor = Vendor::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereRaw('LOWER(name) = ?', [Str::lower($shipperName)])
            ->first();

        if ($existingVendor) {
            return;
        }

        Vendor::withoutGlobalScope('company')->create([
            'company_id' => $companyId,
            'name' => $shipperName,
            'email' => null,
            'phone' => null,
            'address' => null,
        ]);
    }

    public function show($id)
    {
        $booking = LogisticsBookingV2::with(['statusHistories', 'carrier', 'hsCode', 'items'])->findOrFail($id);
        $documents = $booking->documents()->latest()->get();

        return view('pages.products.logistics.manage-booking.v2.show', compact('booking', 'documents'));
    }

    public function edit($id)
    {
        $booking = LogisticsBookingV2::with([
            'items',
            'hsCode',
            'carrier',
            'lcPayments' => fn ($query) => $query->where('entry_source', 'manage_booking'),
        ])->findOrFail($id);
        $documents = $booking->documents()->latest()->get();
        $parentBookings = LogisticsBookingV2::where('id', '!=', $id)
            ->whereNotIn('status', ['Cancelled'])
            ->get(['id', 'booking_number']);

        return view('pages.products.logistics.manage-booking.v2.edit', compact('booking', 'documents', 'parentBookings'));
    }

    public function editChildBooking($id, $childBookingNumber)
    {
        $booking = LogisticsBookingV2::findOrFail($id);
        $childBooking = $this->findChildBookingOrFail($booking, $childBookingNumber);
        $documents = $booking->documents()
            ->where('child_booking_number', $childBookingNumber)
            ->latest()
            ->get();
        $parentBookings = LogisticsBookingV2::whereNotIn('status', ['Cancelled'])
            ->get(['id', 'booking_number']);
        $isChildEdit = true;

        return view('pages.products.logistics.manage-booking.v2.edit', compact(
            'booking',
            'documents',
            'parentBookings',
            'childBooking',
            'childBookingNumber',
            'isChildEdit'
        ));
    }

    public function tracking($id)
    {
        $booking = LogisticsBookingV2::with(['statusHistories', 'documents'])->findOrFail($id);
        $tracking = $this->buildTrackingData($booking);

        return view('pages.products.logistics.manage-booking.v2.tracking', compact('booking', 'tracking'));
    }

    public function update(Request $request, $id)
    {
        $booking = LogisticsBookingV2::findOrFail($id);
        $isChild = $request->has('is_child') && $request->filled('parent_id');

        $rules = [
            'booking_id' => 'nullable|string',
            'booking_type' => $isChild ? 'nullable|string' : 'required|string',
            'freight_mode' => $isChild ? 'nullable|string' : 'required|string',
            'origin_port' => $isChild ? 'nullable|string' : 'required|string',
            'dest_port' => $isChild ? 'nullable|string' : 'required|string',
            'shipper' => $isChild ? 'nullable|string' : 'required|string',
            'consignee' => $isChild ? 'nullable|string' : 'required|string',
            'freight_forwarder' => 'nullable|string|max:255',
            'freight_rate' => 'nullable|numeric|min:0',
            'currency_value' => 'nullable|required_with:freight_rate|numeric|gt:0',
            'carrier_id' => $isChild ? 'nullable' : 'required|exists:logistics_v2_carriers,id',
            'etd' => $isChild ? 'nullable|date' : 'required|date',
            'hs_code' => $isChild ? 'nullable|string' : 'required|string',
            'cargo_description' => $isChild ? 'nullable|string' : 'required|string',
            'incoterms' => $isChild ? 'nullable|string' : 'required|string',
            'bank_instrument_type' => $isChild ? 'nullable' : 'nullable|in:bank_contract,lc,open_account,documentary_collection,po_reference_number,po_invoice_number',
            'po_reference_number' => $isChild ? 'nullable' : 'nullable|string|max:255',
            'po_invoice_number' => $isChild ? 'nullable' : 'nullable|string|max:255',
            'lc_number' => $isChild ? 'nullable' : 'nullable|string|max:255|unique:logistics_letters_of_credit,lc_number',
            'bank_contract_number' => $isChild ? 'nullable' : 'nullable|string|max:255|unique:logistics_letters_of_credit,lc_number',
            'documentary_number' => $isChild
                ? 'nullable'
                : 'required_if:bank_instrument_type,documentary_collection|nullable|string|max:255',
        ];

        $request->validate($rules);

        DB::transaction(function () use ($request, $booking, $isChild) {
            $hsCodeId = $booking->hs_code_id;

            if (! $isChild && filled($request->hs_code)) {
                $hsCode = HsCode::where('hs_code', $request->hs_code)->first();

                if (! $hsCode) {
                    throw ValidationException::withMessages([
                        'hs_code' => 'Selected HS code was not found.',
                    ]);
                }

                $hsCodeId = $hsCode->id;
            }

            $booking->update($this->buildBookingUpdateData($request, $booking, $isChild, $hsCodeId)); /*
                'parent_id' => $isChild ? $request->parent_id : null,
                'is_child' => $isChild,
                'booking_number' => $request->booking_id,
                'booking_type' => $request->booking_type,
                'freight_mode' => $request->freight_mode,
                'freight_sub_mode' => $request->multimodal_sub_mode,
                'origin_port' => $isChild ? ($request->origin_port ?? 'Local') : $request->origin_port,
                'destination_port' => $isChild ? ($request->dest_port ?? 'Local') : $request->dest_port,
                'place_of_receipt' => $isChild ? $request->pickup_point : $request->place_of_receipt,
                'final_delivery_place' => $isChild ? ($request->final_destination ?? $request->delivery_address) : $request->final_delivery_place,
                'exporter' => $isChild ? null : $request->shipper,
                'importer' => $isChild ? null : $request->consignee,
                'notify_party' => $isChild ? null : $request->notify_party,
                'agent' => $request->freight_forwarder,
                'carrier_id' => $request->carrier_id,
                'container_type' => $request->container_type,
                'container_count' => $request->container_count,
                'service_level' => $request->service_level ?? 'standard',
                'estimated_time_departure' => $request->etd,
                'eta' => $request->eta,
                'estimated_time_delivery' => $request->delivery_date,
                'hs_code_id' => $isChild ? $booking->hs_code_id : $hsCodeId,
                'cargo_description' => $isChild ? null : $request->cargo_description,
                'gross_weight' => $request->gross_weight,
                'volume_cbm' => $request->volume_cbm,
                'declared_cargo_value' => $request->cargo_value,
                'dangerous_goods' => $request->dangerous_goods === 'imdg_other' ? ($request->dangerous_goods_custom ?? 'Yes — Other') : ($request->dangerous_goods ?? 'no'),
                'dangerous_goods_custom' => $request->dangerous_goods === 'imdg_other' ? $request->dangerous_goods_custom : null,
                'dangerous_good' => $request->dangerous_goods === 'imdg_other' ? ($request->dangerous_goods_custom ?? 'Yes - Other') : ($request->dangerous_goods ?? 'no'),
                'special_instructions' => $isChild ? $request->delivery_instructions : $request->special_instructions,
                'remarks' => $isChild ? $request->delivery_instructions : $request->special_instructions,
                'incoterms' => $isChild ? ($request->incoterms ?? 'DAP') : $request->incoterms,
                'freight_terms' => $request->freight_terms,
                'quoted_freight_rate' => $request->freight_rate,
                'lc_po_reference' => $request->lc_po_reference,
                'freight_details' => $this->getFreightDetails($request),
                'package_type' => $request->package_type,
                'plate_type' => $request->plate_type,
                'truck_type' => $request->truck_type,
                'number_of_trucks' => $request->number_of_trucks,

                // Inland child dedicated columns
                'gd_number' => $isChild ? $request->gd_number : null,
                'do_number' => $isChild ? $request->do_number : null,
                'customs_channel' => $isChild ? $request->customs_channel : null,
                'carrier_contact' => $isChild ? $request->carrier_contact : null,
                'carrier_contract' => $isChild ? $request->carrier_contract : null,
                'return_deadline' => $isChild ? $request->return_deadline : null,
                'freight_rate_pkr' => $isChild ? $request->freight_rate_pkr : null,
                'transit_stop' => $isChild ? $request->transit_stop : null,
                'oversize_permit' => $isChild ? $request->oversize_permit : null,
                'vehicle_reg' => $isChild ? $request->vehicle_reg : null,
                'vehicle_model' => $isChild ? $request->vehicle_model : null,
                'truck_capacity' => $isChild ? $request->truck_capacity : null,
                'driver_name' => $isChild ? $request->driver_name : null,
                'driver_cnic' => $isChild ? $request->driver_cnic : null,
                'driver_mobile' => $isChild ? $request->driver_mobile : null,
                'co_driver' => $isChild ? $request->co_driver : null,
                'gps_unit_id' => $isChild ? $request->gps_unit_id : null,
                'gps_portal' => $isChild ? $request->gps_portal : null,
                'vehicle_inspection' => $isChild ? $request->vehicle_inspection : null,
                'container_return_type' => $isChild ? $request->container_return_type : null,
                'route_option' => $isChild ? $request->route_option : null,
                'total_distance' => $isChild ? $request->total_distance : null,
                'est_transit_time' => $isChild ? $request->est_transit_time : null,
                'receiving_contact' => $isChild ? $request->receiving_contact : null,
                'delivery_window' => $isChild ? $request->delivery_window : null,
                'unloading_method' => $isChild ? $request->unloading_method : null,
                'delivery_receipt_type' => $isChild ? $request->delivery_receipt_type : null,
                'post_delivery_inspection' => $isChild ? $request->post_delivery_inspection : null,
            */

            // Ensure folder exists in central Document Manager
            LogisticFolder::firstOrCreate([
                'name' => $booking->booking_id ?? $booking->booking_number,
                'parent_id' => null,
            ]);

            if (! $isChild) {
                $this->createBankInstrumentRecord($booking, $request);
                $this->syncBookingItems($booking, $request);
            }

            $this->saveTradeDocuments($booking, $request);
            // Routing legs removed

            if ($request->filled('freight_rate')) {
                \App\Models\LogisticsLcPayment::updateOrCreate(
                    [
                        'booking_id' => $booking->id,
                        'entry_source' => 'manage_booking',
                    ],
                    [
                        'date' => now(),
                        'details' => "Booking No: {$booking->booking_number}" . ($request->incoterms ? ", Incoterm: {$request->incoterms}" : ""),
                        'amount' => $request->freight_rate,
                        'currency' => 'USD',
                        'currency_value' => $request->currency_value,
                        'payment_type' => 'freight_payment',
                        'remarks' => "Freight Terms: " . ($request->freight_terms ?? ''),
                        'status' => 'pending',
                        'company_id' => auth()->user()->company_id ?? null,
                    ]
                );
            } else {
                \App\Models\LogisticsLcPayment::where('booking_id', $booking->id)
                    ->where('entry_source', 'manage_booking')
                    ->delete();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Booking updated successfully.',
        ]);
    }

    public function updateChildBooking(Request $request, $id, $childBookingNumber)
    {
        $booking = LogisticsBookingV2::findOrFail($id);
        $existingChild = $this->findChildBookingOrFail($booking, $childBookingNumber);

        $request->validate([
            'parent_id' => 'required|exists:logistics_bookings,id',
            'booking_number' => 'required|string|max:255',
            'freight_mode' => 'required|string|max:255',
            'carrier' => 'required|string|max:255',
            'container_nos' => 'nullable|array|max:100',
            'container_nos.*' => 'nullable|string|max:255',
            'transit_stop' => 'nullable|string|max:255',
        ]);

        $targetParent = LogisticsBookingV2::findOrFail($request->parent_id);
        $updatedChildBookingNumber = trim((string) $request->booking_number);
        $targetDetails = is_array($targetParent->inland_details) ? $targetParent->inland_details : [];
        $isSameChildSlot = $booking->id === $targetParent->id && $childBookingNumber === $updatedChildBookingNumber;

        if (isset($targetDetails[$updatedChildBookingNumber]) && ! $isSameChildSlot) {
            throw ValidationException::withMessages([
                'booking_number' => 'This child booking number already exists under the selected parent booking.',
            ]);
        }

        $updatedChildData = $this->buildChildBookingPayload($request, $targetParent, $existingChild, $updatedChildBookingNumber);

        DB::transaction(function () use (
            $booking,
            $targetParent,
            $childBookingNumber,
            $updatedChildBookingNumber,
            $updatedChildData,
            $request
        ) {
            $sourceDetails = is_array($booking->inland_details) ? $booking->inland_details : [];
            unset($sourceDetails[$childBookingNumber]);
            $booking->update(['inland_details' => $sourceDetails]);

            $targetDetails = is_array($targetParent->inland_details) ? $targetParent->inland_details : [];
            $targetDetails[$updatedChildBookingNumber] = $updatedChildData;
            $targetParent->update(['inland_details' => $targetDetails]);

            LogisticsTradeDocument::query()
                ->where('booking_id', $booking->id)
                ->where('child_booking_number', $childBookingNumber)
                ->update([
                    'booking_id' => $targetParent->id,
                    'child_booking_number' => $updatedChildBookingNumber,
                ]);

            $this->saveChildTradeDocuments($targetParent, $request, $updatedChildBookingNumber);
        });

        return response()->json([
            'success' => true,
            'message' => 'Child booking updated successfully.',
            'redirect_to' => route('logistics.manage-booking.v2.child-booking', $targetParent->id),
        ]);
    }

    public function deleteSummary($id)
    {
        $booking = LogisticsBookingV2::findOrFail($id);

        return response()->json([
            'booking_number' => $booking->booking_number ?: (string) $booking->id,
            'stages' => $this->countBookingRecords('logistics_booking_v2_stages', 'booking_v2_id', $booking->id),
            'documents' => $this->countBookingRecords('logistics_trade_documents', 'booking_id', $booking->id),
            'shipments' => $this->countBookingRecords('logistics_shipments', 'booking_v2_id', $booking->id),
            'goods_decls' => $this->countBookingRecords('logistics_goods_declarations', 'booking_v2_id', $booking->id),
        ]);
    }

    public function destroy($id)
    {
        $booking = LogisticsBookingV2::findOrFail($id);

        DB::transaction(function () use ($booking) {
            // Delete trade document files from storage
            foreach ($booking->documents as $doc) {
                if ($doc->file_path) {
                    Storage::disk('public')->delete($doc->file_path);
                }
            }

            // Cascade delete all related records
            $booking->documents()->delete();
            $booking->shipments()->delete();
            $booking->goodsDeclarations()->delete();
            $booking->delete();
        });

        return response()->json(['success' => true, 'message' => 'Booking and all related records deleted successfully.']);
    }

    private function buildBookingUpdateData(Request $request, LogisticsBookingV2 $booking, bool $isChild, ?int $hsCodeId): array
    {
        $dangerousGood = $request->dangerous_goods === 'imdg_other'
            ? ($request->dangerous_goods_custom ?? 'Yes - Other')
            : ($request->dangerous_goods ?? 'no');

        return array_merge([
            // 'parent_id' => $isChild ? $request->parent_id : null,
            // 'is_child' => $isChild,
            'booking_number' => $request->booking_id,
            'booking_type' => $request->booking_type,
            'freight_mode' => $request->freight_mode,
            'freight_sub_mode' => $request->input('multimodal_sub_mode', $booking->freight_sub_mode),
            'origin_port' => $isChild
                ? ($request->input('origin_port', $booking->origin_port ?: 'Local'))
                : $request->origin_port,
            'destination_port' => $isChild
                ? ($request->input('dest_port', $booking->destination_port ?: 'Local'))
                : $request->dest_port,
            'place_of_receipt' => $isChild
                ? $request->input('pickup_point', $booking->place_of_receipt)
                : $request->input('place_of_receipt', $booking->place_of_receipt),
            'final_delivery_place' => $isChild
                ? ($request->final_destination ?? $request->delivery_address ?? $booking->final_delivery_place)
                : $request->input('final_delivery_place', $booking->final_delivery_place),
            'exporter' => $isChild ? null : $request->shipper,
            'importer' => $isChild ? null : $request->consignee,
            'notify_party' => $isChild ? null : $request->input('notify_party', $booking->notify_party),
            'agent' => $request->input('freight_forwarder', $booking->agent),
            'carrier_id' => $request->input('carrier_id', $booking->carrier_id),
            'service_level' => $request->input('service_level', $booking->service_level ?? 'standard'),
            'estimated_time_departure' => $request->input('etd', $booking->estimated_time_departure),
            'estimated_time_delivery' => $request->input('delivery_date', $booking->estimated_time_delivery),
            'hs_code_id' => $isChild ? $booking->hs_code_id : $hsCodeId,
            'gross_weight' => $request->input('gross_weight', $booking->gross_weight),
            'volume_cbm' => $request->input('volume_cbm', $booking->volume_cbm),
            'declared_cargo_value' => $request->input('cargo_value', $booking->declared_cargo_value),
            'dangerous_good' => $dangerousGood,
            'remarks' => $isChild
                ? $request->input('delivery_instructions', $booking->remarks)
                : $request->input('special_instructions', $booking->remarks),
            'incoterms' => $isChild
                ? $request->input('incoterms', $booking->incoterms ?? 'DAP')
                : $request->incoterms,
            'freight_terms' => $request->input('freight_terms', $booking->freight_terms),
            'quoted_freight_rate' => $request->input('freight_rate', $booking->quoted_freight_rate),
        ], $isChild ? [] : $this->bankInstrumentData($request));
    }

    private function bankInstrumentData(Request $request): array
    {
        $type = $request->input('bank_instrument_type');
        $allowedTypes = [
            'bank_contract',
            'lc',
            'open_account',
            'documentary_collection',
            'po_reference_number',
            'po_invoice_number',
        ];

        return [
            'bank_instrument_type' => in_array($type, $allowedTypes, true) ? $type : null,
            'po_reference_number' => $request->input('po_reference_number'),
            'po_invoice_number' => $type === 'po_invoice_number' ? $request->input('po_invoice_number') : null,
            'documentary_number' => $type === 'documentary_collection'
                ? $request->input('documentary_number')
                : null,
        ];
    }

    private function createBankInstrumentRecord(LogisticsBookingV2 $booking, Request $request): void
    {
        $companyId = auth()->check() ? (int) auth()->user()->company_id ?: null : null;

        if ($request->filled('lc_number')) {
            LogisticsLetterOfCredit::create([
                'booking_id' => $booking->id,
                'lc_number' => trim((string) $request->input('lc_number')),
                'record_type' => 'lc',
                'status' => 'Draft',
                'company_id' => $companyId,
            ]);
        }

        if ($request->filled('bank_contract_number')) {
            LogisticsLetterOfCredit::create([
                'booking_id' => $booking->id,
                'lc_number' => trim((string) $request->input('bank_contract_number')),
                'record_type' => 'bank_contract',
                'status' => 'Draft',
                'company_id' => $companyId,
            ]);
        }
    }

    private function countBookingRecords(string $table, string $foreignKey, int $bookingId): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $foreignKey)) {
            return 0;
        }

        return DB::table($table)->where($foreignKey, $bookingId)->count();
    }

    private function syncBookingItems(LogisticsBookingV2 $booking, Request $request): void
    {
        LogisticsBookingItem::where('booking_id', $booking->id)->delete();

        $companyId = auth()->check() ? (int) auth()->user()->company_id ?: null : null;

        foreach ($request->input('fcl_details', []) as $item) {
            if (! filled($item['container_number'] ?? null) && ! filled($item['package_type'] ?? null)) {
                continue;
            }

            $isContainerPackage = ($item['package_type'] ?? null) === 'container';

            LogisticsBookingItem::create([
                'booking_id' => $booking->id,
                'carrier_id' => $request->carrier_id,
                'company_id' => $companyId,
                'freight_mode' => 'fcl_sea',
                'freight_sub_mode' => $request->multimodal_sub_mode,
                'container_type' => $request->container_type,
                'package_type' => $item['package_type'] ?? null,
                'container_number' => $isContainerPackage ? ($item['container_number'] ?? null) : null,
                'quantity' => $item['qty'] ?? null,
                'pallet_count' => $isContainerPackage ? ($item['pallet_count'] ?? null) : null,
                'pallets' => $isContainerPackage && isset($item['pallets']) ? json_encode($item['pallets']) : null,
            ]);
        }

        foreach ($request->input('lcl_details', []) as $item) {
            if (! filled($item['description'] ?? null) && ! filled($item['packages'] ?? null) && ! filled($item['weight'] ?? null)) {
                continue;
            }

            LogisticsBookingItem::create([
                'booking_id' => $booking->id,
                'carrier_id' => $request->carrier_id,
                'company_id' => $companyId,
                'freight_mode' => 'lcl_sea',
                'freight_sub_mode' => $request->multimodal_sub_mode,
                'package_type' => $request->package_type,
                'item_description' => $item['description'] ?? $item['item_description'] ?? null,
                'package' => $item['packages'] ?? $item['package'] ?? null,
                'quantity' => $item['quantity'] ?? $item['packages'] ?? null,
                'length' => $item['length'] ?? null,
                'width' => $item['width'] ?? null,
                'height' => $item['height'] ?? null,
                'dimension_unit' => $item['dimension_unit'] ?? null,
                'weight' => $item['weight'] ?? null,
            ]);
        }

        foreach ($request->input('air_details', []) as $item) {
            if (! filled($item['description'] ?? null) && ! filled($item['plates'] ?? null) && ! filled($item['weight'] ?? null)) {
                continue;
            }

            LogisticsBookingItem::create([
                'booking_id' => $booking->id,
                'carrier_id' => $request->carrier_id,
                'company_id' => $companyId,
                'freight_mode' => 'air',
                'freight_sub_mode' => $request->multimodal_sub_mode,
                'package_type' => $request->plate_type,
                'item_description' => $item['description'] ?? $item['item_description'] ?? null,
                'package' => $item['plates'] ?? $item['package'] ?? null,
                'quantity' => $item['plates'] ?? $item['quantity'] ?? null,
                'length' => $item['length'] ?? null,
                'width' => $item['width'] ?? null,
                'height' => $item['height'] ?? null,
                'dimension_unit' => $item['dimension_unit'] ?? null,
                'weight' => $item['weight'] ?? null,
            ]);
        }
    }

    public function cancel(Request $request, $id)
    {
        $booking = LogisticsBookingV2::findOrFail($id);
        $reason = $request->cancel_reason;
        if ($reason === 'Other' && $request->filled('other_reason')) {
            $reason = $request->other_reason;
        }

        $booking->update([
            'status' => 'Cancelled',
        ]);

        $check_reason = LogisticsReason::where('type', 'cancel')->where('reason_text', $reason)->first();

        if (! $check_reason) {
            LogisticsReason::create([
                'type' => 'cancel',
                'reason_text' => $reason,
                'is_system' => false,
            ]);
        }

        LogisticsBookingStatusHistory::create([
            'booking_id' => $booking->id,
            'event_type' => 'cancelled',
            'original_date' => Carbon::today(),
            'new_date' => Carbon::today(),
            'status_changed_to' => 'Cancelled',
            'reason' => $reason,
            'action_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Booking cancelled successfully.');
    }

    public function documents(Request $request)
    {
        $booking = null;
        $documentScope = null;

        if ($request->filled('q')) {
            $query = strtoupper(trim($request->q));

            $booking = LogisticsBookingV2::whereRaw('UPPER(booking_number) = ?', [$query])
                ->orWhereRaw('UPPER(booking_id) = ?', [$query])
                ->first();

            if ($booking) {
                $documents = $booking->documents()->latest()->get();
                $documentScope = $booking->booking_number ?? $booking->booking_id;
            } else {
                $documents = LogisticsTradeDocument::with('booking')
                    ->whereRaw('UPPER(child_booking_number) = ?', [$query])
                    ->latest()
                    ->get();

                $booking = $documents->first()?->booking;
                $documentScope = $documents->isNotEmpty() ? $query : null;
            }
        } else {
            $documents = LogisticsTradeDocument::with('booking')
                ->latest()
                ->get();
        }

        return view(
            'pages.products.logistics.manage-booking.v2.documents',
            compact('booking', 'documents', 'documentScope')
        );

        if ($request->filled('q')) {
            // Search mode — find the specific booking
            $booking = LogisticsBookingV2::where('booking_id', trim($request->q))
                ->orWhere('booking_number', strtoupper(trim($request->q)))
                ->first();
            $documents = $booking
                ? $booking->documents()->latest()->get()
                : collect();
        } else {
            $documents = LogisticsTradeDocument::with('booking')   // adjust model name if different
                ->latest()
                ->get();
        }

        return view(
            'pages.products.logistics.manage-booking.v2.documents',
            compact('booking', 'documents')
        );
    }

    public function storeDocument(Request $request, $id)
    {
        $booking = LogisticsBookingV2::findOrFail($id);

        $request->validate([
            'document_type' => 'required|string',
            'status' => 'required|string',
            'file' => 'nullable|file|max:10240',
        ]);

        $filePath = null;
        $originalFilename = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalFilename = $file->getClientOriginalName();

            // Structured path: logistics/trade-documents/{booking_id}/{document_type}/{date}
            $bookingIdFolder = Str::slug($booking->booking_id ?? $booking->booking_number);
            $docTypeFolder = Str::slug($request->document_type);
            $dateFolder = now()->format('Y-m-d');

            $destinationPath = "logistics/trade-documents/{$bookingIdFolder}/{$docTypeFolder}/{$dateFolder}";
            $filePath = $file->store($destinationPath, 'public');

            // --- Sync with Central Document Manager ---
            // 1. Booking Folder
            $folderBooking = LogisticFolder::firstOrCreate([
                'name' => $booking->booking_id ?? $booking->booking_number,
                'parent_id' => null,
            ]);

            // 2. Document Type Folder
            $folderType = LogisticFolder::firstOrCreate([
                'name' => $request->document_type,
                'parent_id' => $folderBooking->id,
            ]);

            // 3. Date Folder
            $folderDate = LogisticFolder::firstOrCreate([
                'name' => $dateFolder,
                'parent_id' => $folderType->id,
            ]);

            // 4. Create Logistic Document
            LogisticDocument::create([
                'name' => $originalFilename,
                'path' => $filePath,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'folder_id' => $folderDate->id,
            ]);
            // ------------------------------------------
        }

        $doc = LogisticsTradeDocument::create([
            'booking_id' => $booking->id,
            'document_type' => $request->document_type,
            'description' => $request->description,
            'required_for' => $request->required_for,
            'status' => $request->status,
            'file_path' => $filePath,
            'original_filename' => $originalFilename,
            'company_id' => auth()->check() ? (int) auth()->user()->company_id ?: null : null,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Document uploaded successfully.', 'document' => $doc]);
        }

        return redirect()->back()->with('success', 'Document uploaded successfully.');
    }

    public function updateDocument(Request $request, $docId)
    {
        $doc = LogisticsTradeDocument::findOrFail($docId);

        $request->validate([
            'document_type' => 'required|string',
            'status' => 'required|string',
            'file' => 'nullable|file|max:10240',
        ]);

        $doc->document_type = $request->document_type;
        $doc->description = $request->description;
        $doc->required_for = $request->required_for;
        $doc->status = $request->status;

        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($doc->file_path) {
                Storage::disk('public')->delete($doc->file_path);
            }
            $file = $request->file('file');
            $doc->file_path = $file->store('logistics/trade-documents', 'public');
            $doc->original_filename = $file->getClientOriginalName();
        }

        $doc->save();

        return redirect()->back()->with('success', 'Document updated successfully.');
    }

    public function uploadStageFile(Request $request, $stageId)
    {
        $stage = LogisticsBookingV2Stage::findOrFail($stageId);

        $request->validate([
            'file' => 'nullable|file|max:10240',
            'status' => 'nullable|string',
        ]);

        if ($request->filled('status')) {
            $stage->status = $request->status;
        }

        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($stage->file_path) {
                Storage::disk('public')->delete($stage->file_path);
            }
            $file = $request->file('file');
            $stage->file_path = $file->store('logistics/booking-stages', 'public');
            $stage->original_filename = $file->getClientOriginalName();
        } elseif ($request->boolean('remove_file')) {
            if ($stage->file_path) {
                Storage::disk('public')->delete($stage->file_path);
            }
            $stage->file_path = null;
            $stage->original_filename = null;
        }

        $stage->save();

        return response()->json(['success' => true, 'message' => 'Stage file updated successfully.']);
    }

    public function destroyDocument($id)
    {
        $doc = LogisticsTradeDocument::findOrFail($id);
        if ($doc->file_path) {
            Storage::disk('public')->delete($doc->file_path);
        }
        $doc->delete();

        return response()->json(['success' => true]);
    }

    private function saveTradeDocuments(LogisticsBookingV2 $booking, Request $request): void
    {
        if (! $request->has('documents')) {
            return;
        }

        foreach ($request->documents as $i => $docData) {
            if (empty($docData['document_type'])) {
                continue;
            }

            $filePath = null;
            $originalFilename = null;

            if ($request->hasFile("documents.{$i}.file")) {
                $file = $request->file("documents.{$i}.file");
                $originalFilename = $file->getClientOriginalName();

                // Structured path: logistics/trade-documents/{booking_id}/{document_type}/{date}
                $bookingIdFolder = Str::slug($booking->booking_id ?? $booking->booking_number);
                $docTypeFolder = Str::slug($docData['document_type']);
                $dateFolder = now()->format('Y-m-d');

                $destinationPath = "logistics/trade-documents/{$bookingIdFolder}/{$docTypeFolder}/{$dateFolder}";
                $filePath = $file->store($destinationPath, 'public');

                // --- Sync with Central Document Manager ---
                $folderBooking = LogisticFolder::firstOrCreate([
                    'name' => $booking->booking_id ?? $booking->booking_number,
                    'parent_id' => null,
                ]);

                $folderType = LogisticFolder::firstOrCreate([
                    'name' => $docData['document_type'],
                    'parent_id' => $folderBooking->id,
                ]);

                $folderDate = LogisticFolder::firstOrCreate([
                    'name' => $dateFolder,
                    'parent_id' => $folderType->id,
                ]);

                LogisticDocument::create([
                    'name' => $originalFilename,
                    'path' => $filePath,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'folder_id' => $folderDate->id,
                ]);
                // ------------------------------------------
            }

            LogisticsTradeDocument::create([
                'booking_id' => $booking->id,
                'document_type' => $docData['document_type'],
                'description' => $docData['description'] ?? null,
                'required_for' => $docData['required_for'] ?? null,
                'status' => $docData['status'] ?? 'Pending',
                'file_path' => $filePath,
                'original_filename' => $originalFilename,
                'company_id' => auth()->check() ? (int) auth()->user()->company_id ?: null : null,
            ]);
        }
    }

    private function saveChildTradeDocuments(LogisticsBookingV2 $booking, Request $request, string $childBookingNumber): void
    {
        if (! $request->has('documents')) {
            return;
        }

        foreach ($request->documents as $i => $docData) {
            if (empty($docData['document_type'])) {
                continue;
            }

            $filePath = null;
            $originalFilename = null;

            if ($request->hasFile("documents.{$i}.file")) {
                $file = $request->file("documents.{$i}.file");
                $originalFilename = $file->getClientOriginalName();
                $bookingIdFolder = Str::slug($booking->booking_id ?? $booking->booking_number);
                $docTypeFolder = Str::slug($docData['document_type']);
                $dateFolder = now()->format('Y-m-d');
                $destinationPath = "logistics/trade-documents/{$bookingIdFolder}/{$docTypeFolder}/{$dateFolder}";
                $filePath = $file->store($destinationPath, 'public');
            }

            LogisticsTradeDocument::create([
                'booking_id' => $booking->id,
                'child_booking_number' => $childBookingNumber,
                'document_type' => $docData['document_type'],
                'description' => $docData['description'] ?? null,
                'required_for' => $docData['required_for'] ?? null,
                'status' => $docData['status'] ?? 'Pending',
                'file_path' => $filePath,
                'original_filename' => $originalFilename,
                'company_id' => auth()->check() ? (int) auth()->user()->company_id ?: null : null,
            ]);
        }
    }

    private function findChildBookingOrFail(LogisticsBookingV2 $booking, string $childBookingNumber): array
    {
        $inlandDetails = is_array($booking->inland_details) ? $booking->inland_details : [];
        $childBooking = $inlandDetails[$childBookingNumber] ?? null;

        abort_unless(is_array($childBooking), 404, 'Child booking not found.');

        return $childBooking;
    }

    private function buildChildBookingPayload(
        Request $request,
        LogisticsBookingV2 $parentBooking,
        array $existingChild = [],
        ?string $bookingNumber = null
    ): array {
        $containerNumbers = $this->normalizeChildContainerNumbers($request);

        return [
            'booking_identity' => [
                'parent_id' => $parentBooking->id,
                'parent_booking_number' => $parentBooking->booking_number,
                'booking_number' => $bookingNumber ?? $request->booking_number,
                'freight_mode' => $request->freight_mode,
                'container_no' => $containerNumbers[0] ?? null,
                'container_nos' => $containerNumbers,
                'do_number' => $request->do_number,
                'customs_channel' => $request->customs_channel,
                'status' => data_get($existingChild, 'booking_identity.status', 'Booked'),
                'arrival_confirmed_at' => data_get($existingChild, 'booking_identity.arrival_confirmed_at'),
                'delivery_confirmed_at' => data_get($existingChild, 'booking_identity.delivery_confirmed_at'),
            ],
            'inland_carrier' => [
                'carrier' => $request->carrier,
                'carrier_contact' => $request->carrier_contact,
                'carrier_contract' => $request->carrier_contract,
                'etd' => $request->actual_etd,
                'eta' => $request->intransit_point_eta,
                'delivery_date' => $request->delivery_date,
                'return_deadline' => $request->return_deadline,
                'pickup_point' => $request->pickup_point,
                'transit_stop' => $request->transit_stop,
                'final_destination' => $request->final_destination,
                'freight_rate_pkr' => $request->freight_rate_pkr,
                'oversize_permit' => $request->oversize_permit,
            ],
            'vehicle_driver_assignment' => [
                'truck_type' => $request->truck_type,
                'vehicle_reg' => $request->vehicle_reg,
                'vehicle_model' => $request->vehicle_model,
                'truck_capacity' => $request->truck_capacity,
                'driver_cnic' => $request->driver_cnic,
                'driver_name' => $request->driver_name,
                'driver_mobile' => $request->driver_mobile,
                'co_driver' => $request->co_driver,
                'gps_unit_id' => $request->gps_unit_id,
                'gps_portal' => $request->gps_portal,
                'vehicle_inspection' => $request->vehicle_inspection,
                'container_return_type' => $request->container_return_type,
            ],
            'route_plan_waypoints' => [
                'route_option' => $request->route_option,
                'total_distance' => $request->total_distance,
                'est_transit_time' => $request->est_transit_time,
            ],
            'final_delivery_details' => [
                'delivery_address' => $request->delivery_address,
                'receiving_contact' => $request->receiving_contact,
                'delivery_window' => $request->delivery_window,
                'unloading_method' => $request->unloading_method,
                'delivery_receipt_type' => $request->delivery_receipt_type,
                'post_delivery_inspection' => $request->post_delivery_inspection,
                'delivery_instructions' => $request->delivery_instructions,
            ],
        ];
    }

    private function normalizeChildContainerNumbers(Request $request): array
    {
        $containerNumbers = $request->input('container_nos');

        if (! is_array($containerNumbers)) {
            $containerNumbers = [$request->input('container_no')];
        }

        return collect($containerNumbers)
            ->map(fn ($containerNumber) => trim((string) $containerNumber))
            ->filter(fn (string $containerNumber) => $containerNumber !== '')
            ->unique()
            ->take(100)
            ->values()
            ->all();
    }

    public function approvePickup(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $booking = LogisticsBookingV2::findOrFail($id);
        $today = Carbon::today();

        $originalDate = $booking->estimated_time_departure;
        $booking->update([
            'status' => 'In-Transit',
        ]);

        LogisticsBookingStatusHistory::create([
            'booking_id' => $booking->id,
            'event_type' => 'pickup_approved',
            'original_date' => $originalDate,
            'new_date' => $today,
            'status_changed_to' => 'In-Transit',
            'reason' => $request->reason,
            'action_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Pickup approved.']);
    }

    // ── Pickup: Reject / Reschedule ─────────────────────────────
    public function rejectPickup(Request $request, $id)
    {
        $request->validate([
            'new_pickup_date' => 'required|date|after_or_equal:today',
            'reason' => 'required|string|max:500',
        ]);

        $booking = LogisticsBookingV2::findOrFail($id);
        $originalDate = $booking->estimated_time_departure;
        $newDate = Carbon::parse($request->new_pickup_date);
        $today = Carbon::today();

        // Determine new status based on new date
        if ($newDate->isToday()) {
            $newStatus = 'Pickup';
        } elseif ($newDate->greaterThan($today)) {
            $newStatus = 'Booked';
        } else {
            $newStatus = 'Booked';
        }

        $booking->update([
            'estimated_time_departure' => $newDate,
            'status' => $newStatus,
        ]);

        LogisticsBookingStatusHistory::create([
            'booking_id' => $booking->id,
            'event_type' => 'pickup_rejected',
            'original_date' => $originalDate,
            'new_date' => $newDate,
            'status_changed_to' => $newStatus,
            'reason' => $request->reason,
            'action_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Pickup rescheduled.', 'new_status' => $newStatus]);
    }

    // ── Auto-transition: Booked → In-Transit (run via scheduler or manually) ──
    private function autoTransitionStatuses(): void
    {
        $today = Carbon::today()->toDateString();

        // 1. Booked with pickup_date or etd = today → Pickup
        LogisticsBookingV2::where('status', 'Booked')
            ->where(function ($q) use ($today) {
                $q->whereDate('pickup_date', $today)
                    ->orWhereDate('etd', $today);
            })
            ->update(['status' => 'Pickup']);

        // 2. Pickup with date < today -> In-Transit
        LogisticsBookingV2::where('status', 'Pickup')
            ->where(function ($q) use ($today) {
                $q->whereDate('pickup_date', '<', $today)
                    ->orWhereDate('etd', '<', $today);
            })
            ->update(['status' => 'In-Transit']);

        // 3. Booked with date < today (missed pickup) -> In-Transit (assume it moved)
        LogisticsBookingV2::where('status', 'Booked')
            ->where(function ($q) use ($today) {
                $q->whereDate('pickup_date', '<', $today)
                    ->orWhereDate('etd', '<', $today);
            })
            ->update(['status' => 'In-Transit']);
    }

    // ── Delivery: Approve ───────────────────────────────────────
    public function approveDelivery(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $booking = LogisticsBookingV2::findOrFail($id);
        $today = Carbon::today();
        $origDate = $booking->estimated_time_delivery;

        $booking->update([
            'status' => 'Delivered',
        ]);
        LogisticsBookingStatusHistory::create([
            'booking_id' => $booking->id,
            'event_type' => 'delivery_approved',
            'original_date' => $origDate,
            'new_date' => $today,
            'status_changed_to' => 'Delivered',
            'reason' => $request->reason,
            'action_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Delivery confirmed.']);
    }

    // ── Delivery: Reject / Reschedule ──────────────────────────
    public function rejectDelivery(Request $request, $id)
    {
        $request->validate([
            'new_delivery_date' => 'required|date|after_or_equal:today',
            'reason' => 'required|string|max:500',
        ]);

        $booking = LogisticsBookingV2::findOrFail($id);
        $today = Carbon::today();
        $origDate = $booking->estimated_time_delivery;
        $newDate = Carbon::parse($request->new_delivery_date);

        $booking->update([
            'estimated_time_delivery' => $newDate,
            'status' => 'In-Transit',
        ]);

        LogisticsBookingStatusHistory::create([
            'booking_id' => $booking->id,
            'event_type' => 'delivery_rejected',
            'original_date' => $origDate,
            'new_date' => $newDate,
            'status_changed_to' => 'In-Transit',
            'reason' => $request->reason,
            'action_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Delivery rescheduled.']);
    }

    // ── Pending Delivery check data ─────────────────────────────
    public function approveChildArrival(Request $request, $id)
    {
        $request->validate([
            'child_booking_number' => 'required|string',
            'reason' => 'required|string|max:500',
        ]);

        $booking = LogisticsBookingV2::findOrFail($id);
        $this->applyChildPendingAction($booking, $request->child_booking_number, 'child_arrival', 'approve', $request->reason);

        return response()->json(['success' => true, 'message' => 'Child arrival confirmed.']);
    }

    public function rejectChildArrival(Request $request, $id)
    {
        $request->validate([
            'child_booking_number' => 'required|string',
            'new_arrival_date' => 'required|date|after_or_equal:today',
            'reason' => 'required|string|max:500',
        ]);

        $booking = LogisticsBookingV2::findOrFail($id);
        $this->applyChildPendingAction(
            $booking,
            $request->child_booking_number,
            'child_arrival',
            'reject',
            $request->reason,
            Carbon::parse($request->new_arrival_date)
        );

        return response()->json(['success' => true, 'message' => 'Child arrival rescheduled.']);
    }

    public function approveChildDelivery(Request $request, $id)
    {
        $request->validate([
            'child_booking_number' => 'required|string',
            'reason' => 'required|string|max:500',
        ]);

        $booking = LogisticsBookingV2::findOrFail($id);
        $this->applyChildPendingAction($booking, $request->child_booking_number, 'child_delivery', 'approve', $request->reason);

        return response()->json(['success' => true, 'message' => 'Child delivery confirmed.']);
    }

    public function rejectChildDelivery(Request $request, $id)
    {
        $request->validate([
            'child_booking_number' => 'required|string',
            'new_delivery_date' => 'required|date|after_or_equal:today',
            'reason' => 'required|string|max:500',
        ]);

        $booking = LogisticsBookingV2::findOrFail($id);
        $this->applyChildPendingAction(
            $booking,
            $request->child_booking_number,
            'child_delivery',
            'reject',
            $request->reason,
            Carbon::parse($request->new_delivery_date)
        );

        return response()->json(['success' => true, 'message' => 'Child delivery rescheduled.']);
    }

    public function pendingActions(Request $request)
    {
        $today = Carbon::today()->toDateString();

        $pickupDue = LogisticsBookingV2::where('status', 'Booked')->whereDate('estimated_time_departure', '<=', $today)->get();
        $deliveryDue = LogisticsBookingV2::where('status', 'In-Transit')->whereDate('estimated_time_delivery', '<=', $today)->get();
        $childPending = $this->buildChildPendingActions($request->filled('parent_id') ? (int) $request->parent_id : null);

        return response()->json([
            'pickup_due' => $pickupDue,
            'delivery_due' => $deliveryDue,
            'child_arrival_due' => $childPending['child_arrival_due'],
            'child_delivery_due' => $childPending['child_delivery_due'],
        ]);
    }

    public function importBookings(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
        ]);

        try {
            Excel::import(new BookingsImport, $request->file('file'));

            return redirect()->back()->with('success', 'Bookings imported successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing bookings: '.$e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(new BookingsTemplateExport, 'bookings_template.xlsx');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action_type' => 'required|in:approve,reject',
            'reason' => 'required|string|max:500',
            'new_date' => 'required_if:action_type,reject|date|after_or_equal:today',
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.type' => 'required|in:pickup,delivery,child_arrival,child_delivery',
            'items.*.child_booking_number' => 'required_if:items.*.type,child_arrival,child_delivery|nullable|string',
        ]);

        $actionType = $request->action_type;
        $reason = $request->reason;
        $newDate = $request->filled('new_date') ? Carbon::parse($request->new_date) : null;
        $today = Carbon::today();

        DB::transaction(function () use ($request, $actionType, $reason, $newDate, $today) {
            foreach ($request->items as $item) {
                $booking = LogisticsBookingV2::find($item['id']);
                if (! $booking) {
                    continue;
                }

                if (in_array($item['type'], ['child_arrival', 'child_delivery'])) {
                    $this->applyChildPendingAction(
                        $booking,
                        $item['child_booking_number'],
                        $item['type'],
                        $actionType,
                        $reason,
                        $actionType === 'reject' ? $newDate : null
                    );

                    continue;
                }

                if ($item['type'] === 'pickup') {
                    $originalDate = $booking->pickup_date ? $booking->pickup_date->copy() : $today;
                    if ($actionType === 'approve') {
                        $booking->update([
                            'original_pickup_date' => $originalDate,
                            'pickup_date' => $today,
                            'etd' => $today,
                            'pickup_status' => 'approved',
                            'pickup_reason' => $reason,
                            'status' => 'In-Transit',
                        ]);
                        LogisticsBookingStatusHistory::create([
                            'booking_v2_id' => $booking->id,
                            'event_type' => 'pickup_approved',
                            'original_date' => $originalDate,
                            'new_date' => $today,
                            'status_changed_to' => 'In-Transit',
                            'reason' => $reason,
                            'action_by' => auth()->id(),
                        ]);
                    } else { // reject (reschedule)
                        $newStatus = ($newDate->isToday()) ? 'Pickup' : 'Booked';
                        $booking->update([
                            'original_pickup_date' => $originalDate,
                            'pickup_date' => $newDate,
                            'etd' => $newDate,
                            'pickup_status' => 'rescheduled',
                            'pickup_reason' => $reason,
                            'status' => $newStatus,
                        ]);
                        LogisticsBookingStatusHistory::create([
                            'booking_v2_id' => $booking->id,
                            'event_type' => 'pickup_rejected',
                            'original_date' => $originalDate,
                            'new_date' => $newDate,
                            'status_changed_to' => $newStatus,
                            'reason' => $reason,
                            'action_by' => auth()->id(),
                        ]);
                    }
                } else { // delivery
                    $origDate = $booking->delivery_date ? $booking->delivery_date->copy() : ($booking->eta ? Carbon::parse($booking->eta) : $today);
                    if ($actionType === 'approve') {
                        $booking->update([
                            'original_delivery_date' => $origDate,
                            'delivery_date' => $today,
                            'eta' => $today,
                            'delivery_status' => 'approved',
                            'delivery_reason' => $reason,
                            'status' => 'Delivered',
                        ]);
                        LogisticsBookingStatusHistory::create([
                            'booking_v2_id' => $booking->id,
                            'event_type' => 'delivery_approved',
                            'original_date' => $origDate,
                            'new_date' => $today,
                            'status_changed_to' => 'Delivered',
                            'reason' => $reason,
                            'action_by' => auth()->id(),
                        ]);
                    } else { // reject
                        $booking->update([
                            'original_delivery_date' => $origDate,
                            'delivery_date' => $newDate,
                            'eta' => $newDate,
                            'delivery_status' => 'rescheduled',
                            'delivery_reason' => $reason,
                            'status' => 'In-Transit',
                        ]);
                        LogisticsBookingStatusHistory::create([
                            'booking_v2_id' => $booking->id,
                            'event_type' => 'delivery_rejected',
                            'original_date' => $origDate,
                            'new_date' => $newDate,
                            'status_changed_to' => 'In-Transit',
                            'reason' => $reason,
                            'action_by' => auth()->id(),
                        ]);
                    }
                }
            }
        });

        return response()->json(['success' => true, 'message' => 'Bulk action processed successfully.']);
    }

    private function buildChildPendingActions(?int $parentId = null): array
    {
        $today = Carbon::today();
        $query = LogisticsBookingV2::query();

        if ($parentId) {
            $query->whereKey($parentId);
        } else {
            $query->whereNotNull('inland_details');
        }

        $childArrivalDue = [];
        $childDeliveryDue = [];

        foreach ($query->get() as $booking) {
            $inlandDetails = is_array($booking->inland_details) ? $booking->inland_details : [];

            foreach ($inlandDetails as $childBookingNumber => $child) {
                if (! is_array($child)) {
                    continue;
                }

                $status = $this->resolveChildBookingStatus($child);
                $eta = data_get($child, 'inland_carrier.eta');
                $deliveryDate = data_get($child, 'inland_carrier.delivery_date');

                if ($deliveryDate && Carbon::parse($deliveryDate)->startOfDay()->lte($today) && $status !== 'Delivered') {
                    $childDeliveryDue[] = $this->formatChildPendingAction($booking, $childBookingNumber, $child, $status);

                    continue;
                }

                if ($eta && Carbon::parse($eta)->startOfDay()->lte($today) && ! in_array($status, ['In-Transit', 'Delivered'])) {
                    $childArrivalDue[] = $this->formatChildPendingAction($booking, $childBookingNumber, $child, $status);
                }
            }
        }

        return [
            'child_arrival_due' => $childArrivalDue,
            'child_delivery_due' => $childDeliveryDue,
        ];
    }

    private function formatChildPendingAction(LogisticsBookingV2 $booking, string $childBookingNumber, array $child, string $status): array
    {
        return [
            'id' => $booking->id,
            'parent_id' => $booking->id,
            'parent_booking_number' => $booking->booking_number,
            'child_booking_number' => $childBookingNumber,
            'status' => $status,
            'freight_mode' => data_get($child, 'booking_identity.freight_mode'),
            'shipper' => $booking->shipper,
            'consignee' => $booking->consignee,
            'origin_port' => data_get($child, 'inland_carrier.pickup_point') ?: $booking->dest_port ?: $booking->origin_port,
            'destination_port' => data_get($child, 'final_delivery_details.delivery_address')
                ?: data_get($child, 'inland_carrier.final_destination')
                ?: $booking->final_delivery_place
                ?: $booking->dest_port,
            'eta' => data_get($child, 'inland_carrier.eta'),
            'delivery_date' => data_get($child, 'inland_carrier.delivery_date'),
        ];
    }

    private function resolveChildBookingStatus(array $child): string
    {
        return (string) (data_get($child, 'booking_identity.status') ?: 'Booked');
    }

    private function applyChildPendingAction(
        LogisticsBookingV2 $booking,
        string $childBookingNumber,
        string $type,
        string $actionType,
        string $reason,
        ?Carbon $newDate = null
    ): void {
        $inlandDetails = is_array($booking->inland_details) ? $booking->inland_details : [];

        if (! isset($inlandDetails[$childBookingNumber]) || ! is_array($inlandDetails[$childBookingNumber])) {
            abort(404, 'Child booking not found.');
        }

        $child = $inlandDetails[$childBookingNumber];
        $path = $type === 'child_arrival' ? 'inland_carrier.eta' : 'inland_carrier.delivery_date';
        $originalDate = data_get($child, $path);

        if ($actionType === 'approve') {
            $nextStatus = $type === 'child_arrival' ? 'In-Transit' : 'Delivered';
            data_set($child, 'booking_identity.status', $nextStatus);
            data_set(
                $child,
                $type === 'child_arrival' ? 'booking_identity.arrival_confirmed_at' : 'booking_identity.delivery_confirmed_at',
                now()->toDateTimeString()
            );
        } else {
            $rescheduled = $this->mergeDateWithOriginalTime($newDate, $originalDate);
            $nextStatus = $type === 'child_arrival' ? 'Booked' : 'In-Transit';

            data_set($child, $path, $rescheduled->format('Y-m-d H:i:s'));
            data_set($child, 'booking_identity.status', $nextStatus);
            data_set($child, 'booking_identity.arrival_confirmed_at', null);
            data_set($child, 'booking_identity.delivery_confirmed_at', null);
        }

        $inlandDetails[$childBookingNumber] = $child;
        $booking->update(['inland_details' => $inlandDetails]);

        LogisticsBookingStatusHistory::create([
            'booking_id' => $booking->id,
            'event_type' => $type.'_'.($actionType === 'approve' ? 'approved' : 'rejected'),
            'original_date' => $originalDate ? Carbon::parse($originalDate) : null,
            'new_date' => $actionType === 'approve'
                ? Carbon::today()
                : $this->mergeDateWithOriginalTime($newDate, $originalDate),
            'status_changed_to' => $nextStatus,
            'reason' => 'Child '.$childBookingNumber.': '.$reason,
            'action_by' => auth()->id(),
        ]);
    }

    private function mergeDateWithOriginalTime(?Carbon $date, $originalValue): Carbon
    {
        $date = $date ? $date->copy() : Carbon::today();

        if (! $originalValue) {
            return $date->startOfDay();
        }

        try {
            $original = Carbon::parse($originalValue);

            return $date->copy()->setTime($original->hour, $original->minute, $original->second);
        } catch (\Throwable $e) {
            return $date->startOfDay();
        }
    }

    public function updateLegStatus(Request $request, $legId)
    {
        $request->validate([
            'status' => 'required|string|in:Pending,Pickup,In-Transit,Delivered',
        ]);

        $leg = LogisticsBookingLeg::findOrFail($legId);
        $oldStatus = $leg->status;
        $leg->status = $request->status;
        $leg->save();

        // Optional: Log status history if needed
        LogisticsBookingStatusHistory::create([
            'booking_v2_id' => $leg->booking_v2_id,
            'event_type' => 'leg_status_updated',
            'status_changed_from' => $oldStatus,
            'status_changed_to' => $leg->status,
            'reason' => 'Routing leg ('.$leg->origin.' -> '.$leg->destination.') status updated to '.$leg->status,
            'action_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leg status updated successfully.',
            'new_status' => $leg->status,
        ]);
    }

    public function approveLegTiming(Request $request, $legId)
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $leg = LogisticsBookingLeg::findOrFail($legId);
        $today = Carbon::today();

        $leg->update([
            'original_eta' => $leg->eta,
            'original_etd' => $leg->etd,
            'timing_status' => 'approved',
            'timing_reason' => $request->reason,
            'status' => $request->status ?? $leg->status,
        ]);

        LogisticsBookingStatusHistory::create([
            'booking_v2_id' => $leg->booking_v2_id,
            'event_type' => 'leg_timing_approved',
            'reason' => "Timing approved for leg ({$leg->origin} -> {$leg->destination}): ".$request->reason,
            'action_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Leg timing approved.']);
    }

    public function rejectLegTiming(Request $request, $legId)
    {
        $request->validate([
            'new_date' => 'required|date|after_or_equal:today',
            'reason' => 'required|string|max:500',
            'date_type' => 'required|in:eta,etd,estimated_delivery_date',
        ]);

        $leg = LogisticsBookingLeg::findOrFail($legId);
        $field = $request->date_type;
        $oldDate = $leg->$field;
        $newDate = Carbon::parse($request->new_date);

        $updateData = [
            'timing_status' => 'rescheduled',
            'timing_reason' => $request->reason,
        ];

        if ($field === 'eta') {
            $updateData['original_eta'] = $oldDate;
        }
        if ($field === 'etd') {
            $updateData['original_etd'] = $oldDate;
        }

        $updateData[$field] = $newDate;
        if ($request->has('status')) {
            $updateData['status'] = $request->status;
        }

        $leg->update($updateData);

        LogisticsBookingStatusHistory::create([
            'booking_v2_id' => $leg->booking_v2_id,
            'event_type' => 'leg_timing_rejected',
            'original_date' => $oldDate,
            'new_date' => $newDate,
            'reason' => "Timing ({$field}) rescheduled for leg ({$leg->origin} -> {$leg->destination}): ".$request->reason,
            'action_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Leg timing rescheduled.']);
    }

    private function getFreightDetails(Request $request)
    {
        $mode = $request->freight_mode;

        if ($request->has('is_child')) {
            return [
                'inland_details' => [
                    'gd_number' => $request->gd_number,
                    'do_number' => $request->do_number,
                    'customs_channel' => $request->customs_channel,
                    'carrier_contact' => $request->carrier_contact,
                    'carrier_contract' => $request->carrier_contract,
                    'return_deadline' => $request->return_deadline,
                    'freight_rate_pkr' => $request->freight_rate_pkr,
                    'transit_stop' => $request->transit_stop,
                    'oversize_permit' => $request->oversize_permit,
                    'vehicle_reg' => $request->vehicle_reg,
                    'vehicle_model' => $request->vehicle_model,
                    'truck_capacity' => $request->truck_capacity,
                    'driver_name' => $request->driver_name,
                    'driver_cnic' => $request->driver_cnic,
                    'driver_mobile' => $request->driver_mobile,
                    'co_driver' => $request->co_driver,
                    'gps_unit_id' => $request->gps_unit_id,
                    'gps_portal' => $request->gps_portal,
                    'vehicle_inspection' => $request->vehicle_inspection,
                    'container_return_type' => $request->container_return_type,
                    'route_option' => $request->route_option,
                    'total_distance' => $request->total_distance,
                    'est_transit_time' => $request->est_transit_time,
                    'delivery_address' => $request->delivery_address,
                    'receiving_contact' => $request->receiving_contact,
                    'delivery_window' => $request->delivery_window,
                    'unloading_method' => $request->unloading_method,
                    'delivery_receipt_type' => $request->delivery_receipt_type,
                    'post_delivery_inspection' => $request->post_delivery_inspection,
                    'child_sub_mode' => $request->freight_mode, // E.g. FTL, LTL
                ],
                'customs' => [
                    'gd_number' => $request->gd_number,
                    'status' => $request->customs_channel,
                ],
                'container' => [
                    'return_deadline' => $request->return_deadline,
                ],
            ];
        }

        if ($mode === 'fcl_sea') {
            return $request->fcl_details;
        }
        if ($mode === 'lcl_sea') {
            return $request->lcl_details;
        }
        if ($mode === 'air') {
            return $request->air_details;
        }

        return null;
    }

    private function syncRoutingLegs(LogisticsBookingV2 $booking, $legs)
    {
        if (empty($legs) || ! is_array($legs)) {
            return;
        }

        $booking->legs()->delete();

        foreach ($legs as $index => $leg) {
            if (empty($leg['origin']) || empty($leg['destination'])) {
                continue;
            }

            $booking->legs()->create([
                'origin' => $leg['origin'],
                'destination' => $leg['destination'],
                'etd' => ! empty($leg['etd']) ? $leg['etd'] : null,
                'eta' => ! empty($leg['eta']) ? $leg['eta'] : null,
                'estimated_delivery_date' => ! empty($leg['estimated_delivery_date']) ? $leg['estimated_delivery_date'] : null,
                'mode' => $leg['mode'] ?? 'sea',
                'sort_order' => $index,
            ]);
        }
    }

    public function getBookingDetails($id)
    {
        $booking = LogisticsBookingV2::findOrFail($id);

        return response()->json([
            'booking_number' => $booking->booking_number,
            'pickup_point' => $booking->final_delivery_place ?: $booking->destination_port ?: $booking->place_of_receipt ?: $booking->origin_port,
            'final_destination' => $booking->final_delivery_place ?: $booking->destination_port ?: $booking->dest_port,
        ]);
    }

    public function paginateHsCodes(Request $request)
    {
        $search = $request->query('q', '');
        $page = $request->query('page', 1);

        $query = HsCode::query();

        if ($search !== '') {
            $searchLower = strtolower($search);
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(hs_code) LIKE ?', ["%{$searchLower}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"]);
            });
        }

        $hsCodes = $query->orderBy('hs_code')
            ->paginate(20, ['*'], 'page', $page);

        return response()->json([
            'data' => $hsCodes->items(),
            'next_page' => $hsCodes->nextPageUrl() ? $page + 1 : null,
        ]);
    }

    public function searchFreightAgents(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'cursor' => ['nullable', 'string', 'max:500'],
        ]);

        $companyId = auth()->user()?->company_id;
        abort_if(empty($companyId), 403, 'A company is required to search freight agents.');

        $search = LogisticsFreightAgent::normalizeName($validated['q'] ?? '');
        if ($search !== '' && strlen($search) < 2) {
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false],
                'next_cursor' => null,
                'exact_match' => false,
            ]);
        }

        $cursor = $this->decodeFreightAgentCursor($validated['cursor'] ?? null);
        $defaultNames = collect(self::DEFAULT_FREIGHT_AGENTS)
            ->map(fn (array $agent) => LogisticsFreightAgent::normalizeName($agent['text']))
            ->all();

        $defaults = $cursor === null
            ? collect(self::DEFAULT_FREIGHT_AGENTS)
                ->filter(fn (array $agent) => str_starts_with(
                    LogisticsFreightAgent::normalizeName($agent['text']),
                    $search
                ))
                ->values()
            : collect();

        $pageSize = 50;
        $databaseSlots = $pageSize - $defaults->count();

        $agents = LogisticsFreightAgent::query()
            ->where('company_id', $companyId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name_search', '>=', $search)
                    ->where('name_search', '<', $this->freightAgentPrefixEnd($search));
            })
            ->whereNotIn('name_search', $defaultNames)
            ->when($cursor !== null, fn ($query) => $query->where('name_search', '>', $cursor))
            ->orderBy('name_search')
            ->limit($databaseSlots + 1)
            ->get(['id', 'name', 'name_search', 'email', 'contact']);

        $hasMore = $agents->count() > $databaseSlots;
        $visibleAgents = $agents->take($databaseSlots);

        $results = $defaults->concat(
            $visibleAgents->map(fn (LogisticsFreightAgent $agent) => [
                'id' => $agent->name,
                'text' => $agent->name,
                'agent_id' => $agent->id,
                'email' => $agent->email,
                'contact' => $agent->contact,
            ])
        )->values();

        $exactMatch = $search !== '' && $results->contains(
            fn (array $agent) => LogisticsFreightAgent::normalizeName($agent['text']) === $search
        );
        $lastAgent = $visibleAgents->last();

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => $hasMore],
            'next_cursor' => $hasMore && $lastAgent
                ? $this->encodeFreightAgentCursor($lastAgent->name_search)
                : null,
            'exact_match' => $exactMatch,
        ]);
    }

    public function storeFreightAgent(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'contact' => ['nullable', 'string', 'max:50'],
        ]);

        $companyId = auth()->user()?->company_id;
        abort_if(empty($companyId), 403, 'A company is required to create a freight agent.');

        $name = trim($validated['name']);
        $nameSearch = LogisticsFreightAgent::normalizeName($name);

        if ($nameSearch === '') {
            throw ValidationException::withMessages([
                'name' => 'The agent name must contain at least one letter or number.',
            ]);
        }

        $agent = LogisticsFreightAgent::query()
            ->where('company_id', $companyId)
            ->where('name_search', $nameSearch)
            ->first();
        $created = false;

        if (! $agent) {
            try {
                $agent = LogisticsFreightAgent::create([
                    'company_id' => $companyId,
                    'name' => $name,
                    'email' => $validated['email'] ?? null,
                    'contact' => $validated['contact'] ?? null,
                ]);
                $created = true;
            } catch (QueryException $exception) {
                // A concurrent request may have inserted the same normalized name.
                $agent = LogisticsFreightAgent::query()
                    ->where('company_id', $companyId)
                    ->where('name_search', $nameSearch)
                    ->first();

                if (! $agent) {
                    throw $exception;
                }
            }
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'message' => $created
                ? 'Freight forwarder / agent created successfully.'
                : 'This freight forwarder / agent already exists and has been selected.',
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'email' => $agent->email,
                'contact' => $agent->contact,
            ],
        ], $created ? 201 : 200);
    }

    private function freightAgentPrefixEnd(string $prefix): string
    {
        $lastIndex = strlen($prefix) - 1;
        $lastCharacter = ord($prefix[$lastIndex]);

        return substr($prefix, 0, $lastIndex).chr($lastCharacter + 1);
    }

    private function encodeFreightAgentCursor(string $nameSearch): string
    {
        return rtrim(strtr(base64_encode($nameSearch), '+/', '-_'), '=');
    }

    private function decodeFreightAgentCursor(?string $cursor): ?string
    {
        if (blank($cursor)) {
            return null;
        }

        $paddedCursor = str_pad(
            strtr($cursor, '-_', '+/'),
            (int) ceil(strlen($cursor) / 4) * 4,
            '=',
            STR_PAD_RIGHT
        );
        $decoded = base64_decode($paddedCursor, true);

        if ($decoded === false || LogisticsFreightAgent::normalizeName($decoded) !== $decoded) {
            return null;
        }

        return $decoded;
    }

    public function searchTransitStops(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'cursor' => ['nullable', 'string', 'max:500'],
        ]);

        $companyId = auth()->user()?->company_id;
        abort_if(empty($companyId), 403, 'A company is required to search transit stops.');

        $search = LogisticsTransitStop::normalizeName($validated['q'] ?? '');
        if ($search !== '' && strlen($search) < 2) {
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false],
                'next_cursor' => null,
                'exact_match' => false,
            ]);
        }

        $cursor = $this->decodeTransitStopCursor($validated['cursor'] ?? null);
        $defaultNames = collect(self::DEFAULT_TRANSIT_STOPS)
            ->map(fn (array $stop) => LogisticsTransitStop::normalizeName($stop['text']))
            ->all();

        $defaults = $cursor === null
            ? collect(self::DEFAULT_TRANSIT_STOPS)
                ->filter(fn (array $stop) => str_starts_with(
                    LogisticsTransitStop::normalizeName($stop['text']),
                    $search
                ))
                ->values()
            : collect();

        $pageSize = 50;
        $databaseSlots = $pageSize - $defaults->count();

        $stops = LogisticsTransitStop::query()
            ->where('company_id', $companyId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name_search', '>=', $search)
                    ->where('name_search', '<', $this->transitStopPrefixEnd($search));
            })
            ->whereNotIn('name_search', $defaultNames)
            ->when($cursor !== null, fn ($query) => $query->where('name_search', '>', $cursor))
            ->orderBy('name_search')
            ->limit($databaseSlots + 1)
            ->get(['id', 'name', 'name_search']);

        $hasMore = $stops->count() > $databaseSlots;
        $visibleStops = $stops->take($databaseSlots);
        $results = $defaults->concat(
            $visibleStops->map(fn (LogisticsTransitStop $stop) => [
                'id' => $stop->name,
                'text' => $stop->name,
                'transit_stop_id' => $stop->id,
            ])
        )->values();
        $exactMatch = $search !== '' && $results->contains(fn (array $stop) => (
            LogisticsTransitStop::normalizeName($stop['text']) === $search
            || LogisticsTransitStop::normalizeName((string) $stop['id']) === $search
        ));
        $lastStop = $visibleStops->last();

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => $hasMore],
            'next_cursor' => $hasMore && $lastStop
                ? $this->encodeTransitStopCursor($lastStop->name_search)
                : null,
            'exact_match' => $exactMatch,
        ]);
    }

    public function storeTransitStop(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $companyId = auth()->user()?->company_id;
        abort_if(empty($companyId), 403, 'A company is required to create a transit stop.');

        $name = trim($validated['name']);
        $nameSearch = LogisticsTransitStop::normalizeName($name);

        if ($nameSearch === '') {
            throw ValidationException::withMessages([
                'name' => 'The transit-stop name must contain at least one letter or number.',
            ]);
        }

        $transitStop = LogisticsTransitStop::query()
            ->where('company_id', $companyId)
            ->where('name_search', $nameSearch)
            ->first();
        $created = false;

        if (! $transitStop) {
            try {
                $transitStop = LogisticsTransitStop::create([
                    'company_id' => $companyId,
                    'name' => $name,
                ]);
                $created = true;
            } catch (QueryException $exception) {
                $transitStop = LogisticsTransitStop::query()
                    ->where('company_id', $companyId)
                    ->where('name_search', $nameSearch)
                    ->first();

                if (! $transitStop) {
                    throw $exception;
                }
            }
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'message' => $created
                ? 'Transit stop created successfully.'
                : 'This transit stop already exists and has been selected.',
            'transit_stop' => [
                'id' => $transitStop->id,
                'name' => $transitStop->name,
            ],
        ], $created ? 201 : 200);
    }

    private function transitStopPrefixEnd(string $prefix): string
    {
        $lastIndex = strlen($prefix) - 1;
        $lastCharacter = ord($prefix[$lastIndex]);

        return substr($prefix, 0, $lastIndex).chr($lastCharacter + 1);
    }

    private function encodeTransitStopCursor(string $nameSearch): string
    {
        return rtrim(strtr(base64_encode($nameSearch), '+/', '-_'), '=');
    }

    private function decodeTransitStopCursor(?string $cursor): ?string
    {
        if (blank($cursor)) {
            return null;
        }

        $paddedCursor = str_pad(
            strtr($cursor, '-_', '+/'),
            (int) ceil(strlen($cursor) / 4) * 4,
            '=',
            STR_PAD_RIGHT
        );
        $decoded = base64_decode($paddedCursor, true);

        if ($decoded === false || LogisticsTransitStop::normalizeName($decoded) !== $decoded) {
            return null;
        }

        return $decoded;
    }

    private function buildTrackingData(LogisticsBookingV2 $booking): array
    {
        $allDocuments = $booking->documents->sortBy('created_at')->values();
        $parentDocuments = $allDocuments->filter(function ($document) {
            return blank($document->child_booking_number);
        })->values();
        $parentGeneralDocuments = $parentDocuments->filter(function ($document) {
            return ! $this->isCustomsDocument($document);
        })->values();
        $parentCustomsDocuments = $parentDocuments->filter(function ($document) {
            return $this->isCustomsDocument($document);
        })->values();

        $childDocuments = $allDocuments->filter(function ($document) {
            return filled($document->child_booking_number);
        })->groupBy('child_booking_number');

        $segments = [];

        $segments[] = [
            'key' => 'parent',
            'type' => 'parent',
            'booking_number' => $booking->booking_number ?? 'Parent Booking',
            'title' => 'Parent Shipment',
            'route' => $this->formatRoute(
                $booking->place_of_receipt ?: $booking->origin_port,
                $booking->destination_port ?: $booking->final_delivery_place
            ),
            'origin_label' => $this->resolveLocation(
                [$booking->place_of_receipt, $booking->origin_port],
                'Origin'
            ),
            'destination_label' => $this->resolveLocation(
                [$booking->destination_port, $booking->final_delivery_place, $booking->origin_port],
                'Destination'
            ),
            'status' => $booking->status ?: 'Booked',
            'raw_dates' => [
                'ETD' => $booking->estimated_time_departure,
                'ETA' => $booking->eta,
                'Delivery' => $booking->estimated_time_delivery,
            ],
            'dates' => [
                'ETD' => $this->formatTrackingDate($booking->estimated_time_departure),
                'ETA' => $this->formatTrackingDate($booking->eta),
                'Delivery' => $this->formatTrackingDate($booking->estimated_time_delivery),
            ],
            'travel_complete' => $this->hasTrackingDateReached($booking->estimated_time_delivery)
                || $this->hasTrackingDateReached($booking->eta)
                || in_array($booking->status, ['Delivered']),
            'documents' => $parentDocuments,
            'document_summary' => $this->summarizeDocuments($parentDocuments),
        ];

        foreach ($booking->inland_details ?? [] as $childBookingNumber => $child) {
            $identity = $child['booking_identity'] ?? [];
            $carrier = $child['inland_carrier'] ?? [];
            $vehicle = $child['vehicle_driver_assignment'] ?? [];
            $finalDelivery = $child['final_delivery_details'] ?? [];

            $documents = ($childDocuments->get($childBookingNumber) ?? collect())->values();
            $destination = $finalDelivery['delivery_address']
                ?? $carrier['final_destination']
                ?? $carrier['transit_stop']
                ?? $carrier['pickup_point']
                ?? 'Child Destination';

            $segments[] = [
                'key' => 'child-'.$childBookingNumber,
                'type' => 'child',
                'booking_number' => $childBookingNumber,
                'title' => 'Child Leg',
                'route' => $this->formatRoute(
                    $carrier['pickup_point'] ?? $booking->destination_port,
                    $destination
                ),
                'origin_label' => $carrier['pickup_point']
                    ?? $booking->destination_port
                    ?? $booking->origin_port
                    ?? 'Pickup Point',
                'destination_label' => $destination,
                'status' => $carrier['delivery_date'] ? 'Delivered Leg' : 'Inland Processing',
                'raw_dates' => [
                    'ETD' => $carrier['etd'] ?? null,
                    'ETA' => $carrier['eta'] ?? null,
                    'Delivery' => $carrier['delivery_date'] ?? null,
                    'Return Deadline' => $carrier['return_deadline'] ?? null,
                ],
                'dates' => [
                    'ETD' => $this->formatTrackingDate($carrier['etd'] ?? null),
                    'ETA' => $this->formatTrackingDate($carrier['eta'] ?? null),
                    'Delivery' => $this->formatTrackingDate($carrier['delivery_date'] ?? null),
                    'Return Deadline' => $this->formatTrackingDate($carrier['return_deadline'] ?? null, false),
                ],
                'travel_complete' => $this->hasTrackingDateReached($carrier['delivery_date'] ?? null)
                    || $this->hasTrackingDateReached($carrier['eta'] ?? null),
                'meta' => [
                    'mode' => $identity['freight_mode'] ?? null,
                    'carrier' => $carrier['carrier'] ?? null,
                    'vehicle' => $vehicle['vehicle_reg'] ?? null,
                    'driver' => $vehicle['driver_name'] ?? null,
                ],
                'documents' => $documents,
                'document_summary' => $this->summarizeDocuments($documents),
            ];
        }

        $nextUnlocked = true;
        $completedCount = 0;
        $allDocumentGatesCleared = true;

        foreach ($segments as $index => $segment) {
            $segments[$index]['unlocked'] = $nextUnlocked;
            $segments[$index]['completed'] = $nextUnlocked && ($segment['travel_complete'] ?? false);

            $allDocumentGatesCleared = $allDocumentGatesCleared
                && $segment['document_summary']['all_received'];

            if ($segments[$index]['completed']) {
                $completedCount++;
            }

            $nextUnlocked = $nextUnlocked
                && $segment['document_summary']['all_received']
                && ($segment['travel_complete'] ?? false);
        }

        $progressSteps = $this->buildProgressSteps(
            $booking,
            $segments,
            $parentGeneralDocuments,
            $parentCustomsDocuments
        );
        $trackingEvents = $this->buildTrackingEvents(
            $booking,
            $segments,
            $parentGeneralDocuments,
            $parentCustomsDocuments
        );

        return [
            'segments' => $segments,
            'carrier_name' => $this->resolveCarrierName($booking),
            'path_steps' => $this->buildPathSteps($segments),
            'final_step' => [
                'title' => 'Final Delivery',
                'destination_label' => $this->resolveFinalTrackingDestination($booking),
                'unlocked' => $nextUnlocked,
                'completed' => $nextUnlocked && ! empty($segments),
            ],
            'completed_segments' => $completedCount,
            'total_segments' => count($segments),
            'all_documents_received' => $allDocumentGatesCleared,
            'progress_steps' => $progressSteps,
            'current_progress_index' => collect($progressSteps)->search(function ($step) {
                return $step['state'] === 'current';
            }),
            'events' => $trackingEvents,
        ];
    }

    private function buildPathSteps(array $segments): array
    {
        $steps = [];
        $currentAssigned = false;

        foreach ($segments as $segmentIndex => $segment) {
            if ($segmentIndex === 0) {
                $steps[] = [
                    'type' => 'origin',
                    'label' => $segment['origin_label'],
                    'meta' => 'Origin',
                    'state' => 'complete',
                ];
            } else {
                $bookingState = 'upcoming';

                if ($segment['unlocked']) {
                    if ($segment['completed']) {
                        $bookingState = 'complete';
                    } elseif (! $currentAssigned) {
                        $bookingState = 'current';
                        $currentAssigned = true;
                    }
                }

                $steps[] = [
                    'type' => 'booking',
                    'label' => $segment['booking_number'],
                    'meta' => $segment['origin_label'],
                    'state' => $bookingState,
                ];
            }

            foreach ($segment['documents'] as $document) {
                $isReceived = strcasecmp((string) $document->status, 'Received') === 0;
                $state = 'upcoming';

                if (! $segment['unlocked']) {
                    $state = 'upcoming';
                } elseif ($isReceived) {
                    $state = 'complete';
                } elseif (! $currentAssigned) {
                    $state = 'current';
                    $currentAssigned = true;
                }

                $steps[] = [
                    'type' => 'document',
                    'label' => $document->document_type,
                    'meta' => $document->status,
                    'state' => $state,
                ];

                if (! $segment['unlocked'] || ! $isReceived) {
                    continue;
                }
            }

            $destinationState = 'upcoming';

            if ($segment['unlocked']) {
                if ($segment['completed']) {
                    $destinationState = 'complete';
                } elseif (! $currentAssigned) {
                    $destinationState = 'current';
                    $currentAssigned = true;
                }
            }

            $steps[] = [
                'type' => 'destination',
                'label' => $segment['destination_label'],
                'meta' => $segment['type'] === 'parent' ? 'Destination' : 'Stop',
                'state' => $destinationState,
            ];
        }

        return $steps;
    }

    private function summarizeDocuments($documents): array
    {
        $documents = collect($documents)->values();
        $total = $documents->count();
        $received = $documents->filter(function ($document) {
            return strcasecmp((string) $document->status, 'Received') === 0;
        })->count();

        return [
            'total' => $total,
            'received' => $received,
            'all_received' => $total > 0 && $received === $total,
            'has_documents' => $total > 0,
        ];
    }

    private function resolveLocation(array $candidates, string $fallback = '—'): string
    {
        foreach ($candidates as $candidate) {
            if (filled($candidate)) {
                return $candidate;
            }
        }

        return $fallback;
    }

    private function formatRoute($from, $to): string
    {
        $origin = filled($from) ? $from : 'Start';
        $destination = filled($to) ? $to : 'Pending Destination';

        return $origin.' -> '.$destination;
    }

    private function formatTrackingDate($value, bool $withTime = true): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format($withTime ? 'M d, Y h:i A' : 'M d, Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function hasTrackingDateReached($value): bool
    {
        if (blank($value)) {
            return false;
        }

        try {
            return Carbon::parse($value)->startOfDay()->lte(now()->startOfDay());
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function resolveFinalTrackingDestination(LogisticsBookingV2 $booking): string
    {
        $childStages = $booking->inland_details ?? [];

        if (! empty($childStages)) {
            $lastStage = end($childStages);

            if (is_array($lastStage)) {
                return $this->resolveLocation([
                    data_get($lastStage, 'final_delivery_details.delivery_address'),
                    data_get($lastStage, 'inland_carrier.final_destination'),
                    data_get($lastStage, 'inland_carrier.transit_stop'),
                ], $booking->final_delivery_place ?: $booking->destination_port ?: 'Warehouse');
            }
        }

        return $this->resolveLocation([
            $booking->final_delivery_place,
            $booking->destination_port,
            $booking->origin_port,
        ], 'Warehouse');
    }

    private function isCustomsDocument($document): bool
    {
        $documentType = is_object($document) ? $document->document_type : data_get($document, 'document_type');
        $requiredFor = is_object($document) ? $document->required_for : data_get($document, 'required_for');
        $haystack = strtolower(trim(($documentType ?? '').' '.($requiredFor ?? '')));

        if ($haystack === '') {
            return false;
        }

        return Str::contains($haystack, [
            'custom',
            'gd',
            'goods declaration',
            'duty',
            'boe',
            'bill of entry',
            'shipping bill',
            'clearance',
            'assessment',
        ]);
    }

    private function resolveCarrierName(LogisticsBookingV2 $booking): string
    {
        return $booking->carrier?->carrier_company_name
            ?? $booking->getAttribute('carrier')
            ?? 'Carrier pending';
    }

    private function buildProgressSteps(
        LogisticsBookingV2 $booking,
        array $segments,
        $parentGeneralDocuments,
        $parentCustomsDocuments
    ): array {
        $generalSummary = $this->summarizeDocuments($parentGeneralDocuments);
        $customsSummary = $this->summarizeDocuments($parentCustomsDocuments);
        $lastSegment = ! empty($segments) ? end($segments) : null;

        $departedComplete = $this->hasTrackingDateReached($booking->estimated_time_departure)
            || in_array($booking->status, ['Pickup', 'In-Transit', 'Delivered']);

        $inTransitComplete = in_array($booking->status, ['In-Transit', 'Delivered'])
            || collect($segments)->slice(1)->contains(function ($segment) {
                return $this->hasTrackingDateReached(data_get($segment, 'raw_dates.ETD'))
                    || $this->hasTrackingDateReached(data_get($segment, 'raw_dates.ETA'));
            });

        $arrivedComplete = $this->hasTrackingDateReached($booking->estimated_time_delivery)
            || $this->hasTrackingDateReached(data_get($lastSegment, 'raw_dates.ETA'))
            || $this->hasTrackingDateReached(data_get($lastSegment, 'raw_dates.Delivery'))
            || in_array($booking->status, ['Delivered']);

        $deliveredComplete = in_array($booking->status, ['Delivered'])
            || $this->hasTrackingDateReached(data_get($lastSegment, 'raw_dates.Delivery'));

        return $this->decorateProgressSteps([
            [
                'label' => 'Booked',
                'complete' => true,
                'meta' => $booking->created_at ? $booking->created_at->format('M d, Y') : null,
            ],
            [
                'label' => 'Docs OK',
                'complete' => $generalSummary['has_documents'] && $generalSummary['all_received'],
                'meta' => $generalSummary['total'] > 0
                    ? $generalSummary['received'].'/'.$generalSummary['total'].' received'
                    : 'Upload booking docs',
            ],
            [
                'label' => 'Customs',
                'complete' => $customsSummary['has_documents'] && $customsSummary['all_received'],
                'meta' => $customsSummary['total'] > 0
                    ? $customsSummary['received'].'/'.$customsSummary['total'].' received'
                    : 'Upload customs docs',
            ],
            [
                'label' => 'Departed',
                'complete' => $departedComplete,
                'meta' => $this->formatTrackingDate($booking->estimated_time_departure, false),
            ],
            [
                'label' => 'In-Transit',
                'complete' => $inTransitComplete,
                'meta' => max(count($segments) - 1, 0).' child legs',
            ],
            [
                'label' => 'Arrived',
                'complete' => $arrivedComplete,
                'meta' => $this->formatTrackingDate($booking->estimated_time_delivery, false)
                    ?: data_get($lastSegment, 'dates.ETA'),
            ],
            [
                'label' => 'Delivered',
                'complete' => $deliveredComplete,
                'meta' => data_get($lastSegment, 'dates.Delivery'),
            ],
        ]);
    }

    private function decorateProgressSteps(array $steps): array
    {
        $currentAssigned = false;
        $flowBlocked = false;

        foreach ($steps as $index => $step) {
            $state = 'upcoming';

            if (! $flowBlocked && $step['complete']) {
                $state = 'complete';
            } elseif (! $currentAssigned) {
                $state = 'current';
                $currentAssigned = true;
                $flowBlocked = true;
            } else {
                $flowBlocked = true;
            }

            $steps[$index]['state'] = $state;
            $steps[$index]['number'] = $index + 1;
        }

        return $steps;
    }

    private function buildTrackingEvents(
        LogisticsBookingV2 $booking,
        array $segments,
        $parentGeneralDocuments,
        $parentCustomsDocuments
    ): array {
        $events = [];
        $carrierName = $this->resolveCarrierName($booking);

        $events[] = [
            'title' => 'Booking Confirmed',
            'description' => trim($carrierName.' · '.$this->formatRoute($booking->origin_port, $booking->destination_port)),
            'date' => $booking->created_at ? $booking->created_at->format('M d, Y · h:i A') : 'Pending',
            'state' => 'complete',
            'icon' => 'fa-calendar-check',
        ];

        foreach ($parentGeneralDocuments->sortByDesc('updated_at')->values() as $document) {
            $isReceived = strcasecmp((string) $document->status, 'Received') === 0;
            $events[] = [
                'title' => 'Doc Uploaded: '.$document->document_type,
                'description' => trim(($document->required_for ?: $document->description ?: 'Booking document').' · Status: '.$document->status),
                'date' => optional($document->updated_at ?: $document->created_at)->format('M d, Y · h:i A') ?: 'Pending',
                'state' => $isReceived ? 'complete' : 'current',
                'icon' => 'fa-file-circle-check',
            ];
        }

        foreach ($parentCustomsDocuments->sortByDesc('updated_at')->values() as $document) {
            $isReceived = strcasecmp((string) $document->status, 'Received') === 0;
            $events[] = [
                'title' => 'Customs Doc Uploaded: '.$document->document_type,
                'description' => trim(($document->required_for ?: $document->description ?: 'Customs document').' · Status: '.$document->status),
                'date' => optional($document->updated_at ?: $document->created_at)->format('M d, Y · h:i A') ?: 'Pending',
                'state' => $isReceived ? 'complete' : 'current',
                'icon' => 'fa-shield-halved',
            ];
        }

        if (filled($booking->estimated_time_departure)) {
            $departedReached = $this->hasTrackingDateReached($booking->estimated_time_departure)
                || in_array($booking->status, ['Pickup', 'In-Transit', 'Delivered']);

            $events[] = [
                'title' => 'Departed '.$this->resolveLocation([$booking->origin_port, $booking->place_of_receipt], 'Origin'),
                'description' => $carrierName.' · On-time departure flow',
                'date' => $this->formatTrackingDate($booking->estimated_time_departure),
                'state' => $departedReached ? 'complete' : 'upcoming',
                'icon' => 'fa-plane-departure',
            ];
        }

        foreach (collect($segments)->slice(1)->reverse()->values() as $segment) {
            $events[] = [
                'title' => $segment['booking_number'].' · '.$segment['destination_label'],
                'description' => trim(($segment['meta']['carrier'] ?? 'Carrier pending').' · '.$segment['route']),
                'date' => $segment['dates']['ETA'] ?: $segment['dates']['ETD'] ?: 'Pending',
                'state' => $segment['completed'] ? 'complete' : ($segment['unlocked'] ? 'current' : 'upcoming'),
                'icon' => 'fa-truck-fast',
            ];
        }

        if (in_array($booking->status, ['Delivered'])) {
            $events[] = [
                'title' => 'Delivered to '.$this->resolveFinalTrackingDestination($booking),
                'description' => 'Final handover completed',
                'date' => $this->formatTrackingDate($booking->estimated_time_delivery),
                'state' => 'complete',
                'icon' => 'fa-box-check',
            ];
        }

        return array_values(array_reverse($events));
    }
}
