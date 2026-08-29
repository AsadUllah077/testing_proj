@extends('layouts.auth')

@section('title', 'Edit Booking')

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .barcode-card {
            page-break-inside: avoid;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    {{-- <script>
        $(document).ready(function() {
            $('.ajax-hscode-select').select2({
                placeholder: 'Search Commodity / HS Code...',
                allowClear: true,
                ajax: {
                    url: '{{ route('logistics.manage-booking.v2.hs-codes.paginate') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.data.map(item => ({
                                id: item.hs_code,
                                text: item.hs_code + ' - ' + item.description,
                                description: item.description
                            })),
                            pagination: {
                                more: !!data.next_page
                            }
                        };
                    },
                    cache: true
                }
            });

            $('.ajax-hscode-select').on('select2:select', function(e) {
                var data = e.params.data;
                if (data && data.description) {
                    $('input[name="cargo_description"]').val(data.description);
                }
            });

            $('.ajax-hscode-select').on('select2:clear', function(e) {
                $('input[name="cargo_description"]').val('');
            });
        });
    </script> --}}

    <script>
        $(document).ready(function() {
            const $hsCode = $('.ajax-hscode-select');
            const $cargoDescription = $('input[name="cargo_description"]');

            function syncHsCodeDescription() {
                const selected = $hsCode.select2('data')[0];
                if (!selected) return;

                let description = selected.description || '';

                if (!description) {
                    description = $hsCode.find('option:selected').data('description') || '';
                }

                if (description && !$cargoDescription.val()) {
                    $cargoDescription.val(description);
                }
            }

            $hsCode.select2({
                placeholder: 'Search Commodity / HS Code...',
                allowClear: true,
                ajax: {
                    url: '{{ route('logistics.manage-booking.v2.hs-codes.paginate') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.data.map(item => ({
                                id: item.hs_code,
                                text: item.hs_code,
                                description: item.description
                            })),
                            pagination: {
                                more: !!data.next_page
                            }
                        };
                    },
                    cache: true
                }
            });

            syncHsCodeDescription();

            $hsCode.on('select2:select', function(e) {
                var data = e.params.data;
                if (data && data.description) {
                    $cargoDescription.val(data.description);
                }
            });

            $hsCode.on('select2:clear', function() {
                $cargoDescription.val('');
            });
        });
    </script>
@endpush
@section('content')
    @php
        $dangerousGoodValue = $booking->dangerous_good ?? ($booking->dangerous_goods ?? 'no');
        $isCustomDangerous = !in_array($dangerousGoodValue, ['no', 'imdg_1', 'imdg_2']);
        $dangerousSelected = $isCustomDangerous ? 'imdg_other' : $dangerousGoodValue;
        $dangerousCustomVal = $isCustomDangerous ? $dangerousGoodValue : '';
        $isChildEdit = $isChildEdit ?? false;
        $editingChild = $childBooking ?? null;
        $editingChildNumber = $childBookingNumber ?? null;
        $headerBookingNumber = $isChildEdit ? $editingChildNumber : $booking->booking_number ?? 'BK-1093';
        $viewUrl = $isChildEdit
            ? route(
                'logistics.manage-booking.v2.child-booking',
                data_get($editingChild, 'booking_identity.parent_id', $booking->id),
            )
            : route('logistics.manage-booking.v2.show', $booking->id ?? 1);
        $viewText = $isChildEdit ? 'Child List' : 'View';
        $formActionUrl = $isChildEdit
            ? route('logistics.manage-booking.v2.child-booking.update', [$booking->id, $editingChildNumber])
            : route('logistics.manage-booking.v2.update', $booking->id);
        $childParentId = old(
            'parent_id',
            $isChildEdit
                ? data_get($editingChild, 'booking_identity.parent_id', $booking->id)
                : $booking->parent_id ?? '',
        );
        $childParentBookingNumber = old(
            'parent_booking_number',
            $isChildEdit
                ? data_get($editingChild, 'booking_identity.parent_booking_number', $booking->booking_number)
                : '',
        );
        $childPickupPoint = old(
            'pickup_point',
            $isChildEdit ? data_get($editingChild, 'inland_carrier.pickup_point', '') : '',
        );
        $childFinalDestination = old(
            'final_destination',
            $isChildEdit
                ? (data_get($editingChild, 'inland_carrier.final_destination') ?:
                data_get($editingChild, 'final_delivery_details.delivery_address', ''))
                : '',
        );
        $bankInstrumentTypes = ['bank_contract', 'lc', 'open_account', 'documentary_collection'];
        $selectedBankInstrumentType = old('bank_instrument_type', $booking->bank_instrument_type ?? '');
        if (! in_array($selectedBankInstrumentType, $bankInstrumentTypes, true)) {
            $selectedBankInstrumentType = '';
        }
    @endphp
    <div class="mt-4">
        <!-- Module Tabs -->
        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.manage-booking') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="mx-auto px-3">
            <div class="overflow-hidden">

                {{-- Page Header --}}
                <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
                    <div>
                        <h1 class="text-gray-900 text-xl font-normal">
                            {{ $isChildEdit ? 'Edit Child Booking' : 'Edit Booking' }} - {{ $headerBookingNumber }}
                        </h1>
                        <p class="text-xs text-gray-400 mt-0.5">Update booking details — changes will notify the carrier</p>
                    </div>
                    <div class="flex gap-2">
                        <x-common.back-button url="{{ $viewUrl }}" text="{{ $viewText }}" />
                        <x-common.back-button url="{{ route('logistics.manage-booking.v2.index') }}" />
                    </div>
                </div>

                <form method="POST" action_url="{{ $formActionUrl }}" back_url="{{ $viewUrl }}" id="formSubmit"
                    @open-carrier-modal.window="showCarrierModal = true" class="space-y-4" enctype="multipart/form-data"
                    x-data="{
                        freight_mode: '{{ old('freight_mode', $booking->freight_mode ?? '') }}',
                        multimodal_sub_mode: '{{ old('multimodal_sub_mode', $booking->freight_sub_mode ?? ($booking->multimodal_sub_mode ?? '')) }}',
                        is_child: {{ $isChildEdit || $booking->parent_id ? 'true' : 'false' }},
                        childEditMode: {{ $isChildEdit ? 'true' : 'false' }},
                        parent_id: @js($childParentId),
                        dangerous_goods: '{{ old('dangerous_goods', $dangerousSelected) }}',
                        bank_instrument_type: @js($selectedBankInstrumentType),
                        parentData: {
                            booking_number: @js($childParentBookingNumber),
                            pickup_point: @js($childPickupPoint),
                            final_destination: @js($childFinalDestination)
                        },
                        fetchParentDetails() {
                            if (!this.parent_id) {
                                this.parentData = { booking_number: '', pickup_point: '', final_destination: '' };
                                return;
                            }
                            fetch(`/logistics/manage-booking/v2/${this.parent_id}/details`)
                                .then(res => res.json())
                                .then(data => {
                                    this.parentData = data;
                                })
                                .catch(err => console.error('Error fetching parent details:', err));
                        },
                        init() {
                            if (this.parent_id && !this.childEditMode) {
                                this.fetchParentDetails();
                            }
                        },
                        showCarrierModal: false,
                        carrierSubmitting: false,
                        showBarcodeModal: false,
                        barcodeCount: 0,
                        barcodeCommodity: '',
                        clientEmail: '',
                        openBarcodeModal(count, commodity) {
                            this.barcodeCount = count || 1;
                            this.barcodeCommodity = commodity || 'N/A';
                            this.showBarcodeModal = true;
                            this.$nextTick(() => {
                                this.generateBarcodes();
                            });
                        },
                        generateBarcodes() {
                            const container = document.getElementById('barcodeContainer');
                            container.innerHTML = '';
                            for (let i = 0; i < this.barcodeCount; i++) {
                                let div = document.createElement('div');
                                div.className = 'bg-white p-4 rounded border border-gray-200 flex flex-col items-center gap-2 barcode-card shadow-sm';
                                let svg = document.createElement('svg');
                                svg.id = 'barcode-' + i;
                                div.appendChild(svg);
                                let label = document.createElement('p');
                                label.className = 'text-[10px] font-bold text-gray-600 uppercase';
                                label.innerText = this.barcodeCommodity + ' (' + (i + 1) + '/' + this.barcodeCount + ')';
                                div.appendChild(label);
                                container.appendChild(div);
                                JsBarcode('#barcode-' + i, this.barcodeCommodity + '-' + (i + 1), {
                                    format: 'CODE128',
                                    width: 1.5,
                                    height: 50,
                                    displayValue: true,
                                    fontSize: 10
                                });
                            }
                        },
                        submitQuickCarrier() {
                            const companyName = document.getElementById('quick_carrier_company_name').value.trim();
                            const transportModes = document.getElementById('quick_transport_modes').value;
                            const country = document.getElementById('quick_country_of_incorporation').value.trim();
                            const contactPerson = document.getElementById('quick_contact_person').value.trim();
                            const contactEmail = document.getElementById('quick_contact_email').value.trim();
                            const paymentTerms = document.getElementById('quick_payment_terms').value;

                            if (!companyName || !transportModes || !country || !contactPerson || !contactEmail || !paymentTerms) {
                                alert('Please fill in all required fields.');
                                return;
                            }

                            this.carrierSubmitting = true;

                            const csrfInput = document.querySelector('input[name=\'_token\']');
                            const csrfMeta = document.querySelector('meta[name=\'csrf-token\']');
                            const token = csrfInput ? csrfInput.value : (csrfMeta ? csrfMeta.getAttribute('content') : '');

                            fetch('{{ route('logistics.carriers.v2.quick-store') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': token
                                    },
                                    body: JSON.stringify({
                                        carrier_company_name: companyName,
                                        transport_modes: transportModes,
                                        country_of_incorporation: country,
                                        contact_person: contactPerson,
                                        contact_email: contactEmail,
                                        payment_terms: paymentTerms
                                    })
                                })
                                .then(res => res.json().catch(() => ({})).then(data => ({ ok: res.ok, data })))
                                .then(({ ok, data }) => {
                                    this.carrierSubmitting = false;
                                    if (!ok || !data.success || !data.carrier) {
                                        throw data;
                                    }

                                    const carrierId = String(data.carrier.id);
                                    const carrierName = data.carrier.carrier_company_name;

                                    document.querySelectorAll('.carrier-select').forEach(select => {
                                        const isIdSelect = select.getAttribute('name') === 'carrier_id';
                                        const optionValue = isIdSelect ? carrierId : carrierName;
                                        let option = Array.from(select.options).find(opt => opt.value === optionValue);

                                        if (!option) {
                                            option = new Option(carrierName, optionValue, true, true);
                                            if (!isIdSelect) {
                                                option.setAttribute('data-contact', data.carrier.phone_pakistan_office || data.carrier.contact_email || '');
                                                option.setAttribute('data-contract', data.carrier.ntn_scac_code || '');
                                            }
                                            select.appendChild(option);
                                        }

                                        option.selected = true;
                                        select.value = optionValue;

                                        if (window.jQuery && window.jQuery.fn.select2 && window.jQuery(select).data('select2')) {
                                            window.jQuery(select).trigger('change');
                                        } else {
                                            select.dispatchEvent(new Event('change', { bubbles: true }));
                                        }
                                    });

                                    [
                                        'quick_carrier_company_name',
                                        'quick_transport_modes',
                                        'quick_country_of_incorporation',
                                        'quick_contact_person',
                                        'quick_contact_email',
                                        'quick_payment_terms'
                                    ].forEach(id => {
                                        const input = document.getElementById(id);
                                        if (input) input.value = '';
                                    });

                                    this.showCarrierModal = false;
                                    alert('Carrier registered successfully!');
                                })
                                .catch(err => {
                                    this.carrierSubmitting = false;
                                    console.error(err);
                                    let errorMsg = 'Failed to register carrier. ';
                                    if (err.errors) {
                                        errorMsg += Object.values(err.errors).flat().join('\n');
                                    } else if (err.message) {
                                        errorMsg += err.message;
                                    }
                                    alert(errorMsg);
                                });
                        }
                    }">
                    @csrf
                    @method('POST')

                    {{-- Child Booking Toggle --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                @if ($isChildEdit)
                                    <input type="hidden" name="is_child" value="on">
                                @endif
                                <input type="checkbox" name="is_child" x-model="is_child" @disabled($isChildEdit)
                                    class="w-4 h-4 text-[#0078C8] rounded focus:ring-[#0078C8]">
                                <span class="text-sm font-bold text-blue-900 uppercase tracking-tight">Treat as Child
                                    Booking</span>
                            </label>

                            <div x-show="is_child" x-cloak class="flex-1 max-w-sm">
                                <select name="parent_id" x-model="parent_id" x-bind:required="is_child"
                                    @change="fetchParentDetails()"
                                    class="w-full px-3 py-2 bg-white border border-blue-200 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option value="">— Select Parent Booking —</option>
                                    @foreach ($parentBookings as $pb)
                                        <option value="{{ $pb->id }}"
                                            {{ (string) $childParentId === (string) $pb->id ? 'selected' : '' }}>
                                            {{ $pb->booking_number }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <p class="text-[10px] text-blue-600 mt-2">Child bookings inherit parties and core cargo details from
                            their parent for easier multi-stage tracking.</p>
                    </div>

                    {{-- Inland Child UI --}}
                    <fieldset x-bind:disabled="!is_child">
                        <div x-show="is_child" x-cloak>
                            <x-logistics.manage-booking.v2.modes.inland-child :booking="$booking" :child-booking="$editingChild"
                                :child-booking-number="$editingChildNumber" submit-label="Update Child Booking" :show-submit-button="false" />
                        </div>
                    </fieldset>

                    <fieldset x-bind:disabled="is_child">
                        {{-- Shipment Details --}}
                        <div x-show="!is_child">
                            <x-form.form-section title="Shipment Details" :required="true">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                                    <x-form.form-input name="booking_id" label="Booking ID (External)"
                                        placeholder="e.g. BKG-12345" :value="old(
                                            'booking_id',
                                            $booking->booking_number ?? ($booking->booking_id ?? ''),
                                        )" />

                                    <x-form.form-select name="booking_type" label="Booking Type" :required="false"
                                        x-bind:required="!is_child" placeholder="Select type..." :options="[
                                            'import' => 'Import',
                                            'export' => 'Export',
                                            'cross_trade' => 'Cross-Trade',
                                            'inland' => 'Inland',
                                            'return_shipment' => 'Return Shipment',
                                        ]"
                                        :selected="old('booking_type', $booking->booking_type ?? '')" />

                                    <x-form.form-select name="freight_mode" label="Freight Mode" :required="false"
                                        x-bind:required="!is_child" placeholder="Select mode..." :options="[
                                            'fcl_sea' => 'FCL Sea',
                                            'lcl_sea' => 'LCL Sea',
                                            'air' => 'Air Freight',
                                            'road_truck' => 'Road / Truck',
                                            'multimodal' => 'Multimodal',
                                        ]"
                                        :selected="old('freight_mode', $booking->freight_mode ?? '')" x-model="freight_mode" />

                                    <div x-show="freight_mode === 'multimodal'" x-cloak class="flex flex-col gap-1.5">
                                        <label for="multimodal_sub_mode"
                                            class="text-slate-700 text-sm font-semibold leading-5">
                                            Multimodal Sub-Mode <span class="text-red-500">*</span>
                                        </label>
                                        <select id="multimodal_sub_mode" name="multimodal_sub_mode"
                                            class="w-full py-[9px] px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent focus:outline-none bg-white"
                                            x-model="multimodal_sub_mode"
                                            x-bind:required="!is_child && freight_mode === 'multimodal'">
                                            <option value="" disabled>Select sub-mode...</option>
                                            <option value="fcl_sea"
                                                {{ old('multimodal_sub_mode', $booking->freight_sub_mode ?? ($booking->multimodal_sub_mode ?? '')) === 'fcl_sea' ? 'selected' : '' }}>
                                                FCL Sea</option>
                                            <option value="lcl_sea"
                                                {{ old('multimodal_sub_mode', $booking->freight_sub_mode ?? ($booking->multimodal_sub_mode ?? '')) === 'lcl_sea' ? 'selected' : '' }}>
                                                LCL Sea</option>
                                            <option value="air"
                                                {{ old('multimodal_sub_mode', $booking->freight_sub_mode ?? ($booking->multimodal_sub_mode ?? '')) === 'air' ? 'selected' : '' }}>
                                                Air Freight</option>
                                        </select>
                                    </div>

                                    <x-form.form-input name="origin_port" label="Origin Port / Airport" :required="false"
                                        x-bind:required="!is_child" placeholder="e.g. Karachi (KHI), Lahore (LHE)"
                                        :value="old('origin_port', $booking->origin_port ?? '')" />

                                    <x-form.form-input name="dest_port" label="Destination Port / Airport"
                                        :required="false" x-bind:required="!is_child"
                                        placeholder="e.g. Dubai (DXB), London Heathrow (LHR)" :value="old('dest_port', $booking->destination_port ?? '')" />

                                    <div x-show="!is_child">
                                        <x-form.form-input name="place_of_receipt" label="Place of Receipt"
                                            placeholder="Factory / warehouse address" :value="old('place_of_receipt', $booking->place_of_receipt ?? '')" />
                                    </div>

                                    <div x-show="!is_child">
                                        <x-form.form-input name="final_delivery_place" label="Final Delivery Place"
                                            placeholder="Buyer warehouse or port" :value="old(
                                                'final_delivery_place',
                                                $booking->final_delivery_place ?? '',
                                            )" />
                                    </div>

                                </div>
                            </x-form.form-section>
                        </div>

                        {{-- Parties --}}
                        <div x-show="!is_child">
                            <x-form.form-section title="Parties">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                                    <x-form.form-input name="shipper" label="Shipper / Exporter" :required="false"
                                        x-bind:required="!is_child" placeholder="Company name" :value="old('shipper', $booking->exporter ?? ($booking->shipper ?? ''))" />

                                    <x-form.form-input name="consignee" label="Consignee / Importer" :required="false"
                                        x-bind:required="!is_child" placeholder="Company name" :value="old('consignee', $booking->importer ?? ($booking->consignee ?? ''))" />

                                    <x-form.form-input name="notify_party" label="Notify Party" placeholder="Bank or agent"
                                        :value="old('notify_party', $booking->notify_party ?? '')" />

                                    <x-logistics.manage-booking.v2.freight-forwarder-select
                                        :selected="old(
                                            'freight_forwarder',
                                            $booking->agent ?? ($booking->freight_forwarder ?? ''),
                                        )" />

                                </div>
                            </x-form.form-section>
                        </div>

                        <div x-show="!is_child">
                            {{-- Dynamic Carrier & Equipment Sections --}}
                            <template
                                x-if="freight_mode === 'fcl_sea' || (freight_mode === 'multimodal' && multimodal_sub_mode === 'fcl_sea')">
                                <x-logistics.manage-booking.v2.modes.fcl-sea :booking="$booking" />
                            </template>

                            <template
                                x-if="freight_mode === 'lcl_sea' || (freight_mode === 'multimodal' && multimodal_sub_mode === 'lcl_sea')">
                                <x-logistics.manage-booking.v2.modes.lcl-sea :booking="$booking" />
                            </template>

                            <template
                                x-if="freight_mode === 'air' || (freight_mode === 'multimodal' && multimodal_sub_mode === 'air')">
                                <x-logistics.manage-booking.v2.modes.air :booking="$booking" />
                            </template>

                            <template x-if="freight_mode === 'road_truck'">
                                <x-logistics.manage-booking.v2.modes.truck :booking="$booking" />
                            </template>

                            <template
                                x-if="['fcl_sea', 'lcl_sea', 'air', 'road_truck'].indexOf(freight_mode) === -1 && freight_mode !== 'multimodal' && freight_mode !== ''">
                                <x-logistics.manage-booking.v2.modes.generic :booking="$booking" />
                            </template>
                        </div>

                        {{-- Schedule & Service --}}
                        <div x-show="!is_child">
                            <x-form.form-section title="Schedule & Service" :required="true">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
                                    <x-form.form-input name="etd" label="ETD (Estimated Departure)" :required="false"
                                        x-bind:required="!is_child" type="date" :value="old(
                                            'etd',
                                            $booking->estimated_time_departure
                                                ? \Carbon\Carbon::parse($booking->estimated_time_departure)->format(
                                                    'Y-m-d',
                                                )
                                                : ($booking->etd
                                                    ? \Carbon\Carbon::parse($booking->etd)->format('Y-m-d')
                                                    : ''),
                                        )" />


                                    <x-form.form-input name="delivery_date" label="Estimated Delivery Date"
                                        type="date" :value="old(
                                            'delivery_date',
                                            $booking->estimated_time_delivery
                                                ? \Carbon\Carbon::parse($booking->estimated_time_delivery)->format(
                                                    'Y-m-d',
                                                )
                                                : ($booking->delivery_date
                                                    ? \Carbon\Carbon::parse($booking->delivery_date)->format('Y-m-d')
                                                    : ''),
                                        )" />

                                    <x-form.form-select name="service_level" label="Service Level"
                                        placeholder="Select level..." :options="[
                                            'standard' => 'Standard',
                                            'priority' => 'Priority',
                                            'express' => 'Express',
                                        ]" :selected="old('service_level', $booking->service_level ?? 'standard')" />
                                </div>

                                {{-- Routing Legs removed as per request --}}
                            </x-form.form-section>
                        </div>

                        {{-- Cargo Details --}}
                        <div x-show="!is_child">
                            <x-form.form-section title="Cargo Details" :required="true">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                                    @php
                                        $currentHsCode = old(
                                            'hs_code',
                                            $booking->hsCode?->hs_code ?? ($booking->hs_code ?? ''),
                                        );
                                        $currentHsCodeModel = null;
                                        if (!empty($currentHsCode)) {
                                            $currentHsCodeModel = \App\Models\HsCode::where(
                                                'hs_code',
                                                $currentHsCode,
                                            )->first();
                                        }
                                    @endphp
                                    <div class="flex flex-col gap-1.5">
                                        <label for="hs_code" class="text-slate-700 text-sm font-semibold leading-5">
                                            Commodity / HS Code <span class="text-red-500">*</span>
                                        </label>
                                        <select id="hs_code" name="hs_code"
                                            class="ajax-hscode-select select2 w-full py-[9px] px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent focus:outline-none"
                                            x-bind:required="!is_child">
                                            @if ($currentHsCode)
                                                <option value="{{ $currentHsCode }}" data-description="{{ $currentHsCodeModel ? $currentHsCodeModel->description : '' }}" selected>
                                                    {{ $currentHsCode }}
                                                </option>
                                            @endif
                                        </select>
                                        @error('hs_code')
                                            <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <x-form.form-input name="cargo_description" label="Cargo Description"
                                        :required="true" x-bind:required="!is_child"
                                        placeholder="Brief description of goods" :value="old('cargo_description', $booking->cargo_description ?? '')" />

                                    <x-form.form-input name="gross_weight" label="Gross Weight (KG)" type="number"
                                        placeholder="e.g. 18000" :value="old('gross_weight', $booking->gross_weight ?? '')" />

                                    <x-form.form-input name="volume_cbm" label="Volume (CBM)" type="number"
                                        placeholder="e.g. 28.5" :value="old('volume_cbm', $booking->volume_cbm ?? '')" />

                                    <x-form.form-input name="cargo_value" label="Declared Cargo Value (USD)"
                                        placeholder="e.g. $48,200" :value="old(
                                            'cargo_value',
                                            $booking->declared_cargo_value ?? ($booking->cargo_value ?? ''),
                                        )" />

                                    <x-form.form-select name="dangerous_goods" label="Dangerous Goods?"
                                        placeholder="Select..." :options="[
                                            'no' => 'No',
                                            'imdg_1' => 'Yes — IMDG Class 1',
                                            'imdg_2' => 'Yes — IMDG Class 2',
                                            'imdg_other' => 'Yes — Other',
                                        ]" :selected="old('dangerous_goods', $dangerousSelected)"
                                        x-model="dangerous_goods" />

                                    <div x-show="dangerous_goods === 'imdg_other'" x-cloak>
                                        <x-form.form-input name="dangerous_goods_custom" label="Specify Dangerous Goods"
                                            placeholder="Enter dangerous goods details" :value="old('dangerous_goods_custom', $dangerousCustomVal)" />
                                    </div>

                                    <div class="md:col-span-2">
                                        <x-form.form-textarea name="special_instructions"
                                            label="Special Instructions / Remarks"
                                            placeholder="Temperature requirements, handling notes, insurance requirements..."
                                            :value="old(
                                                'special_instructions',
                                                $booking->remarks ?? ($booking->special_instructions ?? ''),
                                            )" :rows="3" />
                                    </div>

                                </div>
                            </x-form.form-section>
                        </div>

                        {{-- Freight & Payment --}}
                        <div x-show="!is_child">
                            <x-form.form-section title="Freight & Payment">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                                    <x-form.form-select name="incoterms" label="Incoterms" :required="true"
                                        x-bind:required="!is_child" placeholder="Select incoterms..." :options="[
                                            'EXW' => 'EXW',
                                            'FCA' => 'FCA',
                                            'FAS' => 'FAS',
                                            'FOB' => 'FOB',
                                            'CFR' => 'CFR',
                                            'CIF' => 'CIF',
                                            'CPT' => 'CPT',
                                            'CIP' => 'CIP',
                                            'DAP' => 'DAP',
                                            'DPU' => 'DPU',
                                            'DAT' => 'DAT',
                                            'DDP' => 'DDP',
                                        ]"
                                        :selected="old('incoterms', $booking->incoterms ?? '')" />

                                    <x-form.form-select name="freight_terms" label="Freight Terms"
                                        placeholder="Select terms..." :options="[
                                            'prepaid' => 'Prepaid',
                                            'collect' => 'Collect',
                                            'third_party' => 'Third Party',
                                        ]" :selected="old('freight_terms', $booking->freight_terms ?? 'prepaid')" />

                                    <x-form.form-input name="freight_rate" label="Quoted Freight Rate (USD)"
                                        type="number" step="0.01" min="0" placeholder="e.g. 1850" :value="old(
                                            'freight_rate',
                                            $booking->quoted_freight_rate ?? ($booking->freight_rate ?? ''),
                                        )" />

                                    <x-form.form-input name="currency_value" label="Currency Value (PKR per 1 USD)"
                                        type="number" step="0.000001" min="0.000001" placeholder="e.g. 280.000000"
                                        :value="old(
                                            'currency_value',
                                            $booking->lcPayments->firstWhere('entry_source', 'manage_booking')?->currency_value ?? '',
                                        )" />

                                    <x-form.form-select name="bank_instrument_type" label="Assign to Bank Instrument"
                                        placeholder="Select bank instrument..." :options="[
                                            'bank_contract' => 'Bank Contract',
                                            'lc' => 'LC',
                                            'open_account' => 'Open Account',
                                            'documentary_collection' => 'Documentary Collection',
                                        ]" :selected="$selectedBankInstrumentType" x-model="bank_instrument_type" />

                                    <x-form.form-input name="po_reference_number" label="PO Reference Number / Invoice Number"
                                        placeholder="e.g. PO-8812" :value="old('po_reference_number', $booking->po_reference_number ?? '')" />

                                    <div x-show="bank_instrument_type === 'lc'" x-cloak>
                                        <x-form.form-input name="lc_number" label="LC Number"
                                            placeholder="e.g. LC-2026-0041" :value="old('lc_number')"
                                            x-bind:disabled="bank_instrument_type !== 'lc'" />
                                    </div>

                                    <div x-show="bank_instrument_type === 'bank_contract'" x-cloak>
                                        <x-form.form-input name="bank_contract_number" label="Bank Contract Number"
                                            placeholder="e.g. BC-2026-0041" :value="old('bank_contract_number')"
                                            x-bind:disabled="bank_instrument_type !== 'bank_contract'" />
                                    </div>

                                    <div x-show="bank_instrument_type === 'documentary_collection'" x-cloak>
                                        <x-form.form-input name="documentary_number" label="Documentary Number"
                                            placeholder="e.g. DOC-2026-0041"
                                            :value="old('documentary_number', $booking->documentary_number ?? '')"
                                            x-bind:required="bank_instrument_type === 'documentary_collection'"
                                            x-bind:disabled="bank_instrument_type !== 'documentary_collection'" />
                                    </div>

                                </div>
                            </x-form.form-section>
                        </div>
                    </fieldset>

                    {{-- Trade Documents --}}
                    <div class="bg-white rounded-lg border border-black/10 overflow-hidden">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-800">Trade Documents</h3>
                                <p class="text-xs text-gray-400 mt-0.5">Manage documents for this booking — add new rows
                                    below or delete existing ones</p>
                            </div>
                            <span class="text-xs text-gray-400">{{ count($documents) }}
                                document{{ count($documents) !== 1 ? 's' : '' }}</span>
                        </div>

                        {{-- Existing documents table --}}
                        @if (count($documents) > 0)
                            @php
                                $statusPill = [
                                    'Received' => 'bg-green-50 text-green-600 border-green-100',
                                    'Pending' => 'bg-amber-50 text-amber-600 border-amber-100',
                                    'Missing' => 'bg-red-50 text-red-600 border-red-100',
                                    'Filed' => 'bg-teal-50 text-teal-600 border-teal-100',
                                    'In Progress' => 'bg-purple-50 text-purple-600 border-purple-100',
                                    'Not Required' => 'bg-gray-100 text-gray-400 border-gray-200',
                                ];
                            @endphp
                            <div class="overflow-x-auto border-b border-gray-100">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50/60 text-gray-400 border-b border-gray-100">
                                            <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider">
                                                Document Type</th>
                                            <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider">
                                                Description</th>
                                            <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider">
                                                Required For</th>
                                            <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider">File
                                            </th>
                                            <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider">Status
                                            </th>
                                            <th
                                                class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider text-right">
                                                Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100" id="existingDocsBody">
                                        @foreach ($documents as $doc)
                                            @php $pill = $statusPill[$doc->status] ?? 'bg-gray-50 text-gray-500 border-gray-200'; @endphp
                                            <tr id="doc-row-{{ $doc->id }}"
                                                class="hover:bg-gray-50/50 transition-colors">
                                                <td class="px-5 py-3 text-sm font-medium text-gray-900">
                                                    {{ $doc->document_type }}</td>
                                                <td class="px-5 py-3 text-sm text-gray-500">{{ $doc->description ?: '—' }}
                                                </td>
                                                <td class="px-5 py-3 text-sm text-gray-500">
                                                    {{ $doc->required_for ?: '—' }}</td>
                                                <td class="px-5 py-3 text-sm">
                                                    @if ($doc->file_path)
                                                        <a href="{{ asset('storage/' . $doc->file_path) }}"
                                                            target="_blank"
                                                            class="flex items-center gap-1.5 text-[#0078C8] hover:underline">
                                                            <i
                                                                class="fa-solid fa-file text-[10px]"></i>{{ $doc->original_filename }}
                                                        </a>
                                                    @else
                                                        <span class="text-gray-300 text-xs italic">No file</span>
                                                    @endif
                                                </td>
                                                <td class="px-5 py-3">
                                                    <span
                                                        class="inline-block whitespace-nowrap px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border {{ $pill }}">
                                                        {{ $doc->status }}
                                                    </span>
                                                </td>
                                                <td class="px-5 py-3 text-right">
                                                    <button type="button"
                                                        onclick="deleteExistingDoc({{ $doc->id }}, '{{ route('logistics.manage-booking.v2.documents.destroy', $doc->id) }}')"
                                                        class="w-7 h-7 flex items-center justify-center rounded-lg border border-red-100 text-red-400 hover:bg-red-50 transition-colors ml-auto">
                                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- Add new documents --}}
                        <div class="p-6 space-y-4" x-data="{
                            nextId: 0,
                            docs: [],
                            addDoc() { this.docs.push({ id: this.nextId++, document_type: '', description: '', required_for: '', status: 'Pending' }); },
                            removeDoc(i) { this.docs.splice(i, 1); },
                        }">
                            <template x-for="(doc, index) in docs" :key="doc.id">
                                <div class="bg-gray-50/60 rounded-lg border border-gray-100 overflow-hidden">
                                    <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 bg-white">
                                        <div class="w-6 h-6 rounded-full bg-[#0078C8]/10 text-[#0078C8] text-xs font-bold flex items-center justify-center shrink-0"
                                            x-text="index + 1"></div>
                                        <select :name="'documents[' + index + '][document_type]'"
                                            x-model="doc.document_type"
                                            class="flex-1 px-3 py-2 bg-white rounded-lg border border-gray-200 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                                            <option value="">Select document type...</option>
                                            <option>Bill of Lading (BOL)</option>
                                            <option>Airway Bill (AWB)</option>
                                            <option>Commercial Invoice</option>
                                            <option>Packing List</option>
                                            <option>Certificate of Origin (CoO)</option>
                                            <option>Letter of Credit (LC)</option>
                                            <option>GD / Goods Declaration</option>
                                            <option>Insurance Certificate</option>
                                            <option>Fumigation Certificate</option>
                                            <option>Phytosanitary Certificate</option>
                                            <option>Other</option>
                                        </select>
                                        <button type="button" @click="removeDoc(index)"
                                            class="w-7 h-7 flex items-center justify-center rounded-lg border border-red-100 text-red-400 hover:bg-red-50 hover:border-red-200 transition-colors shrink-0">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4">
                                        <div>
                                            <label class="text-xs font-medium text-gray-600 mb-1.5 block">Status</label>
                                            <select :name="'documents[' + index + '][status]'" x-model="doc.status"
                                                class="w-full px-3 py-2.5 bg-white rounded-lg border border-gray-200 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                                                <option value="Pending">Pending</option>
                                                <option value="Received">Received</option>
                                                <option value="Missing">Missing</option>
                                                <option value="Filed">Filed</option>
                                                <option value="In Progress">In Progress</option>
                                                <option value="Not Required">Not Required</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label
                                                class="text-xs font-medium text-gray-600 mb-1.5 block">Description</label>
                                            <input type="text" :name="'documents[' + index + '][description]'"
                                                x-model="doc.description" placeholder="Brief description..."
                                                class="w-full px-3 py-2.5 bg-white rounded-lg border border-gray-200 text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                                        </div>
                                        <div>
                                            <label class="text-xs font-medium text-gray-600 mb-1.5 block">Required
                                                For</label>
                                            <input type="text" :name="'documents[' + index + '][required_for]'"
                                                x-model="doc.required_for" placeholder="e.g. Customs clearance..."
                                                class="w-full px-3 py-2.5 bg-white rounded-lg border border-gray-200 text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20 focus:border-[#0078C8] transition">
                                        </div>
                                        <div class="md:col-span-3">
                                            <label class="text-xs font-medium text-gray-600 mb-1.5 block">File <span
                                                    class="text-gray-400 font-normal">(optional)</span></label>
                                            <label
                                                class="flex items-center gap-2.5 w-full px-3 py-2.5 bg-white rounded-lg border border-gray-200 border-dashed cursor-pointer hover:border-[#0078C8] hover:bg-blue-50/30 transition group">
                                                <i
                                                    class="fa-solid fa-paperclip text-gray-300 group-hover:text-[#0078C8] text-sm transition"></i>
                                                <span
                                                    class="file-label text-sm text-gray-400 group-hover:text-[#0078C8] transition truncate">Click
                                                    to upload file</span>
                                                <input type="file" :name="'documents[' + index + '][file]'"
                                                    @change="$el.previousElementSibling.textContent = $event.target.files[0]?.name ?? 'Click to upload file'"
                                                    class="hidden">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <button type="button" @click="addDoc()"
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-medium text-[#0078C8] border border-[#0078C8]/30 bg-blue-50/50 hover:bg-blue-50 rounded-lg transition-colors">
                                <i class="fa-solid fa-plus text-[10px]"></i> Add Document
                            </button>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="bg-white rounded-lg border border-black/10 px-6 py-4 flex items-center justify-end gap-3">
                        <a href="{{ route('logistics.manage-booking.v2.index') }}"
                            class="px-5 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-5 py-2 text-xs font-medium text-white bg-[#0078C8] hover:bg-[#006bb0] rounded-lg transition-colors flex items-center gap-2">
                            {{ $isChildEdit ? 'Update Child Booking' : 'Update Booking' }} <i
                                class="fa-solid fa-arrow-right text-[10px]"></i>
                        </button>
                    </div>

                    {{-- Barcode / Email Modal --}}
                    <div x-show="showBarcodeModal"
                        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                        x-cloak @keydown.escape.window="showBarcodeModal = false">

                        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col"
                            @click.away="showBarcodeModal = false">

                            {{-- Modal Header --}}
                            <div
                                class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Generate & Send Barcodes</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Preview barcodes and email them to the client
                                    </p>
                                </div>
                                <button type="button" @click="showBarcodeModal = false"
                                    class="text-gray-400 hover:text-gray-600 transition-colors">
                                    <i class="fa-solid fa-xmark text-xl"></i>
                                </button>
                            </div>

                            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                                {{-- Barcode Preview Grid --}}
                                <div>
                                    <label
                                        class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3 block">Barcode
                                        Preview (<span x-text="barcodeCount"></span> units)</label>
                                    <div id="barcodeContainer"
                                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 bg-gray-50 p-6 rounded-xl border border-dashed border-gray-200">
                                        <!-- Barcodes will be generated here -->
                                    </div>
                                </div>

                                {{-- Email Form --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-4">
                                        <x-form.form-input name="client_email" label="Client Email"
                                            placeholder="client@example.com" x-model="clientEmail" />
                                        <x-form.form-input name="email_subject" label="Email Subject"
                                            placeholder="Shipment Barcodes - [Commodity]"
                                            x-bind:value="'Shipment Barcodes - ' + barcodeCommodity" />
                                    </div>
                                    <div class="space-y-4">
                                        <x-form.form-textarea name="email_message" label="Message"
                                            placeholder="Please find the barcodes for your shipment..."
                                            :rows="4" />
                                    </div>
                                </div>
                            </div>

                            {{-- Modal Footer --}}
                            <div
                                class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3 bg-gray-50/50">
                                <button type="button" @click="showBarcodeModal = false"
                                    class="px-5 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                    Cancel
                                </button>
                                <button type="button"
                                    class="px-5 py-2 text-xs font-medium text-white bg-[#0078C8] hover:bg-[#006bb0] rounded-lg transition-colors flex items-center gap-2">
                                    <i class="fa-solid fa-paper-plane text-[10px]"></i> Send to Client
                                </button>
                            </div>
                        </div>
                    </div>
                    {{-- Carrier Registration Modal --}}
                    <div x-show="showCarrierModal"
                        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                        x-cloak @keydown.escape.window="showCarrierModal = false">

                        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col"
                            @click.away="showCarrierModal = false">

                            {{-- Modal Header --}}
                            <div
                                class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                        <i class="fa-solid fa-truck-moving text-[#0078C8]"></i> Register New Carrier
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Quickly register a carrier to use in the
                                        booking form
                                    </p>
                                </div>
                                <button type="button" @click="showCarrierModal = false"
                                    class="text-gray-400 hover:text-gray-600 transition-colors">
                                    <i class="fa-solid fa-xmark text-xl"></i>
                                </button>
                            </div>

                            {{-- Modal Body --}}
                            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                                <div>
                                    <label for="quick_carrier_company_name"
                                        class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                        Carrier Company Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="quick_carrier_company_name"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                        placeholder="e.g. Blue Ocean Shipping">
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="quick_transport_modes"
                                            class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                            Primary Transport Mode <span class="text-red-500">*</span>
                                        </label>
                                        <select id="quick_transport_modes"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none">
                                            <option value="" disabled selected>Select mode...</option>
                                            <option value="ocean_liner">Ocean Liner</option>
                                            <option value="air_freight">Air Freight</option>
                                            <option value="inland_trucking">Inland (Trucking)</option>
                                            <option value="rail_freight">Rail Freight</option>
                                            <option value="multimodal">Multimodal</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="quick_country_of_incorporation"
                                            class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                            Country of Incorporation <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="quick_country_of_incorporation"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                            placeholder="e.g. Pakistan">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="quick_contact_person"
                                            class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                            Contact Person <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="quick_contact_person"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                            placeholder="e.g. John Doe">
                                    </div>

                                    <div>
                                        <label for="quick_contact_email"
                                            class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                            Contact Email <span class="text-red-500">*</span>
                                        </label>
                                        <input type="email" id="quick_contact_email"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                            placeholder="e.g. john@blueocean.com">
                                    </div>
                                </div>

                                <div>
                                    <label for="quick_payment_terms"
                                        class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                        Payment Terms <span class="text-red-500">*</span>
                                    </label>
                                    <select id="quick_payment_terms"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none">
                                        <option value="" disabled selected>Select terms...</option>
                                        <option value="net_30">Net 30</option>
                                        <option value="net_45">Net 45</option>
                                        <option value="net_60">Net 60</option>
                                        <option value="advance">Advance</option>
                                        <option value="cod">COD</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Modal Footer --}}
                            <div
                                class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3 bg-gray-50/50">
                                <button type="button" @click="showCarrierModal = false"
                                    x-bind:disabled="carrierSubmitting"
                                    class="px-5 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    Cancel
                                </button>
                                <button type="button" @click="submitQuickCarrier()" x-bind:disabled="carrierSubmitting"
                                    class="px-5 py-2 text-xs font-medium text-white bg-[#0078C8] hover:bg-[#006bb0] rounded-lg transition-colors flex items-center gap-2 disabled:bg-gray-400 disabled:cursor-not-allowed">
                                    <template x-if="carrierSubmitting">
                                        <i class="fa-solid fa-spinner fa-spin text-[10px]"></i>
                                    </template>
                                    <template x-if="!carrierSubmitting">
                                        <i class="fa-solid fa-save text-[10px]"></i>
                                    </template>
                                    <span x-text="carrierSubmitting ? 'Saving...' : 'Save Carrier'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function deleteExistingDoc(id, url) {
                if (!confirm('Delete this document?')) return;
                fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                }).then(r => r.json()).then(function(d) {
                    if (d.success) {
                        var row = document.getElementById('doc-row-' + id);
                        if (row) row.remove();
                    }
                });
            }
        </script>
    @endpush
    </div>
@endsection
