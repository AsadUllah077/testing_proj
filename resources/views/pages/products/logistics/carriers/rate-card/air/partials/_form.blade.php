{{--
    Shared form partial — used by both create.blade.php and edit.blade.php
    Pass $air for edit mode, omit for create mode.
--}}

@php $isEdit = isset($air) && $air !== null; @endphp

{{-- Live All-In Calculator --}}
<div class="mb-4 bg-slate-50 border border-slate-200 rounded-xl p-5">
    <div class="flex items-center gap-2 mb-4">
        <span class="text-orange-500">⚡</span>
        <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Live All-In / KG Calculator</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="bg-white border border-slate-200 rounded-lg p-3 text-center">
            <p class="text-xs text-slate-500 mb-1">Charge Rate</p>
            <p class="text-base font-semibold text-slate-800" id="calc_charge">
                ${{ $isEdit ? number_format($air->charge_rate_usd_kg, 2) : '0.00' }}
            </p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-3 text-center">
            <p class="text-xs text-slate-500 mb-1">Fuel (FSC)</p>
            <p class="text-base font-semibold text-slate-800" id="calc_fsc">
                ${{ $isEdit ? number_format($air->fuel_surcharge_fsc, 2) : '0.00' }}
            </p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-3 text-center">
            <p class="text-xs text-slate-500 mb-1">Security</p>
            <p class="text-base font-semibold text-slate-800" id="calc_security">
                ${{ $isEdit ? number_format($air->security_xray, 2) : '0.00' }}
            </p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-3 text-center">
            <p class="text-xs text-slate-500 mb-1">Handling</p>
            <p class="text-base font-semibold text-slate-800" id="calc_handling">
                ${{ $isEdit ? number_format($air->origin_handling, 2) : '0.00' }}
            </p>
        </div>
        <div class="bg-white border-2 border-blue-600 rounded-lg p-3 text-center">
            <p class="text-xs text-blue-600 font-semibold mb-1">All-In / KG</p>
            <p class="text-lg font-bold text-blue-700" id="calc_total">
                ${{ $isEdit ? number_format($air->all_in_per_kg, 2) : '0.00' }}
            </p>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg border border-black/10 mb-2">
    <div class="p-6 space-y-6">

        {{-- Row 1: Carrier / Origin / Destination / Via Hub --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

            <div>
                <label for="">Select Carrier</label>
                <select name="carrier_id" id="carrier_id"
                    class="select2 w-full bg-white py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                    <option value="">Select Carrier</option>
                    @foreach ($carriers as $carrier)
                        <option value="{{ $carrier->id }}" @selected(($selected ?? null) == $carrier->id)>
                            {{ $carrier->carrier_company_name }}
                        </option>
                    @endforeach

                </select>
            </div>
            <x-form.form-select name="origin_port" label="Origin Port (IATA)" :required="true"
                placeholder="Select origin..." :options="\App\Models\LogisticsAirRateCard::ORIGIN_PORTS" :selected="old('origin_port', $isEdit ? $air->origin_port : 'LHE')" />

            <x-form.form-select name="destination_port" label="Destination Port (IATA)" :required="true"
                placeholder="Select destination..." :options="\App\Models\LogisticsAirRateCard::DESTINATION_PORTS" :selected="old('destination_port', $isEdit ? $air->destination_port : 'IST')" />

            <x-form.form-input name="via_hub" label="Via Hub (optional)" placeholder="e.g. DXB" :value="old('via_hub', $isEdit ? $air->via_hub : '')" />

        </div>

        {{-- Row 2: Weight Breakpoint / Charge Rate / Min Charge --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

            <x-form.form-select name="weight_breakpoint" label="Weight Breakpoint" :required="true"
                placeholder="Select breakpoint..." :options="\App\Models\LogisticsAirRateCard::WEIGHT_BREAKPOINTS" :selected="old('weight_breakpoint', $isEdit ? $air->weight_breakpoint : '+500kg')" />

            <x-form.form-input name="charge_rate_usd_kg" label="Charge Rate (USD/KG)" type="number" step="0.0001"
                placeholder="e.g. 3.33" :required="true" :value="old('charge_rate_usd_kg', $isEdit ? $air->charge_rate_usd_kg : '')" />

            <x-form.form-input name="minimum_charge_usd" label="Minimum Charge (USD)" type="number" step="0.01"
                placeholder="e.g. 50 (M-rate only)" :value="old('minimum_charge_usd', $isEdit ? $air->minimum_charge_usd : '')" />

            {{-- spacer --}}
            <div></div>

        </div>

        {{-- Row 3: Surcharges --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

            <x-form.form-input name="fuel_surcharge_fsc" label="Fuel Surcharge (FSC) $/KG" type="number" step="0.0001"
                placeholder="e.g. 0.80" :value="old('fuel_surcharge_fsc', $isEdit ? $air->fuel_surcharge_fsc : '')" />

            <x-form.form-input name="security_xray" label="Security / XRAY $/KG" type="number" step="0.0001"
                placeholder="e.g. 0.15" :value="old('security_xray', $isEdit ? $air->security_xray : '')" />

            <x-form.form-input name="origin_handling" label="Origin Handling $/KG" type="number" step="0.0001"
                placeholder="e.g. 0.25" :value="old('origin_handling', $isEdit ? $air->origin_handling : '')" />

            <x-form.form-select name="source_document" label="Source Document" placeholder="Select source..."
                :options="\App\Models\LogisticsAirRateCard::SOURCE_DOCUMENTS" :selected="old('source_document', $isEdit ? $air->source_document : 'carrier_tariff_sheet')" />

        </div>

        {{-- Row 4: Validity / Notes --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

            <x-form.form-input name="rate_valid_from" label="Rate Valid From" type="date" :value="old('rate_valid_from', $isEdit ? $air->rate_valid_from?->format('Y-m-d') : '')" />

            <x-form.form-input name="rate_valid_to" label="Rate Valid To" type="date" :required="true"
                :value="old('rate_valid_to', $isEdit ? $air->rate_valid_to->format('Y-m-d') : '')" />

            <div class="md:col-span-2">
                <x-form.form-input name="notes" label="Notes"
                    placeholder="Pharma surcharge, perishable, DGR, special handling..." :value="old('notes', $isEdit ? $air->notes : '')" />
            </div>

        </div>

    </div>
</div>
