{{--
    Shared form partial for create & edit.
    Usage: @include('...._form', ['ocean' => $ocean ?? null])
--}}

@php $isEdit = isset($ocean) && $ocean !== null; @endphp

{{-- Source & Carrier --}}
<div class="mb-2">
    <x-form.form-section title="Source & Carrier" :required="true">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 p-6">

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


            <x-form.form-select name="rate_type" label="Rate Type" :required="true" placeholder="Select type..."
                :options="\App\Models\LogisticsOceanRateCard::RATE_TYPES" :selected="old('rate_type', $isEdit ? $ocean->rate_type : 'nac')" />

            <x-form.form-input name="nac_quote_reference" label="NAC / Quote Reference"
                placeholder="e.g. MAEU-NAC-Q2-2026" :value="old('nac_quote_reference', $isEdit ? $ocean->nac_quote_reference : '')" />

            <x-form.form-select name="source_document" label="Source Document" placeholder="Select source..."
                :options="\App\Models\LogisticsOceanRateCard::SOURCE_DOCUMENTS" :selected="old('source_document', $isEdit ? $ocean->source_document : 'booking_confirmation_email')" />

        </div>
    </x-form.form-section>
</div>

{{-- Route & Equipment --}}
<div class="mb-2">
    <x-form.form-section title="Route & Equipment">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 p-6">

            <x-form.form-select name="origin_port" label="Origin Port (UNLOCODE)" :required="true"
                placeholder="Select origin..." :options="\App\Models\LogisticsOceanRateCard::ORIGIN_PORTS" :selected="old('origin_port', $isEdit ? $ocean->origin_port : 'PKQCT')" />

            <x-form.form-select name="destination_port" label="Destination Port (UNLOCODE)" :required="true"
                placeholder="Select destination..." :options="\App\Models\LogisticsOceanRateCard::DESTINATION_PORTS" :selected="old('destination_port', $isEdit ? $ocean->destination_port : 'GBLGP')" />

            <x-form.form-select name="equipment" label="Equipment" :required="true" placeholder="Select equipment..."
                :options="\App\Models\LogisticsOceanRateCard::EQUIPMENT" :selected="old('equipment', $isEdit ? $ocean->equipment : '20ft_standard')" />

            <x-form.form-input name="transit_time_days" label="Transit Time (Days)" type="number" placeholder="e.g. 25"
                :value="old('transit_time_days', $isEdit ? $ocean->transit_time_days : '')" />

        </div>
    </x-form.form-section>
</div>

{{-- Rate Components --}}
<div class="mb-2">
    <x-form.form-section title="Rate Components — From Email / NAC (USD)">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 p-6">

            <x-form.form-input name="ocean_freight" label="Ocean Freight" type="number" step="0.01"
                placeholder="e.g. 1850" :required="true" :value="old('ocean_freight', $isEdit ? $ocean->ocean_freight : '')" />

            <div>
                <x-form.form-input name="baf" label="BAF — Bunker Adjustment Factor" type="number" step="0.01"
                    placeholder="e.g. 320" :value="old('baf', $isEdit ? $ocean->baf : '')" />
                <p class="text-xs text-gray-400 mt-1">From the 'Surcharges' section of the email</p>
            </div>

            <x-form.form-input name="caf" label="CAF — Currency Adjustment Factor" type="number" step="0.01"
                placeholder="e.g. 80" :value="old('caf', $isEdit ? $ocean->caf : '')" />

            <x-form.form-input name="eis_isps_other" label="EIS / ISPS / Other Surcharges" type="number" step="0.01"
                placeholder="e.g. 15" :value="old('eis_isps_other', $isEdit ? $ocean->eis_isps_other : '')" />

            <x-form.form-input name="thc_origin" label="THC — Origin Port" type="number" step="0.01"
                placeholder="e.g. 150" :value="old('thc_origin', $isEdit ? $ocean->thc_origin : '')" />

            <x-form.form-input name="thc_destination" label="THC — Destination Port" type="number" step="0.01"
                placeholder="e.g. 140" :value="old('thc_destination', $isEdit ? $ocean->thc_destination : '')" />

            <x-form.form-input name="free_days_demurrage" label="Free Days — Demurrage at Dest." type="number"
                placeholder="e.g. 7" :value="old('free_days_demurrage', $isEdit ? $ocean->free_days_demurrage : '')" />

            <x-form.form-input name="demurrage_rate_after_free" label="Demurrage Rate After Free Days (per Day)"
                type="number" step="0.01" placeholder="e.g. 80" :value="old('demurrage_rate_after_free', $isEdit ? $ocean->demurrage_rate_after_free : '')" />

            <x-form.form-input name="free_days_detention" label="Free Days — Detention" type="number"
                placeholder="e.g. 5" :value="old('free_days_detention', $isEdit ? $ocean->free_days_detention : '')" />

            <x-form.form-input name="detention_rate_after_free" label="Detention Rate After Free Days (per Day)"
                type="number" step="0.01" placeholder="e.g. 70" :value="old('detention_rate_after_free', $isEdit ? $ocean->detention_rate_after_free : '')" />

        </div>
    </x-form.form-section>
</div>

{{-- Live All-In Rate Calculator --}}
<div class="my-4 bg-slate-50 border border-slate-200 rounded-xl p-5">
    <div class="flex items-center gap-2 mb-4">
        <span class="text-orange-500 text-sm">lightning</span>
        <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Live All-In Rate Calculator</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
        <div class="bg-white border border-slate-200 rounded-lg p-3 text-center">
            <p class="text-xs text-slate-500 mb-1">Ocean Freight</p>
            <p class="text-base font-semibold text-slate-800" id="calc_ocean_freight">
                ${{ $isEdit ? number_format($ocean->ocean_freight) : '0' }}
            </p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-3 text-center">
            <p class="text-xs text-slate-500 mb-1">BAF</p>
            <p class="text-base font-semibold text-slate-800" id="calc_baf">
                ${{ $isEdit ? number_format($ocean->baf) : '0' }}
            </p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-3 text-center">
            <p class="text-xs text-slate-500 mb-1">CAF</p>
            <p class="text-base font-semibold text-slate-800" id="calc_caf">
                ${{ $isEdit ? number_format($ocean->caf) : '0' }}
            </p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-3 text-center">
            <p class="text-xs text-slate-500 mb-1">EIS / Other</p>
            <p class="text-base font-semibold text-slate-800" id="calc_eis">
                ${{ $isEdit ? number_format($ocean->eis_isps_other) : '0' }}
            </p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-3 text-center">
            <p class="text-xs text-slate-500 mb-1">THC (O+D)</p>
            <p class="text-base font-semibold text-slate-800" id="calc_thc">
                ${{ $isEdit ? number_format($ocean->thc_origin + $ocean->thc_destination) : '0' }}
            </p>
        </div>
        <div class="bg-white border-2 border-blue-600 rounded-lg p-3 text-center">
            <p class="text-xs text-blue-600 font-semibold mb-1">All-In Total</p>
            <p class="text-lg font-bold text-blue-700" id="calc_total">
                ${{ $isEdit ? number_format($ocean->all_in_total) : '0' }}
            </p>
        </div>
    </div>
</div>

{{-- Validity --}}
<div class="mb-2">
    <x-form.form-section title="Validity">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 p-6">

            <x-form.form-input name="valid_from" label="Valid From" type="date" :required="true"
                :value="old('valid_from', $isEdit ? $ocean->valid_from->format('Y-m-d') : '')" />

            <x-form.form-input name="valid_to" label="Valid To" type="date" :required="true"
                :value="old('valid_to', $isEdit ? $ocean->valid_to->format('Y-m-d') : '')" />

            <x-form.form-input name="agreed_volume_teu_month" label="Agreed Volume (TEU/Month)" type="number"
                placeholder="Commit volume under NAC" :value="old('agreed_volume_teu_month', $isEdit ? $ocean->agreed_volume_teu_month : '')" />

            <x-form.form-input name="remarks_gri_pss_notes" label="Remarks / GRI / PSS Notes"
                placeholder="e.g. GRI effective May 1, PSS waived this quarter" :value="old('remarks_gri_pss_notes', $isEdit ? $ocean->remarks_gri_pss_notes : '')" />

        </div>
    </x-form.form-section>
</div>
