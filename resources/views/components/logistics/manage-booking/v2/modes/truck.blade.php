@props(['booking' => null])

<div class="space-y-6">
    <x-form.form-section title="Road / Truck Carrier and Equipment" :required="true">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center justify-between">
                    <label for="carrier" class="text-slate-700 text-sm font-semibold leading-5">
                        Preferred Carrier <span class="text-red-500">*</span>
                    </label>
                    <button type="button" @click="showCarrierModal = true"
                        class="text-xs text-[#0078C8] hover:underline font-medium flex items-center gap-1">
                        <i class="fa-solid fa-plus-circle"></i> Carrier not found? Add New
                    </button>
                </div>
                <select id="carrier_id" name="carrier_id" x-init="$nextTick(() => { $($el).select2({ placeholder: 'Select carrier...' }) })"
                    class="carrier-select w-full h-10 px-3 py-2 bg-white rounded-lg border border-slate-300 text-base text-gray-900 appearance-none bg-no-repeat bg-right pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m19 9-7 7-7-7'/%3E%3C/svg%3E&quot;); background-size: 1.2rem; background-position: right 0.5rem center;">
                    <option value="" disabled
                        {{ old('carrier_id', $booking->carrier_id ?? '') === '' ? 'selected' : '' }}>Select carrier...
                    </option>
                    @php
                        $selectedCarrier = old('carrier_id', $booking->carrier_id ?? '');
                        $approvedCarriers = \App\Models\LogisticsV2Carriers::approved()
                            ->orderBy('carrier_company_name')
                            ->get();
                        $hasSelected = false;
                    @endphp
                    @foreach ($approvedCarriers as $c)
                        @if ($c->id == $selectedCarrier)
                            @php $hasSelected = true; @endphp
                        @endif
                        <option value="{{ $c->id }}" {{ $selectedCarrier == $c->id ? 'selected' : '' }}>
                            {{ $c->carrier_company_name }}
                        </option>
                    @endforeach
                    @if (!empty($selectedCarrier) && !$hasSelected)
                        <option value="{{ $selectedCarrier }}" selected>{{ $selectedCarrier }}</option>
                    @endif
                </select>
            </div>

            <x-form.form-select name="truck_type" label="Truck Type" placeholder="Select type..." :options="[
                'pickup' => 'Pickup (1.5 Ton)',
                'canter' => 'Canter (3.5 Ton)',
                'curtain_side' => 'Curtain Side (7.5 - 10 Ton)',
                'flatbed' => 'Flatbed Trailer',
                'reefer_truck' => 'Reefer / Temperature Controlled',
                'lowboy' => 'Lowboy / Heavy Haul',
                'container_carrier' => 'Container Carrier',
            ]"
                :selected="old('truck_type', $booking->truck_type ?? '')" />

            <x-form.form-input name="number_of_trucks" label="Number of Trucks" type="number" placeholder="1"
                :value="old('number_of_trucks', $booking->number_of_trucks ?? 1)" />
        </div>
    </x-form.form-section>
</div>
