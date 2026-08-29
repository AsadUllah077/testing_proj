@props([
    'booking' => null,
    'childBooking' => null,
    'childBookingNumber' => null,
    'submitLabel' => 'Create Inland Booking',
    'showSubmitButton' => true,
])

@php
    $isChildEditMode = is_array($childBooking);
    $childIdentity = $isChildEditMode ? data_get($childBooking, 'booking_identity', []) : [];
    $childCarrier = $isChildEditMode ? data_get($childBooking, 'inland_carrier', []) : [];
    $childVehicle = $isChildEditMode ? data_get($childBooking, 'vehicle_driver_assignment', []) : [];
    $childRoute = $isChildEditMode ? data_get($childBooking, 'route_plan_waypoints', []) : [];
    $childDelivery = $isChildEditMode ? data_get($childBooking, 'final_delivery_details', []) : [];
    $inlandInfo = $isChildEditMode
        ? [
            'do_number' => data_get($childIdentity, 'do_number'),
            'customs_channel' => data_get($childIdentity, 'customs_channel'),
            'carrier_contact' => data_get($childCarrier, 'carrier_contact'),
            'carrier_contract' => data_get($childCarrier, 'carrier_contract'),
            'return_deadline' => data_get($childCarrier, 'return_deadline'),
            'freight_rate_pkr' => data_get($childCarrier, 'freight_rate_pkr'),
            'transit_stop' => data_get($childCarrier, 'transit_stop'),
            'oversize_permit' => data_get($childCarrier, 'oversize_permit'),
            'vehicle_reg' => data_get($childVehicle, 'vehicle_reg'),
            'vehicle_model' => data_get($childVehicle, 'vehicle_model'),
            'truck_capacity' => data_get($childVehicle, 'truck_capacity'),
            'driver_name' => data_get($childVehicle, 'driver_name'),
            'driver_cnic' => data_get($childVehicle, 'driver_cnic'),
            'driver_mobile' => data_get($childVehicle, 'driver_mobile'),
            'co_driver' => data_get($childVehicle, 'co_driver'),
            'gps_unit_id' => data_get($childVehicle, 'gps_unit_id'),
            'gps_portal' => data_get($childVehicle, 'gps_portal'),
            'vehicle_inspection' => data_get($childVehicle, 'vehicle_inspection'),
            'container_return_type' => data_get($childVehicle, 'container_return_type'),
            'route_option' => data_get($childRoute, 'route_option'),
            'total_distance' => data_get($childRoute, 'total_distance'),
            'est_transit_time' => data_get($childRoute, 'est_transit_time'),
            'delivery_address' => data_get($childDelivery, 'delivery_address'),
            'receiving_contact' => data_get($childDelivery, 'receiving_contact'),
            'delivery_window' => data_get($childDelivery, 'delivery_window'),
            'unloading_method' => data_get($childDelivery, 'unloading_method'),
            'delivery_receipt_type' => data_get($childDelivery, 'delivery_receipt_type'),
            'post_delivery_inspection' => data_get($childDelivery, 'post_delivery_inspection'),
        ]
        : ($booking
            ? $booking->getInlandInfo()
            : []);

    $selectedCarrier = old(
        'carrier',
        $isChildEditMode ? data_get($childCarrier, 'carrier', '') : $booking->carrier ?? '',
    );
    $bookingNumberValue = old('booking_number', $childBookingNumber ?? '');
    $freightModeValue = old('freight_mode', $isChildEditMode ? data_get($childIdentity, 'freight_mode', 'ftl') : 'ftl');
    $storedContainerNumbers = $isChildEditMode ? data_get($childIdentity, 'container_nos') : null;
    if (! is_array($storedContainerNumbers)) {
        $storedContainerNumbers = [
            $isChildEditMode ? data_get($childIdentity, 'container_no', '') : '',
        ];
    }
    $containerNumbersValue = old('container_nos', $storedContainerNumbers);
    if (! is_array($containerNumbersValue) || $containerNumbersValue === []) {
        $containerNumbersValue = [''];
    }
    $transitStopValue = old('transit_stop', data_get($inlandInfo, 'transit_stop', ''));
    $actualEtdValue = old(
        'actual_etd',
        $isChildEditMode && data_get($childCarrier, 'etd')
            ? \Carbon\Carbon::parse(data_get($childCarrier, 'etd'))->format('Y-m-d\TH:i')
            : ($booking && !empty($booking->actual_etd)
                ? $booking->actual_etd->format('Y-m-d\TH:i')
                : ''),
    );
    $inTransitEtaValue = old(
        'intransit_point_eta',
        $isChildEditMode && data_get($childCarrier, 'eta')
            ? \Carbon\Carbon::parse(data_get($childCarrier, 'eta'))->format('Y-m-d\TH:i')
            : ($booking && !empty($booking->intransit_point_eta)
                ? $booking->intransit_point_eta->format('Y-m-d\TH:i')
                : ''),
    );
    $deliveryDateValue = old(
        'delivery_date',
        $isChildEditMode && data_get($childCarrier, 'delivery_date')
            ? \Carbon\Carbon::parse(data_get($childCarrier, 'delivery_date'))->format('Y-m-d\TH:i')
            : ($booking && !empty($booking->delivery_date)
                ? $booking->delivery_date->format('Y-m-d\TH:i')
                : ''),
    );
    $truckTypeValue = old(
        'truck_type',
        $isChildEditMode ? data_get($childVehicle, 'truck_type', '') : $booking->truck_type ?? '',
    );
    $instructionsValue = old(
        'delivery_instructions',
        $isChildEditMode ? data_get($childDelivery, 'delivery_instructions', '') : $booking->special_instructions ?? '',
    );
@endphp

<div class="inland-child-container" x-data="{
    activeTab: 'form',
    containerNumbers: @js(array_values($containerNumbersValue)),
    addContainer() {
        this.containerNumbers.push('');
    },
    removeContainer(index) {
        if (this.containerNumbers.length > 1) {
            this.containerNumbers.splice(index, 1);
        }
    }
}" x-init="$nextTick(() => {
    const regInput = $el.querySelector('input[name=&quot;vehicle_reg&quot;]');
    if (regInput) {
        regInput.addEventListener('change', function() {
            const val = this.value.trim();
            if (val.length >= 2) {
                fetch('/logistics/manage-booking/v2/lookup-vehicle?reg_no=' + encodeURIComponent(val))
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const modelInput = $el.querySelector('input[name=&quot;vehicle_model&quot;]');
                            const capInput = $el.querySelector('input[name=&quot;truck_capacity&quot;]');
                            if (modelInput) modelInput.value = data.vehicle_model;
                            if (capInput) capInput.value = data.truck_capacity;
                        }
                    })
                    .catch(err => console.error('Error looking up vehicle:', err));
            }
        });
    }

    const cnicInput = $el.querySelector('input[name=&quot;driver_cnic&quot;]');
    if (cnicInput) {
        cnicInput.addEventListener('change', function() {
            const val = this.value.trim();
            if (val.length >= 5) {
                fetch('/logistics/manage-booking/v2/lookup-driver?cnic=' + encodeURIComponent(val))
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const nameInput = $el.querySelector('input[name=&quot;driver_name&quot;]');
                            const mobileInput = $el.querySelector('input[name=&quot;driver_mobile&quot;]');
                            const coDriverInput = $el.querySelector('input[name=&quot;co_driver&quot;]');
                            if (nameInput) nameInput.value = data.driver_name;
                            if (mobileInput) mobileInput.value = data.driver_mobile;
                            if (coDriverInput) coDriverInput.value = data.co_driver;
                        }
                    })
                    .catch(err => console.error('Error looking up driver:', err));
            }
        });
    }

    const carrierSelect = $($el).find('.carrier-select');
    if (carrierSelect.length) {
        carrierSelect.select2({ placeholder: 'Select Carrier' }).on('change', function(e) {
            const opt = e.target.options[e.target.selectedIndex];
            if (opt) {
                const contact = opt.getAttribute('data-contact') || '';
                const contract = opt.getAttribute('data-contract') || '';
                const contactInput = $el.querySelector('input[name=&quot;carrier_contact&quot;]');
                const contractInput = $el.querySelector('input[name=&quot;carrier_contract&quot;]');
                if (contactInput) contactInput.value = contact;
                if (contractInput) contractInput.value = contract;
            }
        });
    }
});">
    <style>
        .stabs {
            display: flex;
            gap: 2px;
            background: #e2e8f0;
            padding: 4px;
            border-radius: 12px;
            margin-bottom: 24px;
            width: fit-content;
        }

        .stab {
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            transition: all 0.2s;
            user-select: none;
        }

        .stab:hover {
            color: #1e293b;
            background: rgba(255, 255, 255, 0.5);
        }

        .stab.on {
            background: #fff;
            color: #0078C8;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .fsec {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            margin-bottom: 24px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .fsec-hd {
            padding: 16px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
        }

        .fsec-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .fsec-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .fsec-body {
            padding: 24px;
        }

        .fr {
            display: grid;
            gap: 24px;
            margin-bottom: 24px;
        }

        .fr:last-child {
            margin-bottom: 0;
        }

        .fr2 {
            grid-template-columns: repeat(2, 1fr);
        }

        .fr3 {
            grid-template-columns: repeat(3, 1fr);
        }

        .fr4 {
            grid-template-columns: repeat(4, 1fr);
        }

        .fg {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .fl {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .fl.req::after {
            content: ' *';
            color: #ef4444;
        }

        .fi {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            color: #1e293b;
            transition: all 0.2s;
            background: #fff;
        }

        .fi:focus {
            outline: none;
            border-color: #0078C8;
            box-shadow: 0 0 0 4px rgba(0, 120, 200, 0.1);
        }

        .fi.auto {
            background: #f1f5f9;
            color: #64748b;
            cursor: not-allowed;
            border-style: dashed;
        }

        .fi.filled {
            border-color: #0078C8;
            background: #f0f9ff;
        }

        .fi-hint {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 2px;
        }

        .pill {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .p-nb {
            border: 1px solid transparent;
        }

        .p-green {
            background: #dcfce7;
            color: #166534;
        }

        .p-gray {
            background: #f1f5f9;
            color: #475569;
        }

        .tbl {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
        }

        .tbl th {
            text-align: left;
            padding: 12px 16px;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
        }

        .tbl td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }

        .btn-orange {
            background: #f97316;
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(249, 115, 22, 0.2);
        }

        .btn-orange:hover {
            background: #ea580c;
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(249, 115, 22, 0.3);
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
        }

        .text-xs {
            font-size: 11px;
        }

        /* Variables fallback */
        :root {
            --orange2: #ffedd5;
            --orange3: #fb923c;
            --blue2: #dbeafe;
            --blue4: #60a5fa;
            --blue5: #3b82f6;
            --green2: #dcfce7;
            --green4: #22c55e;
            --red: #ef4444;
            --red2: #fee2e2;
            --red4: #f87171;
            --amber3: #fcd34d;
        }
    </style>

    <!-- Navigation Tabs -->
    <div class="stabs">
        <div class="stab" :class="activeTab === 'form' ? 'on' : ''" @click="activeTab = 'form'">Inland Booking Form
        </div>
        <div class="stab" :class="activeTab === 'vehicle' ? 'on' : ''" @click="activeTab = 'vehicle'">Vehicle & Driver
        </div>
        <div class="stab" :class="activeTab === 'route' ? 'on' : ''" @click="activeTab = 'route'">Route & Waypoints
        </div>
        <div class="stab" :class="activeTab === 'delivery' ? 'on' : ''" @click="activeTab = 'delivery'">Final Delivery
        </div>
    </div>

    <!-- IB FORM -->
    <div x-show="activeTab === 'form'" x-cloak>
        <div class="fsec">
            <div class="fsec-hd">
                <div class="fsec-title">
                    <div class="fsec-icon" style="background:var(--orange3)">🆔</div>Booking Identity
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                    <span class="pill p-nb"
                        style="background:var(--blue2);color:#1e40af;border:1px solid var(--blue4);font-size:9px;">AUTO-LINKED
                        FROM PARENT</span>
                    <span class="pill p-nb"
                        style="background:var(--orange2);color:#EA580C;border:1px solid var(--orange3);font-size:9px;">FILL
                        ON DAY OF DISPATCH</span>
                </div>
            </div>
            <div class="fsec-body">
                <div class="fr fr3">
                    <div class="fg">
                        <div class="fl">Booking Number</div>
                        <input class="fi " name="booking_number" type="text" value="{{ $bookingNumberValue }}" />
                    </div>
                    <div class="fg">
                        <div class="fl">Parent Shipment Ref</div>
                        <input class="fi auto" :value="parentData.booking_number" readonly />
                        <div class="fi-hint">Auto-linked to parent</div>
                    </div>
                    <div class="fg">
                        <div class="fl req">Freight Mode</div>
                        <select class="fi" name="freight_mode">
                            <option value="ftl" {{ $freightModeValue === 'ftl' ? 'selected' : '' }}>FTL - Full Truck
                                Load</option>
                            <option value="ltl" {{ $freightModeValue === 'ltl' ? 'selected' : '' }}>LTL</option>
                            <option value="flatbed" {{ $freightModeValue === 'flatbed' ? 'selected' : '' }}>Flatbed
                                Trailer</option>
                        </select>
                    </div>
                </div>
                <div class="fr fr3">
                    <div class="fg">
                        <div class="flex items-center justify-between" style="margin-bottom: 6px;">
                            <div class="fl" style="margin-bottom: 0;">Container No.</div>
                            <button type="button" @click="addContainer()"
                                class="text-[11px] font-semibold text-[#0078C8] hover:text-[#0066aa]">
                                Add +
                            </button>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(containerNumber, index) in containerNumbers" :key="index">
                                <div class="flex items-center gap-2">
                                    <input class="fi min-w-0 flex-1" type="text"
                                        x-model="containerNumbers[index]"
                                        :name="'container_nos[' + index + ']'"
                                        placeholder="Enter container number" />
                                    <button type="button" @click="removeContainer(index)"
                                        x-show="containerNumbers.length > 1"
                                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700"
                                        title="Remove container" aria-label="Remove container">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="fg">
                        <div class="fl">Delivery Order No.</div>
                        <input class="fi" name="do_number"
                            value="{{ old('do_number', data_get($inlandInfo, 'do_number', '')) }}" />
                        <div class="fi-hint">From shipping agent</div>
                    </div>
                    <div class="fg">
                        <div class="fl">Customs Channel</div>
                        <select class="fi" name="customs_channel">
                            <option value="">Select Channel</option>
                            <option value="green"
                                {{ old('customs_channel', data_get($inlandInfo, 'customs_channel', '')) == 'green' ? 'selected' : '' }}>
                                Green — Cleared</option>
                            <option value="yellow"
                                {{ old('customs_channel', data_get($inlandInfo, 'customs_channel', '')) == 'yellow' ? 'selected' : '' }}>
                                Yellow</option>
                            <option value="red"
                                {{ old('customs_channel', data_get($inlandInfo, 'customs_channel', '')) == 'red' ? 'selected' : '' }}>
                                Red</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="fsec">
            <div class="fsec-hd">
                <div class="fsec-title">
                    <div class="fsec-icon" style="background:var(--orange3)">🚛</div>Inland Carrier & Schedule
                </div>
                <span class="pill p-nb"
                    style="background:var(--orange2);color:#EA580C;border:1px solid var(--orange3);font-size:9px;">FILL
                    ON DAY OF DEPARTURE</span>
            </div>
            <div class="fsec-body">
                <div class="fr fr3">
                    <div class="fg">
                        <div class="flex items-center justify-between mb-1" style="margin-bottom: 4px;">
                            <div class="fl req" style="margin-bottom: 0;">Inland Carrier</div>
                            <button type="button" @click="showCarrierModal = true"
                                class="text-[10px] text-[#0078C8] hover:underline font-semibold flex items-center gap-0.5">
                                ➕ Add New
                            </button>
                        </div>

                        {{-- <select class="select2 w-full fi carrier-select" name="carrier">
                            <option value="" disabled
                                {{ $selectedCarrier === '' ? 'selected' : '' }}>Select Carrier
                            </option>
                            @php
                                $approvedCarriers = \App\Models\LogisticsV2Carriers::approved()
                                    ->orderBy('carrier_company_name')
                                    ->get();
                                $defaultCarriers = ['NLC', 'TCS', 'Al-Rehman', 'Pak Wheels', 'Other'];
                                $approvedNames = $approvedCarriers->pluck('carrier_company_name')->toArray();
                                $hasSelected = false;
                            @endphp
                            @foreach ($approvedCarriers as $c)
                                @if ($c->carrier_company_name === $selectedCarrier)
                                    @php $hasSelected = true; @endphp
                                @endif
                                <option value="{{ $c->carrier_company_name }}"
                                    data-contact="{{ $c->phone_pakistan_office ?: ($c->contact_email ?: '') }}"
                                    data-contract="{{ $c->ntn_scac_code ?: '' }}"
                                    {{ $selectedCarrier === $c->carrier_company_name ? 'selected' : '' }}>
                                    {{ $c->carrier_company_name }}
                                </option>
                            @endforeach
                            @foreach ($defaultCarriers as $dc)
                                @if (!in_array($dc, $approvedNames))
                                    @if ($dc === $selectedCarrier)
                                        @php $hasSelected = true; @endphp
                                    @endif
                                    <option value="{{ $dc }}"
                                        {{ $selectedCarrier === $dc ? 'selected' : '' }}>
                                        {{ $dc }}
                                    </option>
                                @endif
                            @endforeach
                            @if (!empty($selectedCarrier) && !$hasSelected)
                                <option value="{{ $selectedCarrier }}" selected>{{ $selectedCarrier }}</option>
                            @endif
                        </select> --}}
                        <select class="fi carrier-select" name="carrier">
                            <option value="" disabled {{ $selectedCarrier === '' ? 'selected' : '' }}>Select
                                Carrier
                            </option>
                            @php
                                $approvedCarriers = \App\Models\LogisticsV2Carriers::approved()
                                    ->orderBy('carrier_company_name')
                                    ->get();
                                $defaultCarriers = ['NLC', 'TCS', 'Al-Rehman', 'Pak Wheels', 'Other'];
                                $approvedNames = $approvedCarriers->pluck('carrier_company_name')->toArray();
                                $hasSelected = false;
                            @endphp
                            @foreach ($approvedCarriers as $c)
                                @if ($c->carrier_company_name === $selectedCarrier)
                                    @php $hasSelected = true; @endphp
                                @endif
                                <option value="{{ $c->carrier_company_name }}"
                                    {{ $selectedCarrier === $c->carrier_company_name ? 'selected' : '' }}>
                                    {{ $c->carrier_company_name }}
                                </option>
                            @endforeach
                            @foreach ($defaultCarriers as $dc)
                                @if (!in_array($dc, $approvedNames))
                                    @if ($dc === $selectedCarrier)
                                        @php $hasSelected = true; @endphp
                                    @endif
                                    <option value="{{ $dc }}"
                                        {{ $selectedCarrier === $dc ? 'selected' : '' }}>
                                        {{ $dc }}
                                    </option>
                                @endif
                            @endforeach
                            @if (!empty($selectedCarrier) && !$hasSelected)
                                <option value="{{ $selectedCarrier }}" selected>{{ $selectedCarrier }}</option>
                            @endif
                        </select>

                    </div>
                    <div class="fg">
                        <div class="fl">Carrier Office / Contact</div>
                        <input class="fi" name="carrier_contact"
                            value="{{ old('carrier_contact', data_get($inlandInfo, 'carrier_contact', '')) }}" />
                    </div>
                    <div class="fg">
                        <div class="fl">Carrier Contract Ref</div>
                        <input class="fi" name="carrier_contract"
                            value="{{ old('carrier_contract', data_get($inlandInfo, 'carrier_contract', '')) }}" />
                    </div>
                </div>
                <div class="fr fr4">
                    <div class="fg">
                        <div class="fl req">Actual ETD</div>
                        <input class="fi" type="datetime-local" name="actual_etd"
                            value="{{ $actualEtdValue }}" />
                        <div class="fi-hint">Set on departure day</div>
                    </div>
                    <div class="fg">
                        <div class="fl req">ETA — Intransit Point</div>
                        <input class="fi" type="datetime-local" name="intransit_point_eta"
                            value="{{ $inTransitEtaValue }}" />
                        <div class="fi-hint">Estimated arrival at dry port</div>
                    </div>
                    <div class="fg">
                        <div class="fl">ETA - Final Delivery</div>
                        <input class="fi" type="datetime-local" name="delivery_date"
                            value="{{ $deliveryDateValue }}" />
                        <div class="fi-hint">Final delivery estimation</div>
                    </div>
                    <div class="fg">
                        <div class="fl req" style="color:var(--red)">Container Return Deadline</div>
                        <input class="fi" style="border-color:var(--red4);background:var(--red2);color:var(--red)"
                            type="date" name="return_deadline"
                            value="{{ old('return_deadline', data_get($inlandInfo, 'return_deadline', '')) }}" />
                        <div class="fi-hint" style="color:var(--red)">⚠ Return empty to port by this date</div>
                    </div>
                </div>
                <div class="fr fr3">
                    <div class="fg">
                        <div class="fl req">Pickup Point</div>
                        <input class="fi" name="pickup_point" x-model="parentData.pickup_point" />
                        <div class="fi-hint">E.g. Port Gate, Warehouse</div>
                    </div>
                    <div class="fg">
                        <x-logistics.manage-booking.v2.transit-stop-select :selected="$transitStopValue" />
                    </div>
                    <div class="fg">
                        <div class="fl req">Final Destination</div>
                        <input class="fi" name="final_destination" x-model="parentData.final_destination" />
                    </div>
                </div>
                <div class="fr fr2">
                    <div class="fg">
                        <div class="fl">Inland Freight Rate (PKR)</div>
                        <input class="fi" name="freight_rate_pkr"
                            value="{{ old('freight_rate_pkr', data_get($inlandInfo, 'freight_rate_pkr', '')) }}" />
                    </div>

                    <div class="fg">
                        <div class="fl">Oversize Permit</div>
                        <select class="fi" name="oversize_permit">
                            <option value="no"
                                {{ old('oversize_permit', data_get($inlandInfo, 'oversize_permit', '')) == 'no' ? 'selected' : '' }}>
                                No</option>
                            <option value="yes"
                                {{ old('oversize_permit', data_get($inlandInfo, 'oversize_permit', '')) == 'yes' ? 'selected' : '' }}>
                                Yes — obtained</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Wizard Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-end gap-3">
                <button type="button" @click="activeTab = 'vehicle'"
                    class="px-5 py-2 text-xs font-bold text-white bg-[#0078C8] hover:bg-[#006bb0] rounded-lg transition-all shadow-sm flex items-center gap-2">
                    Next: Vehicle &amp; Driver <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- IB VEHICLE -->
    <div x-show="activeTab === 'vehicle'" x-cloak>
        <div class="fsec">
            <div class="fsec-hd">
                <div class="fsec-title">
                    <div class="fsec-icon" style="background:var(--orange3)">🚚</div>Vehicle & Driver Assignment
                </div>
                <span class="pill p-nb"
                    style="background:var(--orange2);color:#EA580C;border:1px solid var(--orange3);font-size:9px;">ASSIGNED
                    ON DISPATCH DAY</span>
            </div>
            <div class="fsec-body">
                <div class="fr fr4">
                    <div class="fg">
                        <div class="fl req">Vehicle Type</div><select class="fi" name="truck_type">
                            <option value="20ft" {{ $truckTypeValue == '20ft' ? 'selected' : '' }}>
                                Container Trailer (20ft)</option>
                            <option value="40ft" {{ $truckTypeValue == '40ft' ? 'selected' : '' }}>
                                Container Trailer (40ft)</option>
                            <option value="flatbed" {{ $truckTypeValue == 'flatbed' ? 'selected' : '' }}>
                                Flatbed Trailer</option>
                        </select>
                    </div>
                    <div class="fg">
                        <div class="fl req">Vehicle Reg. No.</div><input class="fi" name="vehicle_reg"
                            value="{{ old('vehicle_reg', data_get($inlandInfo, 'vehicle_reg', '')) }}" />
                    </div>
                    <div class="fg">
                        <div class="fl">Make / Model</div><input class="fi" name="vehicle_model"
                            value="{{ old('vehicle_model', data_get($inlandInfo, 'vehicle_model', '')) }}" />
                    </div>
                    <div class="fg">
                        <div class="fl">Truck Capacity (tons)</div><input class="fi" name="truck_capacity"
                            value="{{ old('truck_capacity', data_get($inlandInfo, 'truck_capacity', '')) }}" />
                    </div>
                </div>
                <div class="fr fr4">
                    <div class="fg">
                        <div class="fl req">Driver CNIC</div><input class="fi" name="driver_cnic"
                            value="{{ old('driver_cnic', data_get($inlandInfo, 'driver_cnic', '')) }}" />
                    </div>

                    <div class="fg">
                        <div class="fl req">Driver Name</div><input class="fi" name="driver_name"
                            value="{{ old('driver_name', data_get($inlandInfo, 'driver_name', '')) }}" />
                    </div>

                    <div class="fg">
                        <div class="fl">Driver Mobile</div><input class="fi" name="driver_mobile"
                            value="{{ old('driver_mobile', data_get($inlandInfo, 'driver_mobile', '')) }}" />
                    </div>
                    <div class="fg">
                        <div class="fl">Co-Driver</div><input class="fi" name="co_driver"
                            value="{{ old('co_driver', data_get($inlandInfo, 'co_driver', '')) }}" />
                    </div>
                </div>
                <div class="fr fr4">
                    <div class="fg">
                        <div class="fl req">GPS Unit ID</div><input class="fi" name="gps_unit_id"
                            value="{{ old('gps_unit_id', data_get($inlandInfo, 'gps_unit_id', '')) }}" />
                    </div>
                    <div class="fg">
                        <div class="fl">GPS Portal</div><input class="fi" name="gps_portal"
                            value="{{ old('gps_portal', data_get($inlandInfo, 'gps_portal', '')) }}" />
                    </div>
                    <div class="fg">
                        <div class="fl">Vehicle Inspection</div><select class="fi" name="vehicle_inspection">
                            <option value="pending"
                                {{ old('vehicle_inspection', data_get($inlandInfo, 'vehicle_inspection', '')) == 'pending' ? 'selected' : '' }}>
                                Pending</option>
                            <option value="cleared"
                                {{ old('vehicle_inspection', data_get($inlandInfo, 'vehicle_inspection', '')) == 'cleared' ? 'selected' : '' }}>
                                Cleared</option>
                        </select>
                    </div>
                    <div class="fg">
                        <div class="fl">Empty Container Return</div><select class="fi"
                            name="container_return_type">
                            <option value="same"
                                {{ old('container_return_type', data_get($inlandInfo, 'container_return_type', '')) == 'same' ? 'selected' : '' }}>
                                Same truck returns empty</option>
                            <option value="separate"
                                {{ old('container_return_type', data_get($inlandInfo, 'container_return_type', '')) == 'separate' ? 'selected' : '' }}>
                                Separate truck arranged</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Wizard Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between">
                <button type="button" @click="activeTab = 'form'"
                    class="px-5 py-2 text-xs font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> Back
                </button>
                <button type="button" @click="activeTab = 'route'"
                    class="px-5 py-2 text-xs font-bold text-white bg-[#0078C8] hover:bg-[#006bb0] rounded-lg transition-all shadow-sm flex items-center gap-2">
                    Next: Route &amp; Waypoints <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- IB ROUTE -->
    <div x-show="activeTab === 'route'" x-cloak>
        <div class="fsec">
            <div class="fsec-hd">
                <div class="fsec-title">
                    <div class="fsec-icon" style="background:var(--amber3)">🗺</div>Route Plan & Waypoints
                </div>
            </div>
            <div class="fsec-body">
                <div class="fr fr3">
                    <div class="fg">
                        <div class="fl">Route Option</div><select class="fi" name="route_option">
                            <option value="motorway"
                                {{ old('route_option', data_get($inlandInfo, 'route_option', '')) == 'motorway' ? 'selected' : '' }}>
                                Full Motorway (M-9 + M-2 + M-1)</option>
                            <option value="gt_road"
                                {{ old('route_option', data_get($inlandInfo, 'route_option', '')) == 'gt_road' ? 'selected' : '' }}>
                                GT Road (N-5)</option>
                            <option value="rail"
                                {{ old('route_option', data_get($inlandInfo, 'route_option', '')) == 'rail' ? 'selected' : '' }}>
                                NLC Rail + Road</option>
                        </select>
                    </div>
                    <div class="fg">
                        <div class="fl">Total Distance (Auto)</div><input class="fi " name="total_distance"
                            value="{{ old('total_distance', data_get($inlandInfo, 'total_distance', '')) }}" />
                    </div>
                    <div class="fg">
                        <div class="fl">Est. Transit Time (Auto)</div><input class="fi "
                            name="est_transit_time"
                            value="{{ old('est_transit_time', data_get($inlandInfo, 'est_transit_time', '')) }}" />
                    </div>
                </div>
            </div>

            <!-- Wizard Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between">
                <button type="button" @click="activeTab = 'vehicle'"
                    class="px-5 py-2 text-xs font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> Back
                </button>
                <button type="button" @click="activeTab = 'delivery'"
                    class="px-5 py-2 text-xs font-bold text-white bg-[#0078C8] hover:bg-[#006bb0] rounded-lg transition-all shadow-sm flex items-center gap-2">
                    Next: Final Delivery <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- IB DELIVERY -->
    <div x-show="activeTab === 'delivery'" x-cloak>
        <div class="fsec">
            <div class="fsec-hd">
                <div class="fsec-title">
                    <div class="fsec-icon" style="background:var(--green2)">🏭</div>Final Delivery Details
                </div>
            </div>
            <div class="fsec-body">
                <div class="fr fr3">
                    <div class="fg">
                        <div class="fl req">Delivery Address</div><input class="fi" name="delivery_address"
                            x-model="parentData.final_destination" />
                    </div>
                    <div class="fg">
                        <div class="fl req">Receiving Contact</div><input class="fi" name="receiving_contact"
                            value="{{ old('receiving_contact', data_get($inlandInfo, 'receiving_contact', '')) }}" />
                    </div>
                    <div class="fg">
                        <div class="fl">Delivery Window</div><input class="fi" name="delivery_window"
                            value="{{ old('delivery_window', data_get($inlandInfo, 'delivery_window', '')) }}" />
                    </div>
                </div>
                <div class="fr fr3">
                    <div class="fg">
                        <div class="fl">Unloading</div><select class="fi" name="unloading_method">
                            <option value="forklift"
                                {{ old('unloading_method', data_get($inlandInfo, 'unloading_method', '')) == 'forklift' ? 'selected' : '' }}>
                                Forklift available</option>
                            <option value="manual"
                                {{ old('unloading_method', data_get($inlandInfo, 'unloading_method', '')) == 'manual' ? 'selected' : '' }}>
                                Manual</option>
                        </select>
                    </div>
                    <div class="fg">
                        <div class="fl">Delivery Receipt</div><select class="fi"
                            name="delivery_receipt_type">
                            <option value="grn"
                                {{ old('delivery_receipt_type', data_get($inlandInfo, 'delivery_receipt_type', '')) == 'grn' ? 'selected' : '' }}>
                                GRN (Signed)</option>
                            <option value="digital"
                                {{ old('delivery_receipt_type', data_get($inlandInfo, 'delivery_receipt_type', '')) == 'digital' ? 'selected' : '' }}>
                                Digital sign-off</option>
                        </select>
                    </div>
                    <div class="fg">
                        <div class="fl">Post-Delivery Inspection</div><select class="fi"
                            name="post_delivery_inspection">
                            <option value="full"
                                {{ old('post_delivery_inspection', data_get($inlandInfo, 'post_delivery_inspection', '')) == 'full' ? 'selected' : '' }}>
                                Full unit count</option>
                            <option value="random"
                                {{ old('post_delivery_inspection', data_get($inlandInfo, 'post_delivery_inspection', '')) == 'random' ? 'selected' : '' }}>
                                Random sample</option>
                        </select>
                    </div>
                </div>
                <div class="fg">
                    <div class="fl">Instructions</div>
                    <textarea class="fi" name="delivery_instructions" rows="3">{{ $instructionsValue }}</textarea>
                </div>
            </div>

            <!-- Wizard Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between">
                <button type="button" @click="activeTab = 'route'"
                    class="px-5 py-2 text-xs font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> Back
                </button>
                @if ($showSubmitButton)
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg transition-all shadow-lg shadow-green-600/20 flex items-center gap-2">
                        <i class="fa-solid fa-check-double text-[10px]"></i> {{ $submitLabel }}
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
