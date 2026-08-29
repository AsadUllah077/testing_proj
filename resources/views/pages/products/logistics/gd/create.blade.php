@extends('layouts.auth')

@section('title', 'File New GD')

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function () {
            const $hsCodeSelect = $('.ajax-hscode-select');

            if (!$hsCodeSelect.length) {
                return;
            }

            $hsCodeSelect.each(function () {
                const $select = $(this);

                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
            });

            $hsCodeSelect.select2({
                placeholder: 'Search Commodity / HS Code...',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '{{ route('logistics.manage-booking.v2.hs-codes.paginate') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function (data) {
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

            $hsCodeSelect.on('select2:select', function (e) {
                const data = e.params.data || {};
                const description = data.description || $(this).find('option:selected').data('description') || '';

                if (description) {
                    $('input[name="description"]').val(description).trigger('input').trigger('change');
                }
            });

            $hsCodeSelect.on('select2:clear', function () {
                $('input[name="description"]').val('').trigger('input').trigger('change');
            });
        });
    </script>
@endpush

@section('content')
    <div class="mt-4">
        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.gd') as $module)
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
                        <h1 class="text-gray-900 text-xl font-normal">File New Goods Declaration (GD)</h1>
                        <p class="text-xs text-gray-400 mt-0.5">Pakistan Customs — Federal Board of Revenue · WeBOC Filing
                        </p>
                    </div>
                    <x-common.back-button url="{{ route('logistics.gd.index') }}" />
                </div>

                <form method="POST" action_url="{{ route('logistics.gd.store') }}" enctype="multipart/form-data"
                    back_url="{{ route('logistics.gd.index') }}" id="formSubmit" class="space-y-4" x-data="{
                            declarationValueUsd: @js(old('delivery_term_value', 0)),
                            rate: @js(old('exchange_rate', 278.40)),
                            cdPct: @js(old('customs_duty_rate', 0)),
                            adPct: @js(old('additional_duty_rate', 3)),
                            stPct: @js(old('sales_tax_rate', 17)),
                            itPct: @js(old('income_tax_rate', 2)),
                            declarationValuePkr: 0,
                            cdAmt: 0,
                            adAmt: 0,
                            stAmt: 0,
                            itAmt: 0,
                            totalDuty: 0,
                            landedCost: 0,
                            unit: @js(old('unit', '')),
                            deliveryTerm: @js(old('delivery_term', '')),
                            dutyPaymentMode: @js(old('duty_payment_mode', '')),
                            insuranceType: @js(old('insurance_type', '')),
                            paymentDocuments: [{ name: '' }],
                            bookingsById: @js($bookingAutofill),
                            unitLabels: {
                                PCS: 'PCS',
                                KG: 'KG',
                                MT: 'MT',
                                CBM: 'CBM',
                                Ltr: 'Ltr',
                                Dozen: 'Dozen',
                            },
                            unitPlaceholders: {
                                PCS: 'e.g. 500 pieces',
                                KG: 'e.g. 1200 kg',
                                MT: 'e.g. 2.5 metric tons',
                                CBM: 'e.g. 18 cbm',
                                Ltr: 'e.g. 1000 liters',
                                Dozen: 'e.g. 40 dozen',
                            },
                            quantityLabel() {
                                if (this.unit === 'KG') {
                                    return 'Gross Weight (KG)';
                                }

                                return this.unit ? `Quantity (${this.unitLabels[this.unit] || this.unit})` : 'Quantity';
                            },
                            quantityPlaceholder() {
                                return this.unitPlaceholders[this.unit] || 'Select unit first';
                            },
                            deliveryTermLabel() {
                                return (this.deliveryTerm || '').toUpperCase() + ' Value (USD)';
                            },
                            deliveryTermPlaceholder() {
                                return this.deliveryTerm ? 'Enter ' + this.deliveryTerm.toUpperCase() + ' value' : '';
                            },
                            isThirdPartyPayment() {
                                return this.dutyPaymentMode === 'third_party';
                            },
                            paymentPrimaryLabel() {
                                return this.isThirdPartyPayment() ? 'Name' : 'Invoice Number';
                            },
                            paymentPrimaryPlaceholder() {
                                return this.isThirdPartyPayment() ? 'Enter third party name' : 'Enter invoice number';
                            },
                            paymentFileLabel() {
                                return this.isThirdPartyPayment() ? 'Upload Image' : 'Invoice Document';
                            },
                            insuranceValueLabel() {
                                return this.insuranceType === 'percentage' ? 'Insurance Percentage' : 'Insurance Value';
                            },
                            insuranceValuePlaceholder() {
                                return this.insuranceType === 'percentage' ? 'e.g. 2.5' : 'e.g. 1500';
                            },
                            addPaymentDocument() {
                                this.paymentDocuments.push({ name: '' });
                            },
                            removePaymentDocument(index) {
                                if (this.paymentDocuments.length > 1) {
                                    this.paymentDocuments.splice(index, 1);
                                }
                            },
                            applyBooking(bookingId) {
                                const booking = this.bookingsById[String(bookingId)] || this.bookingsById[bookingId];

                                if (!booking) {
                                    return;
                                }

                                this.setFieldValue('bl_awb_number', booking.booking_number, true);
                                this.setFieldValue('importer', booking.importer, true);
                                this.setFieldValue('exporter', booking.exporter, true);
                                this.setFieldValue('country_of_origin', booking.country_of_origin, true);
                                this.setHsCode(booking.hs_code, booking.hs_description);
                                this.setFieldValue('description', booking.description || booking.hs_description, true);
                            },
                            setFieldValue(name, value, allowBlank = false) {
                                const field = document.querySelector(`[name='${name}']`);

                                if (!field || ((!value && value !== 0) && !allowBlank)) {
                                    return;
                                }

                                field.value = value || '';
                                field.dispatchEvent(new Event('input', { bubbles: true }));
                                field.dispatchEvent(new Event('change', { bubbles: true }));
                            },
                            setHsCode(hsCode, description = '') {
                                const hsCodeValue = hsCode || '';

                                if (!window.jQuery || !$('#hs_code').length) {
                                    this.setFieldValue('hs_code', hsCodeValue, true);
                                    return;
                                }

                                const $hsCode = $('#hs_code');

                                if (!hsCodeValue) {
                                    $hsCode.val(null).trigger('change');
                                    return;
                                }

                                const $existingOption = $hsCode.find('option').filter(function () {
                                    return this.value === hsCodeValue;
                                });

                                if ($existingOption.length) {
                                    $existingOption.prop('selected', true).attr('data-description', description || '');
                                } else {
                                    const option = new Option(hsCodeValue, hsCodeValue, true, true);
                                    option.dataset.description = description || '';
                                    $hsCode.append(option);
                                }

                                $hsCode.val(hsCodeValue).trigger('change');
                            },
                            number(value) {
                                const parsed = Number.parseFloat(value);

                                return Number.isFinite(parsed) ? parsed : 0;
                            },
                            calc() {
                                const declarationValue = this.number(this.declarationValueUsd);
                                const exchangeRate = this.number(this.rate);
                                this.declarationValuePkr = declarationValue * exchangeRate;
                                this.cdAmt = this.declarationValuePkr * (this.number(this.cdPct) / 100);
                                this.adAmt = this.declarationValuePkr * (this.number(this.adPct) / 100);
                                this.stAmt = (this.declarationValuePkr + this.cdAmt + this.adAmt) * (this.number(this.stPct) / 100);
                                this.itAmt = this.declarationValuePkr * (this.number(this.itPct) / 100);
                                this.totalDuty = this.cdAmt + this.adAmt + this.stAmt + this.itAmt;
                                this.landedCost = this.declarationValuePkr + this.totalDuty;
                            },
                            fmt(n) { return 'PKR ' + this.number(n).toLocaleString('en-PK', { minimumFractionDigits: 0, maximumFractionDigits: 0 }); }
                        }" x-init="calc()">
                    @csrf

                    {{-- Declaration Details --}}
                    <x-form.form-section title="Declaration Details" :required="true">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                            <x-form.form-input name="gd_number" label="GD Number" :required="true"
                                placeholder="Enter GD number" :value="old('gd_number')" />

                            <x-form.form-select name="declaration_type" label="Declaration Type" :required="true"
                                placeholder="Select type..." :options="[
            'import' => 'Import',
            'export' => 'Export',
            'transit' => 'Transit',
            'temp_import' => 'Temporary Import',
        ]" :selected="old('declaration_type')" />

                            <x-form.form-select name="status" label="GD Status" :required="true" :options="[
                                'Draft' => 'Draft',
                                'Filed' => 'Filed',
                                'Approved' => 'Approved',
                                'Cleared' => 'Cleared',
                                'On Hold' => 'On Hold',
                                'Rejected' => 'Rejected',
                            ]" :selected="old('status', 'Draft')" />

                            <x-form.form-select name="collectorate" label="Collectorate"
                                placeholder="Select collectorate..." :options="[
                                                    'khi_pq' => 'Karachi (Port Qasim)',
                                                    'khi_kpt' => 'Karachi (KPT)',
                                                    'gwadar' => 'Gwadar Port',

                                                    'lhe' => 'Lahore Dry Port',
                                                    'isb' => 'Islamabad Dry Port',
                                                    'rwp' => 'Rawalpindi Dry Port',
                                                    'fsd' => 'Faisalabad Dry Port',
                                                    'skt' => 'Sialkot Dry Port',
                                                    'mux' => 'Multan Dry Port',
                                                    'pew' => 'Peshawar Dry Port',
                                                    'qta' => 'Quetta Dry Port',
                                                    'hyd' => 'Hyderabad Dry Port',
                                                    'suk' => 'Sukkur Dry Port',

                                                    'torkham' => 'Torkham Border Station',
                                                    'chaman' => 'Chaman Border Station',
                                                    'taftan' => 'Taftan Border Station',
                                                    'wagah' => 'Wagah Border Station',
                                                    'sust' => 'Sust Dry Port',
                                                ]"
                                :selected="old('collectorate')" />

                            <div class="flex flex-col gap-1.5">
                                <label class="text-slate-700 text-sm font-semibold leading-5">Linked Booking</label>
                                <select id="booking_v2_id" name="booking_v2_id" @change="applyBooking($event.target.value)"
                                    class="w-full px-3 py-2.5 bg-white rounded-lg border border-slate-300 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                    <option value="">— None —</option>
                                    @foreach ($bookings as $b)
                                        <option value="{{ $b->id }}" {{ old('booking_v2_id') == $b->id ? 'selected' : '' }}>
                                            {{ $b->booking_number }} · {{ $b->origin_port }} → {{ $b->destination_port }}
                                            @if ($b->cargo_description)
                                                · {{ Str::limit($b->cargo_description, 30) }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <x-form.form-input name="bl_awb_number" label="Bill of Lading / AWB Number"
                                placeholder="e.g. MSCUAKR0289012" :value="old('bl_awb_number')" />

                        </div>
                    </x-form.form-section>

                    {{-- Importer & Supplier --}}
                    <x-form.form-section title="Importer & Supplier">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                            <x-form.form-input name="importer" label="Importer Name" :required="true"
                                placeholder="Registered importer name" :value="old('importer')" />

                            <x-form.form-input name="ntn_cnic" label="NTN / CNIC" placeholder="e.g. 1234567-8"
                                :value="old('ntn_cnic')" />

                            <x-form.form-input name="exporter" label="Supplier / Exporter Name"
                                placeholder="Foreign supplier name" :value="old('exporter')" />

                            <x-form.form-select name="country_of_origin" label="Country of Origin"
                                placeholder="Select country..." :options="config('countries')"
                                :selected="old('country_of_origin')" />

                        </div>
                    </x-form.form-section>

                    {{-- Cargo & HS Classification --}}
                    <x-form.form-section title="Cargo & HS Classification">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                            @php
                                $selectedHsCode = old('hs_code');
                            @endphp
                            <div class="flex flex-col gap-1.5">
                                <label for="hs_code" class="text-slate-700 text-sm font-semibold leading-5">
                                    Commodity / HS Code <span class="text-red-500">*</span>
                                </label>
                                <select id="hs_code" name="hs_code"
                                    class="ajax-hscode-select select2 w-full py-[9px] px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent focus:outline-none"
                                    required>
                                    @if ($selectedHsCode)
                                        <option value="{{ $selectedHsCode }}" selected>{{ $selectedHsCode }}</option>
                                    @endif
                                </select>
                                @error('hs_code')
                                    <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>
                                @enderror
                            </div>

                            <x-form.form-input name="description" label="Cargo Description" :required="true"
                                placeholder="As per invoice" :value="old('description')" />

                            <x-form.form-select name="unit" label="Unit of Measure" placeholder="Select unit..." x-model="unit" :options="[
            'PCS' => 'PCS',
            'KG' => 'KG',
            'MT' => 'MT',
            'CBM' => 'CBM',
            'Ltr' => 'Ltr',
            'Dozen' => 'Dozen',
        ]" :selected="old('unit')" />

                            <div class="flex flex-col gap-1.5">
                                <label for="quantity" class="text-slate-700 text-sm font-semibold leading-5"
                                    x-text="quantityLabel()"></label>
                                <input type="text" id="quantity" name="quantity" value="{{ old('quantity') }}"
                                    x-bind:placeholder="quantityPlaceholder()"
                                    class="w-full px-3 py-2.5 bg-white rounded-lg border border-slate-300 text-base text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition{{ $errors->has('quantity') ? ' border-red-400' : '' }}">
                                @error('quantity')
                                    <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>
                                @enderror
                            </div>

                            <x-form.form-input name="net_weight" label="Net Weight (KG)" placeholder="e.g. 1100"
                                :value="old('net_weight')" />

                        </div>
                    </x-form.form-section>

                    {{-- Duties & Taxes (live calculator) --}}
                    <x-form.form-section title="Value & Duties (Auto-Calculated)">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                            <x-form.form-select name="delivery_term" label="Delivery Term"
                                placeholder="Select delivery term..." x-model="deliveryTerm" :options="[
                                    'cfr' => 'CFR',
                                    'fob' => 'FOB',
                                ]" :selected="old('delivery_term')" />

                            <div class="flex flex-col gap-1.5" x-show="deliveryTerm" x-cloak>
                                <label for="delivery_term_value" class="text-slate-700 text-sm font-semibold leading-5"
                                    x-text="deliveryTermLabel()"></label>
                                <input type="number" id="delivery_term_value" name="delivery_term_value"
                                    value="{{ old('delivery_term_value') }}" x-bind:placeholder="deliveryTermPlaceholder()"
                                    x-model="declarationValueUsd" @input="calc()" x-bind:disabled="!deliveryTerm"
                                    min="0" step="0.01"
                                    class="w-full px-3 py-2.5 bg-white rounded-lg border border-slate-300 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition{{ $errors->has('delivery_term_value') ? ' border-red-400' : '' }}">
                                @error('delivery_term_value')
                                    <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-slate-700 text-sm font-semibold leading-5">Exchange Rate (USD/PKR) <span
                                        class="text-red-500">*</span></label>
                                <input type="number" name="exchange_rate" x-model="rate" @input="calc()"
                                    value="{{ old('exchange_rate', '278.40') }}"
                                    class="w-full px-3 py-2.5 bg-white rounded-lg border border-slate-300 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                <p class="text-slate-400 text-xs">SBP interbank rate</p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-slate-700 text-sm font-semibold leading-5">Customs Duty %</label>
                                <input type="number" name="customs_duty_rate" x-model="cdPct" @input="calc()"
                                    placeholder="e.g. 15"
                                    class="w-full px-3 py-2.5 bg-white rounded-lg border border-slate-300 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                <input type="hidden" name="customs_duty" :value="cdAmt.toFixed(2)">
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-slate-700 text-sm font-semibold leading-5">Additional Customs Duty
                                    %</label>
                                <input type="number" name="additional_duty_rate" x-model="adPct" @input="calc()"
                                    class="w-full px-3 py-2.5 bg-white rounded-lg border border-slate-300 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                <input type="hidden" name="additional_duty" :value="adAmt.toFixed(2)">
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-slate-700 text-sm font-semibold leading-5">Sales Tax %</label>
                                <input type="number" name="sales_tax_rate" x-model="stPct" @input="calc()"
                                    class="w-full px-3 py-2.5 bg-white rounded-lg border border-slate-300 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                <input type="hidden" name="sales_tax" :value="stAmt.toFixed(2)">
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-slate-700 text-sm font-semibold leading-5">Income Tax / WHT %</label>
                                <input type="number" name="income_tax_rate" x-model="itPct" @input="calc()"
                                    class="w-full px-3 py-2.5 bg-white rounded-lg border border-slate-300 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                <input type="hidden" name="income_tax" :value="itAmt.toFixed(2)">
                            </div>

                            <input type="hidden" name="total_duty" :value="totalDuty.toFixed(2)">
                            <input type="hidden" name="landed_cost" :value="landedCost.toFixed(2)">

                        </div>

                        {{-- Live Results --}}
                        <div
                            class="mx-6 mb-6 grid grid-cols-2 md:grid-cols-4 gap-3 p-4 bg-gray-50 rounded-xl border border-gray-200">
                            <div class="text-center">
                                <p class="text-base font-black text-gray-800" x-text="fmt(declarationValuePkr)">PKR 0</p>
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-0.5">
                                    Customs Value (PKR)</p>
                            </div>
                            <div class="text-center">
                                <p class="text-base font-black text-[#0078C8]" x-text="fmt(cdAmt + adAmt)">PKR 0</p>
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-0.5">Customs
                                    + Add. Duty</p>
                            </div>
                            <div class="text-center">
                                <p class="text-base font-black text-red-500" x-text="fmt(totalDuty)">PKR 0</p>
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-0.5">Total
                                    Duty</p>
                            </div>
                            <div class="text-center">
                                <p class="text-base font-black text-green-600" x-text="fmt(landedCost)">PKR 0</p>
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mt-0.5">Landed
                                    Cost</p>
                            </div>
                        </div>
                    </x-form.form-section>

                    {{-- Payment & Remarks --}}
                    <x-form.form-section title="Payment & Remarks">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">

                            <x-form.form-select name="duty_payment_mode" label="Duty Payment Mode"
                                placeholder="Select mode..." x-model="dutyPaymentMode" :options="[
            '1link' => '1-Link / Bank Transfer',
            'pay_order' => 'Pay Order',
            'cash' => 'Cash (at Customs)',
            'third_party' => 'Third Party',
        ]" :selected="old('duty_payment_mode')" />

                            <x-form.form-input name="filed_date" label="Filing Date" type="date"
                                :value="old('filed_date')" />

                        </div>
                        <div class="px-6 pb-6 space-y-5" x-show="dutyPaymentMode" x-cloak>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex flex-col gap-1.5">
                                    <label for="duty_payment_invoice_number"
                                        class="text-slate-700 text-sm font-semibold leading-5"
                                        x-text="paymentPrimaryLabel()"></label>
                                    <input type="text" id="duty_payment_invoice_number"
                                        name="duty_payment_invoice_number" x-show="!isThirdPartyPayment()"
                                        x-bind:disabled="isThirdPartyPayment()"
                                        x-bind:placeholder="paymentPrimaryPlaceholder()"
                                        value="{{ old('duty_payment_invoice_number') }}"
                                        class="w-full px-3 py-2.5 bg-white rounded-lg border border-slate-300 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                    <input type="text" id="third_party_name" name="third_party_name"
                                        x-show="isThirdPartyPayment()" x-bind:disabled="!isThirdPartyPayment()"
                                        x-bind:placeholder="paymentPrimaryPlaceholder()"
                                        value="{{ old('third_party_name') }}"
                                        class="w-full px-3 py-2.5 bg-white rounded-lg border border-slate-300 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label for="invoice_document"
                                        class="text-slate-700 text-sm font-semibold leading-5"
                                        x-text="paymentFileLabel()"></label>
                                    <input type="file" id="invoice_document" name="invoice_document"
                                        accept=".pdf,.jpg,.jpeg,.png,.webp,image/*,application/pdf"
                                        class="w-full px-3 py-2.5 bg-white rounded-lg border border-slate-300 text-sm text-gray-900 file:mr-3 file:px-3 file:py-1.5 file:rounded-md file:border-0 file:bg-blue-50 file:text-[#0078C8] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                </div>
                            </div>
                        </div>

                        <div class="px-6 pb-6 space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <x-form.form-select name="insurance_type" label="Insurance"
                                    placeholder="Select value or percentage..." x-model="insuranceType" :options="[
                                        'value' => 'Value',
                                        'percentage' => 'Percentage',
                                    ]" :selected="old('insurance_type')" />

                                <div class="flex flex-col gap-1.5" x-show="insuranceType" x-cloak>
                                    <label for="insurance_value" class="text-slate-700 text-sm font-semibold leading-5"
                                        x-text="insuranceValueLabel()"></label>
                                    <input type="number" id="insurance_value" name="insurance_value"
                                        value="{{ old('insurance_value') }}" min="0" step="0.01"
                                        x-bind:disabled="!insuranceType" x-bind:placeholder="insuranceValuePlaceholder()"
                                        class="w-full px-3 py-2.5 bg-white rounded-lg border border-slate-300 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-5 space-y-3">
                               

                                <template x-for="(document, index) in paymentDocuments" :key="index">
                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 bg-gray-50/70 border border-gray-100 rounded-lg p-4">
                                        <div class="md:col-span-5 flex flex-col gap-1.5">
                                            <label class="text-xs font-medium text-gray-600">Name</label>
                                            <input type="text" x-model="document.name"
                                                :name="'payment_documents[' + index + '][name]'"
                                                placeholder="Document name"
                                                class="w-full px-3 py-2.5 bg-white rounded-lg border border-slate-300 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                        </div>
                                        <div class="md:col-span-6 flex flex-col gap-1.5">
                                            <label class="text-xs font-medium text-gray-600">Upload Document</label>
                                            <input type="file" :name="'payment_documents[' + index + '][file]'"
                                                accept=".pdf,.jpg,.jpeg,.png,.webp,image/*,application/pdf"
                                                class="w-full px-3 py-2.5 bg-white rounded-lg border border-slate-300 text-sm text-gray-900 file:mr-3 file:px-3 file:py-1.5 file:rounded-md file:border-0 file:bg-blue-50 file:text-[#0078C8] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                        </div>
                                        <div class="md:col-span-1 flex items-end">
                                            <button type="button" @click="removePaymentDocument(index)"
                                                x-show="paymentDocuments.length > 1"
                                                class="w-full md:w-auto px-3 py-2.5 text-sm text-red-500 hover:text-red-600 transition">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                 <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-semibold text-slate-700">Additional Payment Documents</h3>
                                    <button type="button" @click="addPaymentDocument()"
                                        class="text-[#0078C8] hover:text-[#006bb0] text-sm font-semibold flex items-center gap-1.5">
                                        <i class="fa-solid fa-plus-circle"></i> Add More
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 pb-6">
                            <x-form.form-textarea name="remarks" label="Special Conditions / Remarks"
                                placeholder="SRO exemptions, concessionary duty claims, DTRE, bonded warehouse..."
                                :value="old('remarks')" :rows="3" />
                        </div>
                    </x-form.form-section>

                    <x-common.button url="{{ route('logistics.gd.index') }}" type="submit" />

                </form>
            </div>
        </div>
    </div>
@endsection
