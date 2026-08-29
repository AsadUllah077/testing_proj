{{--
    Shared form partial — used by both create.blade.php and edit.blade.php
    Pass $inland for edit mode, omit / null for create mode.
--}}

@php $isEdit = isset($inland) && $inland !== null; @endphp

<div class="bg-white rounded-lg border border-black/10 mb-2">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 p-6">

        {{-- Carrier / Zone / Routes / Transit --}}
        <div>
            <label for="">Select Carrier</label>
            <select name="carrier_id" id="carrier_id"
                class="select2 w-full bg-white py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                <option value="">Select Carrier</option>
                @foreach ($carriers as $carrier)
                    <option value="{{ $carrier->id }}" @selected(($inland->carrier_id ?? null) == $carrier->id)>
                        {{ $carrier->carrier_company_name }}
                    </option>
                @endforeach

            </select>
        </div>
        <x-form.form-input name="zone_route_description" label="Zone / Route Description" :required="true"
            placeholder="e.g. Inter-Province (Major)" :value="old('zone_route_description', $isEdit ? $inland->zone_route_description : '')" />

        <x-form.form-input name="example_routes" label="Example Routes" placeholder="e.g. KHI↔LHE, LHE↔ISB"
            :value="old('example_routes', $isEdit ? $inland->example_routes : '')" />

        <x-form.form-input name="transit_time" label="Transit Time" placeholder="e.g. 2–3 days" :value="old('transit_time', $isEdit ? $inland->transit_time : '')" />

        {{-- Weight-based Rates --}}
        <x-form.form-input name="rate_up_to_500kg" label="Rate: Up to 500 KG (PKR)" type="number"
            placeholder="e.g. 18000" :value="old('rate_up_to_500kg', $isEdit ? $inland->rate_up_to_500kg : '')" />

        <x-form.form-input name="rate_501_1000kg" label="Rate: 501–1,000 KG (PKR)" type="number"
            placeholder="e.g. 24000" :value="old('rate_501_1000kg', $isEdit ? $inland->rate_501_1000kg : '')" />

        <x-form.form-input name="rate_1001_5000kg" label="Rate: 1,001–5,000 KG (PKR)" type="number"
            placeholder="e.g. 35000" :value="old('rate_1001_5000kg', $isEdit ? $inland->rate_1001_5000kg : '')" />

        {{-- Container Rates --}}
        <x-form.form-input name="rate_20ft_container" label="Rate: 20FT Container (PKR)" type="number"
            placeholder="e.g. 140000" :value="old('rate_20ft_container', $isEdit ? $inland->rate_20ft_container : '')" />

        <x-form.form-input name="rate_40ft_container" label="Rate: 40FT Container (PKR)" type="number"
            placeholder="e.g. 185000" :value="old('rate_40ft_container', $isEdit ? $inland->rate_40ft_container : '')" />

        {{-- MOU Dates / Fuel / Notes --}}
        <x-form.form-input name="mou_effective_date" label="MOU Effective Date" type="date" :value="old('mou_effective_date', $isEdit ? $inland->mou_effective_date?->format('Y-m-d') : '')" />

        <x-form.form-input name="mou_expiry_date" label="MOU Expiry Date" type="date" :value="old('mou_expiry_date', $isEdit ? $inland->mou_expiry_date?->format('Y-m-d') : '')" />

        <x-form.form-select name="fuel_levy_clause" label="Fuel Levy Clause" placeholder="Select option..."
            :options="\App\Models\LogisticsInlandRateCard::FUEL_LEVY_CLAUSES" :selected="old('fuel_levy_clause', $isEdit ? $inland->fuel_levy_clause : 'included_in_above_rates')" />

        <div class="md:col-span-1">
            <x-form.form-input name="notes" label="Notes" placeholder="Special handling, DGR, perishables..."
                :value="old('notes', $isEdit ? $inland->notes : '')" />
        </div>

    </div>
</div>

{{-- Rate Preview Card --}}
<div class="bg-slate-50 border border-slate-200 rounded-xl p-5 mb-2">
    <div class="flex items-center gap-2 mb-4">
        <span class="text-green-600">🚛</span>
        <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Rate Matrix Preview</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead>
                <tr class="border-b border-slate-200">
                    <th class="py-2 px-3 text-left text-slate-500 font-semibold uppercase">Up to 500 KG</th>
                    <th class="py-2 px-3 text-left text-slate-500 font-semibold uppercase">501–1,000 KG</th>
                    <th class="py-2 px-3 text-left text-slate-500 font-semibold uppercase">1,001–5,000 KG</th>
                    <th class="py-2 px-3 text-left text-slate-500 font-semibold uppercase">20FT Container</th>
                    <th class="py-2 px-3 text-left text-slate-500 font-semibold uppercase">40FT Container</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="py-2 px-3 font-bold text-slate-800" id="prev_500">—</td>
                    <td class="py-2 px-3 font-bold text-slate-800" id="prev_1000">—</td>
                    <td class="py-2 px-3 font-bold text-slate-800" id="prev_5000">—</td>
                    <td class="py-2 px-3 font-bold text-[#0078C8]" id="prev_20ft">—</td>
                    <td class="py-2 px-3 font-bold text-[#0078C8]" id="prev_40ft">—</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
