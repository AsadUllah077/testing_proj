@props(['booking' => null])

@php
    $airRows = old(
        'air_details',
        $booking?->items
            ? $booking->items
                ->filter(fn($item) => $item->freight_mode === 'air' || $item->freight_sub_mode === 'air')
                ->map(fn($item) => [
                    'description' => $item->item_description,
                    'plates' => $item->quantity ?? $item->package ?? 1,
                    'weight' => $item->weight,
                    'weight_unit' => 'kg',
                    'length' => $item->length,
                    'width' => $item->width,
                    'height' => $item->height,
                    'dimension_unit' => $item->dimension_unit ?? 'cm',
                ])
                ->values()
                ->all()
            : []
    );

    if (empty($airRows)) {
        $airRows = [[
            'description' => '',
            'plates' => 1,
            'weight' => '',
            'weight_unit' => 'kg',
            'length' => '',
            'width' => '',
            'height' => '',
            'dimension_unit' => 'cm',
        ]];
    }
@endphp

<div class="space-y-6">
    <x-form.form-section title="Air Freight Carrier and Equipment" :required="true">
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

            <x-form.form-input name="plate_type" label="Plate / ULD Type" placeholder="e.g. PMC, AKE, PLA"
                :value="old('plate_type', $booking->plate_type ?? '')" />

            <div class="md:col-span-2">
                <div x-data="{
                    rows: @js($airRows),
                    addRow() { this.rows.push({ plates: 1, weight: '', weight_unit: 'kg', length: '', width: '', height: '', dimension_unit: 'cm', description: '' }) },
                    removeRow(index) { if (this.rows.length > 1) this.rows.splice(index, 1) },
                    getTotalPlates() {
                        return this.rows.reduce((acc, row) => acc + (parseInt(row.plates) || 0), 0);
                    }
                }" class="space-y-6">

                    <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                        <label class="text-xs font-bold text-gray-400 uppercase tracking-widest">Plate / Cargo
                            Details</label>
                    </div>

                    <template x-for="(row, index) in rows" :key="index">
                        <div
                            class="space-y-3 bg-gray-50/30 p-5 rounded-2xl border border-gray-100 relative group transition-all hover:shadow-sm">

                            {{-- Item Description --}}
                            <div class="w-full">
                                <label
                                    class="text-[10px] font-bold text-gray-400 uppercase mb-1 block tracking-tight">Items
                                    Description</label>
                                <input type="text" :name="'air_details[' + index + '][description]'"
                                    x-model="row.description"
                                    class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-4 focus:ring-[#0078C8]/5 focus:border-[#0078C8] outline-none transition-all placeholder:text-gray-300"
                                    placeholder="e.g. Sensitive Electronics - Pallet A">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                {{-- Plates Count --}}
                                <div class="md:col-span-2">
                                    <label
                                        class="text-[10px] font-bold text-gray-400 uppercase mb-1 block tracking-tight">Plates
                                        *</label>
                                    <input type="number" :name="'air_details[' + index + '][plates]'" x-model="row.plates"
                                        class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-4 focus:ring-[#0078C8]/5 focus:border-[#0078C8] outline-none transition-all"
                                        placeholder="1">
                                </div>

                                {{-- Weight --}}
                                <div class="md:col-span-3">
                                    <label
                                        class="text-[10px] font-bold text-gray-400 uppercase mb-1 block tracking-tight">Weight
                                        *</label>
                                    <div class="flex">
                                        <input type="number" :name="'air_details[' + index + '][weight]'"
                                            x-model="row.weight"
                                            class="flex-1 px-4 py-2.5 text-sm border border-gray-200 rounded-l-xl focus:ring-4 focus:ring-[#0078C8]/5 focus:border-[#0078C8] outline-none transition-all"
                                            placeholder="0.00">
                                        <select :name="'air_details[' + index + '][weight_unit]'" x-model="row.weight_unit"
                                            class="w-20 px-2 py-2.5 text-xs border border-l-0 border-gray-200 rounded-r-xl bg-white focus:outline-none">
                                            <option value="kg">kg</option>
                                            <option value="lb">lb</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Dimensions --}}
                                <div class="md:col-span-6">
                                    <label
                                        class="text-[10px] font-bold text-gray-400 uppercase mb-1 block tracking-tight">Dimensions
                                        (L x W x H)</label>
                                    <div class="flex items-center gap-2">
                                        <input type="number" :name="'air_details[' + index + '][length]'"
                                            x-model="row.length" placeholder="L"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-4 focus:ring-[#0078C8]/5 focus:border-[#0078C8] outline-none">
                                        <span class="text-gray-300 text-xs">×</span>
                                        <input type="number" :name="'air_details[' + index + '][width]'"
                                            x-model="row.width" placeholder="W"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-4 focus:ring-[#0078C8]/5 focus:border-[#0078C8] outline-none">
                                        <span class="text-gray-300 text-xs">×</span>
                                        <input type="number" :name="'air_details[' + index + '][height]'"
                                            x-model="row.height" placeholder="H"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-4 focus:ring-[#0078C8]/5 focus:border-[#0078C8] outline-none">

                                        <select :name="'air_details[' + index + '][dimension_unit]'"
                                            x-model="row.dimension_unit"
                                            class="w-20 px-2 py-2.5 text-xs border border-gray-200 rounded-xl bg-white focus:outline-none ml-2">
                                            <option value="cm">cm</option>
                                            <option value="in">in</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Row Actions --}}
                                <div class="md:col-span-1 flex items-center justify-center gap-3 pb-2.5">
                                    <button type="button" @click="openBarcodeModal(row.plates, row.description)"
                                        class="text-[#0078C8] hover:text-[#006bb0] transition-colors"
                                        title="Generate Barcodes">
                                        <i class="fa-solid fa-barcode text-base"></i>
                                    </button>
                                    <button type="button" @click="removeRow(index)" x-show="rows.length > 1"
                                        class="text-red-400 hover:text-red-600 transition-colors">
                                        <i class="fa-solid fa-trash-can text-base"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                        <div class="flex items-center gap-4">
                            <p class="text-sm font-medium text-gray-600">Total plates: <span
                                    class="text-gray-900 font-bold" x-text="getTotalPlates()"></span></p>
                        </div>
                        <button type="button" @click="addRow()"
                            class="flex items-center gap-2 px-4 py-2 text-xs font-bold text-[#0078C8] bg-blue-50 hover:bg-blue-100 rounded-xl transition-all">
                            <i class="fa-solid fa-plus-circle"></i> ADD NEW PLATE
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </x-form.form-section>
</div>
