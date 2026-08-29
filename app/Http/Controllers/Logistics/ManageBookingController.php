<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsBooking;
use App\Models\LogisticsBookingEvent;
use App\Models\LogisticsBookingStatusHistory;
use App\Models\LogisticsBookingV2;
use App\Models\LogisticsCarrier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManageBookingController extends Controller
{
    public function inbound()
    {
        return view('pages.products.logistics.manage-booking.inbound');
    }
    public function outbound()
    {
        return view('pages.products.logistics.manage-booking.outbound');
    }
    public function return()
    {
        return view('pages.products.logistics.manage-booking.return');
    }
    public function create()
    {
        $carriers = LogisticsCarrier::all();
        return view('pages.products.logistics.manage-booking.create', compact('carriers'));
    }

    public function show($id)
    {
        if (!is_numeric($id) || (int) $id <= 0) abort(404);
        $booking = LogisticsBooking::with(['items', 'carrier', 'events.creator'])->findOrFail((int) $id);
        return view('pages.products.logistics.manage-booking.show', compact('booking'));
    }

    public function edit($id)
    {
        if (!is_numeric($id) || (int) $id <= 0) abort(404);
        $booking = LogisticsBooking::with('items')->findOrFail((int) $id);
        $carriers = LogisticsCarrier::all();
        return view('pages.products.logistics.manage-booking.create', compact('booking', 'carriers'));
    }

    public function getData(Request $request)
    {
        try {
            $mode = $request->get('mode', 'Inland');

            // If mode is Inland, we want to fetch LTL and FTL (the types saved from the create form)
            $query = LogisticsBooking::with(['items', 'carrier', 'events'])->orderBy('created_at', 'desc');

            if ($mode === 'Inland') {
                $query->whereIn('service_type', ['Inland', 'LTL', 'FTL']);
            } else {
                $query->where('service_type', $mode);
            }

            $today = now()->startOfDay();

            $bookings = $query->get()->map(function ($booking) use ($today) {
                // Normalize the mode for the frontend UI
                $displayMode = $booking->service_type;
                if (in_array($displayMode, ['LTL', 'FTL'])) {
                    $displayMode = 'Inland';
                }

                // Status is approval-gated via events
                $hasPickupApproved   = $booking->events->contains('event_type', 'pickup_approved');
                $hasDeliveryApproved = $booking->events->contains('event_type', 'delivery_approved');

                if ($booking->status === 'Draft') {
                    $shipmentStatus = 'Draft';
                } elseif ($hasDeliveryApproved) {
                    $shipmentStatus = 'Delivered';
                } elseif ($hasPickupApproved) {
                    $pickDate     = $booking->pick_date ? \Carbon\Carbon::parse($booking->pick_date)->startOfDay() : null;
                    $approvalEvent = $booking->events->firstWhere('event_type', 'pickup_approved');
                    $approvalDate = $approvalEvent?->actual_date ? \Carbon\Carbon::parse($approvalEvent->actual_date)->startOfDay() : null;

                    if ($pickDate && $today->lt($pickDate)) {
                        // Approved early but pick date hasn't arrived yet → still Booked
                        $shipmentStatus = 'Booked';
                    } elseif ($approvalDate && $today->lte($approvalDate)) {
                        // Same day as approval → Picked Up
                        $shipmentStatus = 'Picked Up';
                    } else {
                        // Day after approval onwards → In Transit
                        $shipmentStatus = 'In Transit';
                    }
                } else {
                    // No pickup approval yet → always Booked, regardless of date
                    $shipmentStatus = 'Booked';
                }

                return [
                    'db_id'       => $booking->id,
                    'id'          => 'SHP-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT),
                    'pos'         => $booking->reference_po ? [$booking->reference_po] : ['---'],
                    'carrier'     => $booking->carrier ? $booking->carrier->legal_carrier_name : 'TBD',
                    'ref'         => $booking->reference_billing ?? ($booking->service_type . '-' . $booking->id),
                    'mode'        => $displayMode,
                    'status'      => $shipmentStatus,
                    'eta'         => $booking->eta ? $booking->eta->format('M d, Y') : 'TBD',
                    'origin'      => $booking->origin_company ?: ($booking->origin_country . ' ' . $booking->origin_postal),
                    'destination' => $booking->dest_company ?: ($booking->dest_country . ' ' . $booking->dest_postal),
                ];
            });

            return response()->json($bookings);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $bookingData = $request->all();

            // Map frontend camelCase to snake_case
            $bookingId = $request->input('db_id');
            if ($bookingId === 'null' || !$bookingId) {
                $bookingId = null;
            }

            $data = [
                'service_type' => (string) ($bookingData['serviceType'] ?? 'Inland'),
                'origin_country' => (string) ($bookingData['originCountry'] ?? ''),
                'origin_postal' => (string) ($bookingData['originPostal'] ?? ''),
                'origin_type' => (string) ($bookingData['originType'] ?? 'Business'),
                'origin_company' => $bookingData['originCompany'] ?? null,
                'origin_street' => $bookingData['originStreet'] ?? null,
                'origin_street2' => $bookingData['originStreet2'] ?? null,
                'origin_city' => $bookingData['originCity'] ?? null,
                'origin_state' => $bookingData['originState'] ?? null,

                'dest_country' => (string) ($bookingData['destCountry'] ?? ''),
                'dest_postal' => (string) ($bookingData['destPostal'] ?? ''),
                'dest_type' => (string) ($bookingData['destType'] ?? 'Business'),
                'dest_company' => $bookingData['destCompany'] ?? null,
                'dest_street' => $bookingData['destStreet'] ?? null,
                'dest_street2' => $bookingData['destStreet2'] ?? null,
                'dest_city' => $bookingData['destCity'] ?? null,
                'dest_state' => $bookingData['destState'] ?? null,

                'second_party' => $bookingData['secondParty'] ?? null,
                'third_party'  => $bookingData['thirdParty']  ?? null,
                'loading_date' => !empty($bookingData['loadingDate']) ? $bookingData['loadingDate'] : null,
                'pick_date'    => !empty($bookingData['pickDate'])    ? $bookingData['pickDate']    : null,
                'eta'          => !empty($bookingData['eta'])          ? $bookingData['eta']          : null,
                'services' => json_encode($bookingData['services'] ?? []),
                'freight_class' => (string) ($bookingData['freightClass'] ?? ''),

                'carrier_id' => !empty($bookingData['carrierId']) ? (int) $bookingData['carrierId'] : null,
                'carrier_rate' => (float) ($bookingData['carrierRate'] ?? 0),

                'pickup_time' => (string) ($bookingData['pickupTime'] ?? ''),
                'pickup_first_name' => (string) ($bookingData['pickupFirstName'] ?? ''),
                'pickup_last_name' => (string) ($bookingData['pickupLastName'] ?? ''),
                'pickup_email' => (string) ($bookingData['pickupEmail'] ?? ''),
                'pickup_phone' => (string) ($bookingData['pickupPhone'] ?? ''),

                'delivery_location_name' => (string) ($bookingData['deliveryLocationName'] ?? ''),
                'delivery_first_name' => (string) ($bookingData['deliveryFirstName'] ?? ''),
                'delivery_last_name' => (string) ($bookingData['deliveryLastName'] ?? ''),
                'delivery_email' => (string) ($bookingData['deliveryEmail'] ?? ''),
                'delivery_phone' => (string) ($bookingData['deliveryPhone'] ?? ''),

                'is_draft' => ($bookingData['is_draft'] ?? '0') === '1',
                'status' => ($bookingData['is_draft'] ?? '0') === '1' ? 'Draft' : 'Active',
                'company_id' => session('company_id'),
                'updated_at' => now()
            ];

            // Handle New Carrier Creation if ID is temporary
            if ($data['carrier_id'] && !DB::table('logistics_carriers')->where('id', $data['carrier_id'])->exists()) {
                // If it doesn't exist, it's a new carrier name from the search/modal
                $carrierName = $request->input('carrierSearch') ?: 'New Carrier';
                $newCarrierId = DB::table('logistics_carriers')->insertGetId([
                    'legal_carrier_name' => $carrierName,
                    'logistics_mode' => 'road_freight', // Default for this wizard
                    'company_id' => session('company_id'),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $data['carrier_id'] = $newCarrierId;
            }

            if ($bookingId) {
                $existing = LogisticsBooking::find($bookingId);

                // If pick_date changed, clear stale pickup events so alert resets for the new date
                $oldPickDate = $existing?->pick_date ? $existing->pick_date->toDateString() : null;
                $newPickDate = !empty($data['pick_date']) ? $data['pick_date'] : null;
                if ($oldPickDate !== $newPickDate) {
                    LogisticsBookingEvent::where('logistics_booking_id', $bookingId)
                        ->whereIn('event_type', ['pickup_approved', 'pickup_rejected'])
                        ->delete();
                }

                // If eta changed, clear stale delivery events so alert resets for the new date
                $oldEta = $existing?->eta ? $existing->eta->toDateString() : null;
                $newEta = !empty($data['eta']) ? $data['eta'] : null;
                if ($oldEta !== $newEta) {
                    LogisticsBookingEvent::where('logistics_booking_id', $bookingId)
                        ->whereIn('event_type', ['delivery_approved', 'delivery_rejected'])
                        ->delete();
                }

                DB::table('logistics_bookings')->where('id', $bookingId)->update($data);
                $booking = LogisticsBooking::find($bookingId);
                $booking->items()->delete();
            } else {
                $data['created_at'] = now();
                $bookingId = DB::table('logistics_bookings')->insertGetId($data);
                $booking = LogisticsBooking::find($bookingId);
            }

            // Store items with images
            if ($request->has('items')) {
                foreach ($request->items as $index => $item) {
                    $imagePath = null;
                    if ($request->hasFile("items.$index.image_file")) {
                        $imagePath = $request->file("items.$index.image_file")->store('shipments', 'public');
                    }

                    $booking->items()->create([
                        'description' => (string) ($item['desc'] ?? ''),
                        'package_type' => (string) ($item['packageType'] ?? 'Pallets'),
                        'condition' => (string) ($item['condition'] ?? 'New'),
                        'quantity' => (int) ($item['qty'] ?? 1),
                        'length' => (float) ($item['l'] ?? 0),
                        'width' => (float) ($item['w'] ?? 0),
                        'height' => (float) ($item['h'] ?? 0),
                        'weight' => (float) ($item['weight'] ?? 0),
                        'image_path' => $imagePath,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully!',
                'booking_id' => 'SHP-' . str_pad($booking->id, 4, '0', STR_PAD_LEFT)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Booking store error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        if (!is_numeric($id) || (int) $id <= 0) abort(404);
        try {
            DB::beginTransaction();
            $booking = LogisticsBooking::findOrFail((int) $id);
            $booking->items()->delete();
            $booking->delete();
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function zipLookup(Request $request)
    {
        $q       = trim($request->get('q', ''));
        $country = strtoupper(trim($request->get('country', 'US')));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = \DB::table('zip_codes')
            ->where('country_code', $country)
            ->where('zipcode', 'ILIKE', $q . '%')
            ->select('zipcode', 'city', 'state')
            ->orderBy('zipcode')
            ->limit(12)
            ->get();

        return response()->json($results);
    }

    public function pendingActions()
    {
        $today = Carbon::today();

        // The live logistics_bookings table uses the v2 date fields.
        $pickupDue = LogisticsBookingV2::with('carrier')
            ->where('status', 'Booked')
            ->whereNotNull('estimated_time_departure')
            ->whereDate('estimated_time_departure', '<=', $today)
            ->get()
            ->map(fn($booking) => $this->bookingAlertPayload(
                $booking,
                'pickup',
                $booking->estimated_time_departure
            ));

        $deliveryDue = LogisticsBookingV2::with('carrier')
            ->where('status', 'In-Transit')
            ->whereNotNull('estimated_time_delivery')
            ->whereDate('estimated_time_delivery', '<=', $today)
            ->get()
            ->map(fn($booking) => $this->bookingAlertPayload(
                $booking,
                'delivery',
                $booking->estimated_time_delivery
            ));

        $inTransit = LogisticsBookingV2::with('carrier')
            ->where('status', 'In-Transit')
            ->whereNotNull('estimated_time_delivery')
            ->get()
            ->map(function ($booking) {
                $payload = $this->bookingAlertPayload(
                    $booking,
                    'in_transit',
                    $booking->estimated_time_delivery
                );
                $payload['eta'] = $payload['scheduled_date'];

                return $payload;
            });

        return response()->json([
            'pickup_due'   => $pickupDue,
            'delivery_due' => $deliveryDue,
            'in_transit'   => $inTransit,
        ]);
    }

    public function approvePickup(Request $request, $id)
    {
        if (!is_numeric($id) || (int) $id <= 0) abort(404);
        $booking = LogisticsBookingV2::findOrFail((int) $id);
        $originalDate = $booking->estimated_time_departure;

        $booking->update([
            'status' => 'In-Transit',
        ]);

        $this->recordBookingStatusHistory(
            $booking,
            'pickup_approved',
            $originalDate,
            Carbon::today(),
            'In-Transit',
            $request->input('reason') ?: null
        );

        return response()->json(['success' => true]);
    }

    public function rejectPickup(Request $request, $id)
    {
        if (!is_numeric($id) || (int) $id <= 0) abort(404);
        $booking = LogisticsBookingV2::findOrFail((int) $id);
        $originalDate = $booking->estimated_time_departure;
        $newDate = $request->input('new_date') ?: null;

        if ($newDate) {
            $booking->update([
                'estimated_time_departure' => $newDate,
                'status' => 'Booked',
            ]);
        }

        $this->recordBookingStatusHistory(
            $booking,
            'pickup_rejected',
            $originalDate,
            $newDate,
            $booking->status,
            $request->input('reason') ?: null
        );

        return response()->json(['success' => true]);
    }

    public function approveDelivery(Request $request, $id)
    {
        if (!is_numeric($id) || (int) $id <= 0) abort(404);
        $booking = LogisticsBookingV2::findOrFail((int) $id);
        $originalDate = $booking->estimated_time_delivery;

        $booking->update([
            'status' => 'Delivered',
        ]);

        $this->recordBookingStatusHistory(
            $booking,
            'delivery_approved',
            $originalDate,
            Carbon::today(),
            'Delivered',
            $request->input('reason') ?: null
        );

        return response()->json(['success' => true]);
    }

    public function rejectDelivery(Request $request, $id)
    {
        if (!is_numeric($id) || (int) $id <= 0) abort(404);
        $booking = LogisticsBookingV2::findOrFail((int) $id);
        $originalDate = $booking->estimated_time_delivery;
        $newDate = $request->input('new_date') ?: null;

        if ($newDate) {
            $booking->update([
                'estimated_time_delivery' => $newDate,
                'status' => 'In-Transit',
            ]);
        }

        $this->recordBookingStatusHistory(
            $booking,
            'delivery_rejected',
            $originalDate,
            $newDate,
            $booking->status,
            $request->input('reason') ?: null
        );

        return response()->json(['success' => true]);
    }

    private function bookingAlertPayload(LogisticsBookingV2 $booking, string $type, $date): array
    {
        $carrier = $booking->carrier;
        $scheduledDate = $date ? Carbon::parse($date)->format('M d, Y') : '-';

        return [
            'db_id' => $booking->id,
            'id' => $booking->booking_number ?: 'SHP-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT),
            'type' => $type,
            'carrier' => $carrier?->carrier_company_name ?: ($carrier?->trading_brand_name ?: 'TBD'),
            'origin' => $booking->origin_port ?: ($booking->place_of_receipt ?: '-'),
            'destination' => $booking->destination_port ?: ($booking->final_delivery_place ?: '-'),
            'scheduled_date' => $scheduledDate,
        ];
    }

    private function recordBookingStatusHistory(
        LogisticsBookingV2 $booking,
        string $eventType,
        $originalDate,
        $newDate,
        ?string $status,
        ?string $reason
    ): void {
        LogisticsBookingStatusHistory::create([
            'booking_id' => $booking->id,
            'event_type' => $eventType,
            'original_date' => $originalDate,
            'new_date' => $newDate,
            'status_changed_to' => $status,
            'reason' => $reason,
            'action_by' => auth()->id(),
            'company_id' => $booking->company_id,
        ]);
    }
}
