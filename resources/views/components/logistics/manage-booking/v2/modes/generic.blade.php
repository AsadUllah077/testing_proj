@props(['booking' => null])

<div class="space-y-6">
    <x-form.form-section title="Carrier & Equipment" :required="true">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
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

            <x-form.form-select name="container_type" label="Container Type" placeholder="Select type..."
                :options="[
                    '20ft' => '20ft Standard',
                    '40ft' => '40ft Standard',
                    '40ft_hc' => '40ft High Cube',
                    '20ft_ref' => '20ft Reefer',
                    'open_top' => 'Open Top',
                    'flat_rack' => 'Flat Rack',
                ]" :selected="old('container_type', $booking->container_type ?? '')" />

            <x-form.form-input name="container_count" label="Number of Containers / Units" type="number"
                placeholder="e.g. 2" :value="old('container_count', $booking->container_count ?? '')" />

            <x-form.form-select name="service_level" label="Service Level" placeholder="Select level..."
                :options="[
                    'standard' => 'Standard',
                    'priority' => 'Priority',
                    'express' => 'Express',
                ]" :selected="old('service_level', $booking->service_level ?? 'standard')" />
        </div>
    </x-form.form-section>
</div>
