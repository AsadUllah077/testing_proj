{{-- @props(['booking' => null])

@php
    $fclRows = old(
        'fcl_details',
        $booking?->items
            ? $booking->items
                ->filter(fn($item) => $item->freight_mode === 'fcl_sea' || $item->freight_sub_mode === 'fcl_sea')
                ->map(
                    fn($item) => [
                        'pallets' => $item->number_of_pallets,
                        'commodity' => $item->details_of_commodities,
                    ],
                )
                ->values()
                ->all()
            : [],
    );

    if (empty($fclRows)) {
        $fclRows = [['pallets' => '', 'commodity' => '']];
    }
@endphp

<div class="space-y-6">
    <x-form.form-section title="FCL Sea Carrier and Equipment" :required="true">
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
                <select id="carrier" name="carrier_id" x-init="$nextTick(() => { $($el).select2({ placeholder: 'Select carrier...' }) })"
                    class="select2 carrier-select w-full h-10 px-3 py-2 bg-white rounded-lg border border-slate-300 text-base text-gray-900 appearance-none bg-no-repeat bg-right pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m19 9-7 7-7-7'/%3E%3C/svg%3E&quot;); background-size: 1.2rem; background-position: right 0.5rem center;">
                    <option value="" disabled
                        {{ old('carrier_id', $booking->carrier_id ?? '') === '' ? 'selected' : '' }}>Select carrier...
                    </option>
                    @php
                        $selectedCarrier = old('carrier_id', $booking->carrier_id ?? '');
                        $approvedCarriers = \App\Models\LogisticsV2Carriers::approved()
                            ->where('transport_modes', 'ocean_liner')
                            ->orderBy('carrier_company_name')
                            ->get();
                        $approvedNames = $approvedCarriers->pluck('carrier_company_name')->toArray();
                        $hasSelected = false;
                    @endphp
                    @foreach ($approvedCarriers as $c)
                        @if ($c->id === $selectedCarrier)
                            @php $hasSelected = true; @endphp
                        @endif
                        <option value="{{ $c->id }}" {{ $selectedCarrier === $c->id ? 'selected' : '' }}>
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
                ]" :selected="old('container_type', $booking?->items?->first()?->container_type ?? '')" />

            <div class="md:col-span-2">
                <div x-data="{
                    rows: @js($fclRows),
                    addRow() { this.rows.push({ pallets: '', commodity: '' }) },
                    removeRow(index) { if (this.rows.length > 1) this.rows.splice(index, 1) }
                }" class="space-y-4">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-medium text-gray-600 uppercase tracking-wider">Pallet & Commodity
                            Details</label>
                        <button type="button" @click="addRow()"
                            class="text-[#0078C8] hover:text-[#006bb0] text-xs font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-plus-circle"></i> Add More
                        </button>
                    </div>

                    <template x-for="(row, index) in rows" :key="index">
                        <div
                            class="grid grid-cols-1 md:grid-cols-12 gap-4 bg-gray-50/50 p-4 rounded-xl border border-gray-100 relative group">
                            <div class="md:col-span-3">
                                <label class="text-[10px] text-gray-400 mb-1 block">Number of Pallets</label>
                                <input type="number" :name="'fcl_details[' + index + '][pallets]'"
                                    x-model="row.pallets"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="e.g. 10">
                            </div>
                            <div class="md:col-span-8">
                                <label class="text-[10px] text-gray-400 mb-1 block">Details of Commodities</label>
                                <input type="text" :name="'fcl_details[' + index + '][commodity]'"
                                    x-model="row.commodity"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="e.g. Cotton Yarn, Grade A">
                            </div>
                            <div class="md:col-span-1 flex items-end justify-center pb-1 gap-2">
                                <button type="button" @click="openBarcodeModal(row.pallets, row.commodity)"
                                    class="text-blue-500 hover:text-blue-700 transition-colors"
                                    title="Generate Barcodes">
                                    <i class="fa-solid fa-barcode text-sm"></i>
                                </button>
                                <button type="button" @click="removeRow(index)" x-show="rows.length > 1"
                                    class="text-red-400 hover:text-red-600 transition-colors">
                                    <i class="fa-solid fa-trash-can text-sm"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </x-form.form-section>
</div> --}}


{{-- @props(['booking' => null])

@php
    $fclRows = old(
        'fcl_details',
        $booking?->items
            ? $booking->items
                ->filter(fn($item) => $item->freight_mode === 'fcl_sea' || $item->freight_sub_mode === 'fcl_sea')
                ->map(
                    fn($item) => [
                        'package_type' => $item->package_type ?? '',
                        'container_number' => $item->container_number ?? '',
                        'qty' => $item->quantity ?? 1,
                        'reference_number' => $item->reference_number ?? '',
                        'pallets' => $item->number_of_pallets ?? 1,
                        'commodity' => $item->details_of_commodities ?? '',
                        'package_details' => $item->package_details ?? [],
                    ],
                )
                ->values()
                ->all()
            : [],
    );

    if (empty($fclRows)) {
        $fclRows = [[
            'package_type' => '',
            'container_number' => '',
            'qty' => 1,
            'reference_number' => '',
            'pallets' => 1,
            'commodity' => '',
            'package_details' => [],
        ]];
    }
@endphp

<div class="space-y-6">
    <x-form.form-section title="FCL Sea Carrier and Equipment" :required="true">
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
                <select id="carrier" name="carrier_id" x-init="$nextTick(() => { $($el).select2({ placeholder: 'Select carrier...' }) })"
                    class="select2 carrier-select w-full h-10 px-3 py-2 bg-white rounded-lg border border-slate-300 text-base text-gray-900 appearance-none bg-no-repeat bg-right pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m19 9-7 7-7-7'/%3E%3C/svg%3E&quot;); background-size: 1.2rem; background-position: right 0.5rem center;">
                    <option value="" disabled
                        {{ old('carrier_id', $booking->carrier_id ?? '') === '' ? 'selected' : '' }}>Select carrier...
                    </option>
                    @php
                        $selectedCarrier = old('carrier_id', $booking->carrier_id ?? '');
                        $approvedCarriers = \App\Models\LogisticsV2Carriers::approved()
                            ->where('transport_modes', 'ocean_liner')
                            ->orderBy('carrier_company_name')
                            ->get();
                        $approvedNames = $approvedCarriers->pluck('carrier_company_name')->toArray();
                        $hasSelected = false;
                    @endphp
                    @foreach ($approvedCarriers as $c)
                        @if ($c->id === $selectedCarrier)
                            @php $hasSelected = true; @endphp
                        @endif
                        <option value="{{ $c->id }}" {{ $selectedCarrier === $c->id ? 'selected' : '' }}>
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
                ]" :selected="old('container_type', $booking?->items?->first()?->container_type ?? '')" />

            <div class="md:col-span-2">
                <div x-data="{
                    rows: @js($fclRows),
                    addRow() { 
                        this.rows.push({ 
                            package_type: '', 
                            container_number: '', 
                            qty: 1, 
                            reference_number: '', 
                            pallets: 1, 
                            commodity: '',
                            package_details: []
                        }) 
                    },
                    removeRow(index) { 
                        if (this.rows.length > 1) this.rows.splice(index, 1) 
                    },
                    updatePackageDetails(index) {
                        const row = this.rows[index];
                        const qty = parseInt(row.qty) || 0;
                        const pallets = parseInt(row.pallets) || 0;
                        
                        // Generate package details based on pallet count
                        if (pallets > 0) {
                            const currentDetails = row.package_details || [];
                            const newDetails = [];
                            
                            for (let i = 0; i < pallets; i++) {
                                newDetails.push({
                                    pallet_number: i + 1,
                                    description: currentDetails[i]?.description || ''
                                });
                            }
                            
                            // Validate pallets not more than qty
                            if (pallets > qty && qty > 0) {
                                alert('Pallet count cannot exceed total quantity (' + qty + ')');
                                row.pallets = qty;
                                return this.updatePackageDetails(index);
                            }
                            
                            row.package_details = newDetails;
                        } else {
                            row.package_details = [];
                        }
                    },
                    updatePalletCount(index) {
                        const row = this.rows[index];
                        const qty = parseInt(row.qty) || 0;
                        let pallets = parseInt(row.pallets) || 0;
                        
                        // Validate pallets not more than qty
                        if (pallets > qty && qty > 0) {
                            alert('Pallet count cannot exceed total quantity (' + qty + ')');
                            row.pallets = qty;
                            pallets = qty;
                        }
                        
                        this.updatePackageDetails(index);
                    },
                    updateQty(index) {
                        const row = this.rows[index];
                        const qty = parseInt(row.qty) || 0;
                        const pallets = parseInt(row.pallets) || 0;
                        
                        // If pallets exceed qty, adjust pallets
                        if (pallets > qty && qty > 0) {
                            row.pallets = qty;
                        }
                        
                        // Update package details to match new qty
                        this.updatePackageDetails(index);
                    }
                }" class="space-y-6">
                    
                    <template x-for="(row, index) in rows" :key="index">
                        <div class="space-y-4 bg-gray-50/50 p-6 rounded-xl border border-gray-100">
                            <!-- Main Row Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                <!-- Package Type -->
                                <div class="md:col-span-2">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Package Type <span class="text-red-500">*</span></label>
                                    <select x-model="row.package_type" :name="'fcl_details[' + index + '][package_type]'"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none">
                                        <option value="">Select type...</option>
                                        <option value="container">Container</option>
                                        <option value="parcel">Parcel</option>
                                        <option value="small_parcel">Small Parcel</option>
                                        <option value="box">Box</option>
                                        <option value="small_box">Small Box</option>
                                        <option value="pallet">Pallet</option>
                                        <option value="crate">Crate</option>
                                        <option value="drum">Drum</option>
                                        <option value="barrel">Barrel</option>
                                        <option value="carton">Carton</option>
                                        <option value="bag">Bag</option>
                                        <option value="roll">Roll</option>
                                        <option value="bundle">Bundle</option>
                                        <option value="skid">Skid</option>
                                        <option value="bale">Bale</option>
                                        <option value="loose">Loose</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <!-- Container Number -->
                                <div class="md:col-span-2">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Container Number</label>
                                    <input type="text" x-model="row.container_number" :name="'fcl_details[' + index + '][container_number]'"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                        placeholder="e.g. MSCU-1234567">
                                </div>

                                <!-- Quantity -->
                                <div class="md:col-span-2">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Quantity <span class="text-red-500">*</span></label>
                                    <input type="number" x-model="row.qty" @input="updateQty(index)" 
                                        :name="'fcl_details[' + index + '][qty]'"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                        placeholder="Total quantity" min="1">
                                </div>

                                <!-- Reference Number -->
                                <div class="md:col-span-2">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Reference Number</label>
                                    <input type="text" x-model="row.reference_number" :name="'fcl_details[' + index + '][reference_number]'"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                        placeholder="e.g. PO-12345">
                                </div>

                                <!-- Pallet Count -->
                                <div class="md:col-span-2">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Pallet Count</label>
                                    <input type="number" x-model="row.pallets" @input="updatePalletCount(index)" 
                                        :name="'fcl_details[' + index + '][pallets]'"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                        placeholder="Number of pallets" min="0">
                                    <p class="text-[10px] text-gray-400 mt-1" x-show="row.qty > 0">
                                        <span x-text="'Max: ' + row.qty"></span>
                                    </p>
                                </div>

                                <!-- Remove Button -->
                                <div class="md:col-span-1 flex items-end justify-center pb-1">
                                    <button type="button" @click="removeRow(index)" x-show="rows.length > 1"
                                        class="text-red-400 hover:text-red-600 transition-colors">
                                        <i class="fa-solid fa-trash-can text-sm"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Commodity -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Commodity Details</label>
                                <input type="text" x-model="row.commodity" :name="'fcl_details[' + index + '][commodity]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="e.g. Cotton Yarn, Grade A">
                            </div>

                            <!-- Dynamic Package Details -->
                            <div x-show="row.package_details && row.package_details.length > 0" 
                                 class="mt-4 border-t border-gray-200 pt-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Package Details</h4>
                                <div class="space-y-2">
                                    <template x-for="(detail, detailIndex) in row.package_details" :key="detailIndex">
                                        <div class="grid grid-cols-12 gap-3 bg-white p-3 rounded-lg border border-gray-200">
                                            <div class="col-span-2">
                                                <label class="text-[10px] text-gray-400 block">Pallet #</label>
                                                <span class="text-sm font-medium text-gray-700" x-text="detail.pallet_number"></span>
                                            </div>
                                            <div class="col-span-9">
                                                <label class="text-[10px] text-gray-400 block">Description</label>
                                                <input type="text" x-model="detail.description" 
                                                    :name="'fcl_details[' + index + '][package_details][' + detailIndex + '][description]'"
                                                    class="w-full px-3 py-1 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                                    placeholder="Package description">
                                            </div>
                                            <div class="col-span-1 flex items-end justify-center pb-1">
                                                <button type="button" @click="generateBarcode(detail.description)"
                                                    class="text-blue-500 hover:text-blue-700 transition-colors" 
                                                    title="Generate Barcode">
                                                    <i class="fa-solid fa-barcode text-sm"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Hidden input for package details JSON -->
                            <input type="hidden" :name="'fcl_details[' + index + '][package_details_json]'" 
                                   :value="JSON.stringify(row.package_details)">
                        </div>
                    </template>

                    <!-- Add Row Button -->
                    <div class="flex justify-end">
                        <button type="button" @click="addRow()"
                            class="text-[#0078C8] hover:text-[#006bb0] text-sm font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-plus-circle"></i> Add Another Container
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </x-form.form-section>
</div>

<script>
    function generateBarcode(description) {
        // Simple barcode generation logic
        if (description) {
            const barcode = 'BAR-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6);
            alert('Barcode generated: ' + barcode + ' for: ' + description);
            // In a real implementation, you would open a barcode modal here
        } else {
            alert('Please enter a description first');
        }
    }
</script> --}}

{{-- @props(['booking' => null])

@php
    $fclRows = old(
        'fcl_details',
        $booking?->items
            ? $booking->items
                ->filter(fn($item) => $item->freight_mode === 'fcl_sea' || $item->freight_sub_mode === 'fcl_sea')
                ->map(
                    fn($item) => [
                        'package_type' => $item->package_type ?? '',
                        'container_number' => $item->container_number ?? '',
                        'qty' => $item->quantity ?? 1,
                        'reference_number' => $item->reference_number ?? '',
                        'pallets' => $item->number_of_pallets ?? 1,
                        'commodity' => $item->details_of_commodities ?? '',
                        'containers' => $item->containers ?? [],
                    ],
                )
                ->values()
                ->all()
            : [],
    );

    if (empty($fclRows)) {
        $fclRows = [[
            'package_type' => '',
            'container_number' => '',
            'qty' => 1,
            'reference_number' => '',
            'pallets' => 1,
            'commodity' => '',
            'containers' => [],
        ]];
    }
@endphp

<div class="space-y-6">
    <x-form.form-section title="FCL Sea Carrier and Equipment" :required="true">
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
                <select id="carrier" name="carrier_id" x-init="$nextTick(() => { $($el).select2({ placeholder: 'Select carrier...' }) })"
                    class="select2 carrier-select w-full h-10 px-3 py-2 bg-white rounded-lg border border-slate-300 text-base text-gray-900 appearance-none bg-no-repeat bg-right pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m19 9-7 7-7-7'/%3E%3C/svg%3E&quot;); background-size: 1.2rem; background-position: right 0.5rem center;">
                    <option value="" disabled
                        {{ old('carrier_id', $booking->carrier_id ?? '') === '' ? 'selected' : '' }}>Select carrier...
                    </option>
                    @php
                        $selectedCarrier = old('carrier_id', $booking->carrier_id ?? '');
                        $approvedCarriers = \App\Models\LogisticsV2Carriers::approved()
                            ->where('transport_modes', 'ocean_liner')
                            ->orderBy('carrier_company_name')
                            ->get();
                        $approvedNames = $approvedCarriers->pluck('carrier_company_name')->toArray();
                        $hasSelected = false;
                    @endphp
                    @foreach ($approvedCarriers as $c)
                        @if ($c->id === $selectedCarrier)
                            @php $hasSelected = true; @endphp
                        @endif
                        <option value="{{ $c->id }}" {{ $selectedCarrier === $c->id ? 'selected' : '' }}>
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
                ]" :selected="old('container_type', $booking?->items?->first()?->container_type ?? '')" />

            <div class="md:col-span-2">
                <div x-data="{
                    rows: @js($fclRows),
                    
                    addRow() { 
                        this.rows.push({ 
                            package_type: '', 
                            container_number: '', 
                            qty: 1, 
                            reference_number: '', 
                            pallets: 1, 
                            commodity: '',
                            containers: []
                        }) 
                    },
                    
                    removeRow(index) { 
                        if (this.rows.length > 1) this.rows.splice(index, 1) 
                    },
                    
                    // Update containers based on QTY
                    updateContainers(index) {
                        const row = this.rows[index];
                        const qty = parseInt(row.qty) || 0;
                        const currentContainers = row.containers || [];
                        const newContainers = [];
                        
                        // Keep existing container data where possible
                        for (let i = 0; i < qty; i++) {
                            const existingContainer = currentContainers[i] || {};
                            const palletCount = parseInt(existingContainer.palletCount) || row.pallets || 1;
                            
                            newContainers.push({
                                container_number: existingContainer.container_number || '',
                                palletCount: palletCount,
                                pallets: this.generatePalletDetails(palletCount, existingContainer.pallets || [])
                            });
                        }
                        
                        row.containers = newContainers;
                    },
                    
                    // Generate pallet details for a container
                    generatePalletDetails(palletCount, existingPallets = []) {
                        const pallets = [];
                        for (let i = 0; i < palletCount; i++) {
                            pallets.push({
                                pallet_number: i + 1,
                                description: existingPallets[i]?.description || ''
                            });
                        }
                        return pallets;
                    },
                    
                    // Update pallets for a specific container
                    updateContainerPallets(rowIndex, containerIndex) {
                        const row = this.rows[rowIndex];
                        const container = row.containers[containerIndex];
                        const palletCount = parseInt(container.palletCount) || 0;
                        
                        if (palletCount >= 0) {
                            const currentPallets = container.pallets || [];
                            const newPallets = [];
                            
                            for (let i = 0; i < palletCount; i++) {
                                newPallets.push({
                                    pallet_number: i + 1,
                                    description: currentPallets[i]?.description || ''
                                });
                            }
                            
                            container.pallets = newPallets;
                        }
                    },
                    
                    // Add a new container manually
                    addContainer(index) {
                        const row = this.rows[index];
                        row.qty = (parseInt(row.qty) || 0) + 1;
                        this.updateContainers(index);
                    },
                    
                    // Remove a container
                    removeContainer(rowIndex, containerIndex) {
                        const row = this.rows[rowIndex];
                        if (row.containers.length > 1) {
                            row.containers.splice(containerIndex, 1);
                            row.qty = row.containers.length;
                        }
                    },
                    
                    // Initialize containers when row is added or changed
                    initContainers(index) {
                        const row = this.rows[index];
                        if (!row.containers || row.containers.length === 0) {
                            this.updateContainers(index);
                        }
                    }
                }" 
                x-init="rows.forEach((row, index) => initContainers(index))"
                class="space-y-6">
                    
                    <template x-for="(row, index) in rows" :key="index">
                        <div class="space-y-4 bg-gray-50/50 p-6 rounded-xl border border-gray-100">
                            <!-- Main Row Header -->
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-700" x-text="'Container Group #' + (index + 1)"></h3>
                                <button type="button" @click="removeRow(index)" x-show="rows.length > 1"
                                    class="text-red-400 hover:text-red-600 transition-colors text-sm">
                                    <i class="fa-solid fa-trash-can"></i> Remove Group
                                </button>
                            </div>

                            <!-- Main Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                <!-- Package Type -->
                                <div class="md:col-span-2">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Package Type <span class="text-red-500">*</span></label>
                                    <select x-model="row.package_type" :name="'fcl_details[' + index + '][package_type]'"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none">
                                        <option value="">Select type...</option>
                                        <option value="container">Container</option>
                                        <option value="parcel">Parcel</option>
                                        <option value="small_parcel">Small Parcel</option>
                                        <option value="box">Box</option>
                                        <option value="small_box">Small Box</option>
                                        <option value="pallet">Pallet</option>
                                        <option value="crate">Crate</option>
                                        <option value="drum">Drum</option>
                                        <option value="barrel">Barrel</option>
                                        <option value="carton">Carton</option>
                                        <option value="bag">Bag</option>
                                        <option value="roll">Roll</option>
                                        <option value="bundle">Bundle</option>
                                        <option value="skid">Skid</option>
                                        <option value="bale">Bale</option>
                                        <option value="loose">Loose</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <!-- Reference Number -->
                                <div class="md:col-span-2">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Reference Number</label>
                                    <input type="text" x-model="row.reference_number" :name="'fcl_details[' + index + '][reference_number]'"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                        placeholder="e.g. PO-12345">
                                </div>

                                <!-- Quantity (Number of Containers) -->
                                <div class="md:col-span-2">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Number of Containers <span class="text-red-500">*</span></label>
                                    <input type="number" x-model="row.qty" @input="updateContainers(index)" 
                                        :name="'fcl_details[' + index + '][qty]'"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                        placeholder="Total containers" min="1">
                                </div>

                                <!-- Commodity -->
                                <div class="md:col-span-6">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Commodity Details</label>
                                    <input type="text" x-model="row.commodity" :name="'fcl_details[' + index + '][commodity]'"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                        placeholder="e.g. Cotton Yarn, Grade A">
                                </div>
                            </div>

                            <!-- Container Sections -->
                            <div class="mt-4 border-t border-gray-200 pt-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-medium text-gray-700">Containers</h4>
                                    <button type="button" @click="addContainer(index)"
                                        class="text-[#0078C8] hover:text-[#006bb0] text-xs font-semibold flex items-center gap-1.5">
                                        <i class="fa-solid fa-plus-circle"></i> Add Container
                                    </button>
                                </div>

                                <div class="space-y-4">
                                    <template x-for="(container, containerIndex) in row.containers" :key="containerIndex">
                                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-sm font-medium text-gray-600" x-text="'Container #' + (containerIndex + 1)"></span>
                                                <button type="button" @click="removeContainer(index, containerIndex)" 
                                                    x-show="row.containers.length > 1"
                                                    class="text-red-400 hover:text-red-600 text-xs">
                                                    <i class="fa-solid fa-times"></i> Remove
                                                </button>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                                <!-- Container Number -->
                                                <div class="md:col-span-3">
                                                    <label class="text-[10px] text-gray-400 mb-1 block">Container Number</label>
                                                    <input type="text" x-model="container.container_number" 
                                                        :name="'fcl_details[' + index + '][containers][' + containerIndex + '][container_number]'"
                                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                                        placeholder="e.g. MSCU-1234567">
                                                </div>

                                                <!-- Pallet Count for this Container -->
                                                <div class="md:col-span-2">
                                                    <label class="text-[10px] text-gray-400 mb-1 block">Pallet Count</label>
                                                    <input type="number" x-model="container.palletCount" 
                                                        @input="updateContainerPallets(index, containerIndex)"
                                                        :name="'fcl_details[' + index + '][containers][' + containerIndex + '][palletCount]'"
                                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                                        placeholder="Number of pallets" min="0">
                                                </div>
                                            </div>

                                            <!-- Pallet Details -->
                                            <div x-show="container.pallets && container.pallets.length > 0" 
                                                class="mt-3 pt-3 border-t border-gray-100">
                                                <label class="text-[10px] text-gray-400 mb-2 block">Package Descriptions</label>
                                                <div class="space-y-2">
                                                    <template x-for="(pallet, palletIndex) in container.pallets" :key="palletIndex">
                                                        <div class="flex items-center gap-3">
                                                            <span class="text-xs font-medium text-gray-500 w-20" x-text="'Pallet ' + pallet.pallet_number"></span>
                                                            <input type="text" x-model="pallet.description"
                                                                :name="'fcl_details[' + index + '][containers][' + containerIndex + '][pallets][' + palletIndex + '][description]'"
                                                                class="flex-1 px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                                                placeholder="Package description">
                                                            <button type="button" @click="generateBarcode(pallet.description)"
                                                                class="text-blue-500 hover:text-blue-700 transition-colors">
                                                                <i class="fa-solid fa-barcode text-sm"></i>
                                                            </button>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Add Row Button -->
                    <div class="flex justify-end">
                        <button type="button" @click="addRow()"
                            class="text-[#0078C8] hover:text-[#006bb0] text-sm font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-plus-circle"></i> Add Another Container Group
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </x-form.form-section>
</div>

<script>
    function generateBarcode(description) {
        if (description) {
            const barcode = 'BAR-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6);
            alert('Barcode generated: ' + barcode + ' for: ' + description);
        } else {
            alert('Please enter a description first');
        }
    }
</script> --}}


{{-- @props(['booking' => null])

@php
    $fclRows = old(
        'fcl_details',
        $booking?->items
            ? $booking->items
                ->filter(fn($item) => $item->freight_mode === 'fcl_sea' || $item->freight_sub_mode === 'fcl_sea')
                ->map(
                    fn($item) => [
                        'package_type' => $item->package_type ?? '',
                        'container_number' => $item->container_number ?? '',
                        'container_qty' => $item->container_qty ?? 1,
                        'reference_number' => $item->reference_number ?? '',
                        'pallet_count' => $item->pallet_count ?? 0,
                        'commodity' => $item->details_of_commodities ?? '',
                        'pallets' => $item->pallets ?? [],
                    ],
                )
                ->values()
                ->all()
            : [],
    );

    if (empty($fclRows)) {
        $fclRows = [[
            'package_type' => '',
            'container_number' => '',
            'container_qty' => 1,
            'reference_number' => '',
            'pallet_count' => 0,
            'commodity' => '',
            'pallets' => [],
        ]];
    }
@endphp

<div class="space-y-6">
    <x-form.form-section title="FCL Sea Carrier and Equipment" :required="true">
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
                <select id="carrier" name="carrier_id" x-init="$nextTick(() => { $($el).select2({ placeholder: 'Select carrier...' }) })"
                    class="select2 carrier-select w-full h-10 px-3 py-2 bg-white rounded-lg border border-slate-300 text-base text-gray-900 appearance-none bg-no-repeat bg-right pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m19 9-7 7-7-7'/%3E%3C/svg%3E&quot;); background-size: 1.2rem; background-position: right 0.5rem center;">
                    <option value="" disabled
                        {{ old('carrier_id', $booking->carrier_id ?? '') === '' ? 'selected' : '' }}>Select carrier...
                    </option>
                    @php
                        $selectedCarrier = old('carrier_id', $booking->carrier_id ?? '');
                        $approvedCarriers = \App\Models\LogisticsV2Carriers::approved()
                            ->where('transport_modes', 'ocean_liner')
                            ->orderBy('carrier_company_name')
                            ->get();
                        $approvedNames = $approvedCarriers->pluck('carrier_company_name')->toArray();
                        $hasSelected = false;
                    @endphp
                    @foreach ($approvedCarriers as $c)
                        @if ($c->id === $selectedCarrier)
                            @php $hasSelected = true; @endphp
                        @endif
                        <option value="{{ $c->id }}" {{ $selectedCarrier === $c->id ? 'selected' : '' }}>
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
                ]" :selected="old('container_type', $booking?->items?->first()?->container_type ?? '')" />

            <div class="md:col-span-2">
                <div x-data="{
                    rows: @js($fclRows),
                    
                    addRow() { 
                        this.rows.push({ 
                            package_type: '', 
                            container_number: '', 
                            container_qty: 1, 
                            reference_number: '', 
                            pallet_count: 0, 
                            commodity: '',
                            pallets: []
                        }) 
                    },
                    
                    removeRow(index) { 
                        if (this.rows.length > 1) this.rows.splice(index, 1) 
                    },
                    
                    // Update pallets based on pallet count
                    updatePallets(index) {
                        const row = this.rows[index];
                        const palletCount = parseInt(row.pallet_count) || 0;
                        const currentPallets = row.pallets || [];
                        const newPallets = [];
                        
                        // Keep existing descriptions where possible
                        for (let i = 0; i < palletCount; i++) {
                            newPallets.push({
                                pallet_number: i + 1,
                                description: currentPallets[i]?.description || ''
                            });
                        }
                        
                        row.pallets = newPallets;
                    },
                    
                    // Add container section based on container_qty
                    getContainerSections(index) {
                        const row = this.rows[index];
                        const qty = parseInt(row.container_qty) || 1;
                        const sections = [];
                        
                        for (let i = 0; i < qty; i++) {
                            sections.push({
                                container_number: row.container_number || '',
                                index: i + 1
                            });
                        }
                        
                        return sections;
                    }
                }" class="space-y-6">
                    
                    <template x-for="(row, index) in rows" :key="index">
                        <div class="space-y-4 bg-gray-50/50 p-6 rounded-xl border border-gray-100">
                            <!-- Header -->
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-700" x-text="'Container Group #' + (index + 1)"></h3>
                                <button type="button" @click="removeRow(index)" x-show="rows.length > 1"
                                    class="text-red-400 hover:text-red-600 transition-colors text-sm">
                                    <i class="fa-solid fa-trash-can"></i> Remove Group
                                </button>
                            </div>

                            <!-- Main Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                <!-- Package Type -->
                                <div class="md:col-span-2">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Package Type <span class="text-red-500">*</span></label>
                                    <select x-model="row.package_type" :name="'fcl_details[' + index + '][package_type]'"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none">
                                        <option value="">Select type...</option>
                                        <option value="container">Container</option>
                                        <option value="parcel">Parcel</option>
                                        <option value="small_parcel">Small Parcel</option>
                                        <option value="box">Box</option>
                                        <option value="small_box">Small Box</option>
                                        <option value="pallet">Pallet</option>
                                        <option value="crate">Crate</option>
                                        <option value="drum">Drum</option>
                                        <option value="barrel">Barrel</option>
                                        <option value="carton">Carton</option>
                                        <option value="bag">Bag</option>
                                        <option value="roll">Roll</option>
                                        <option value="bundle">Bundle</option>
                                        <option value="skid">Skid</option>
                                        <option value="bale">Bale</option>
                                        <option value="loose">Loose</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <!-- Container Number -->
                                <div class="md:col-span-2">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Container Number</label>
                                    <input type="text" x-model="row.container_number" :name="'fcl_details[' + index + '][container_number]'"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                        placeholder="e.g. MSCU-1234567">
                                </div>

                                <!-- Container QTY -->
                                <div class="md:col-span-2">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Container QTY <span class="text-red-500">*</span></label>
                                    <input type="number" x-model="row.container_qty" 
                                        :name="'fcl_details[' + index + '][container_qty]'"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                        placeholder="Number of containers" min="1">
                                </div>

                                <!-- Reference Number -->
                                <div class="md:col-span-2">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Reference Number</label>
                                    <input type="text" x-model="row.reference_number" :name="'fcl_details[' + index + '][reference_number]'"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                        placeholder="e.g. PO-12345">
                                </div>

                                <!-- Pallet Count -->
                                <div class="md:col-span-2">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Pallet Count</label>
                                    <input type="number" x-model="row.pallet_count" @input="setTimeout(() => updatePallets(index), 10)"
                                        :name="'fcl_details[' + index + '][pallet_count]'"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                        placeholder="Number of pallets" min="0">
                                </div>

                                <!-- Commodity -->
                                <div class="md:col-span-2">
                                    <label class="text-xs font-medium text-gray-600 mb-1 block">Commodity</label>
                                    <input type="text" x-model="row.commodity" :name="'fcl_details[' + index + '][commodity]'"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                        placeholder="e.g. Cotton Yarn">
                                </div>
                            </div>

                            <!-- Container Sections based on Container QTY -->
                            <div class="mt-4 border-t border-gray-200 pt-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-medium text-gray-700">Container Details</h4>
                                    <span class="text-xs text-gray-500" x-text="'Total: ' + (row.container_qty || 0) + ' containers'"></span>
                                </div>

                                <div class="space-y-4">
                                    <template x-for="(container, containerIndex) in getContainerSections(index)" :key="containerIndex">
                                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-sm font-medium text-gray-600" x-text="'Container #' + container.index"></span>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                                <!-- Container Number for this specific container -->
                                                <div class="md:col-span-3">
                                                    <label class="text-[10px] text-gray-400 mb-1 block">Container Number</label>
                                                    <input type="text" 
                                                        :name="'fcl_details[' + index + '][containers][' + containerIndex + '][container_number]'"
                                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                                        placeholder="e.g. MSCU-1234567">
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Pallet Details Section -->
                            <div x-show="row.pallets && row.pallets.length > 0" 
                                class="mt-4 border-t border-gray-200 pt-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-medium text-gray-700">Package Details</h4>
                                    <span class="text-xs text-gray-500" x-text="'Total: ' + row.pallets.length + ' pallets'"></span>
                                </div>

                                <div class="space-y-2">
                                    <template x-for="(pallet, palletIndex) in row.pallets" :key="palletIndex">
                                        <div class="flex items-center gap-3 bg-white p-3 rounded-lg border border-gray-100">
                                            <span class="text-xs font-medium text-gray-500 w-20" x-text="'Pallet ' + pallet.pallet_number"></span>
                                            <input type="text" x-model="pallet.description"
                                                :name="'fcl_details[' + index + '][pallets][' + palletIndex + '][description]'"
                                                class="flex-1 px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                                placeholder="Package description">
                                            <button type="button" @click="generateBarcode(pallet.description)"
                                                class="text-blue-500 hover:text-blue-700 transition-colors">
                                                <i class="fa-solid fa-barcode text-sm"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Add Row Button -->
                    <div class="flex justify-end">
                        <button type="button" @click="addRow()"
                            class="text-[#0078C8] hover:text-[#006bb0] text-sm font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-plus-circle"></i> Add Another Container Group
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </x-form.form-section>
</div>

<script>
    function generateBarcode(description) {
        if (description) {
            const barcode = 'BAR-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6);
            alert('Barcode generated: ' + barcode + ' for: ' + description);
        } else {
            alert('Please enter a description first');
        }
    }
</script> --}}


{{-- @props(['booking' => null])

@php
    $fclRows = old(
        'fcl_details',
        $booking?->items
            ? $booking->items
                ->filter(fn($item) => $item->freight_mode === 'fcl_sea' || $item->freight_sub_mode === 'fcl_sea')
                ->map(
                    fn($item) => [
                        'package_type' => $item->package_type ?? '',
                        'container_number' => $item->container_number ?? '',
                        'qty' => $item->qty ?? 1,
                        'pallet_count' => $item->pallet_count ?? 0,
                        'pallets' => $item->pallets ?? [],
                    ],
                )
                ->values()
                ->all()
            : [],
    );

    if (empty($fclRows)) {
        $fclRows = [[
            'package_type' => '',
            'container_number' => '',
            'qty' => 1,
            'pallet_count' => 0,
            'pallets' => [],
        ]];
    }
@endphp

<div class="space-y-6">
    <x-form.form-section title="FCL Sea Carrier and Equipment" :required="true">
        <div class="grid grid-cols-1 gap-6 p-6">
            <!-- Carrier Field -->
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
                <select id="carrier" name="carrier_id" x-init="$nextTick(() => { $($el).select2({ placeholder: 'Select carrier...' }) })"
                    class="select2 carrier-select w-full h-10 px-3 py-2 bg-white rounded-lg border border-slate-300 text-base text-gray-900 appearance-none bg-no-repeat bg-right pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m19 9-7 7-7-7'/%3E%3C/svg%3E&quot;); background-size: 1.2rem; background-position: right 0.5rem center;">
                    <option value="" disabled
                        {{ old('carrier_id', $booking->carrier_id ?? '') === '' ? 'selected' : '' }}>Select carrier...
                    </option>
                    @php
                        $selectedCarrier = old('carrier_id', $booking->carrier_id ?? '');
                        $approvedCarriers = \App\Models\LogisticsV2Carriers::approved()
                            ->where('transport_modes', 'ocean_liner')
                            ->orderBy('carrier_company_name')
                            ->get();
                        $approvedNames = $approvedCarriers->pluck('carrier_company_name')->toArray();
                        $hasSelected = false;
                    @endphp
                    @foreach ($approvedCarriers as $c)
                        @if ($c->id === $selectedCarrier)
                            @php $hasSelected = true; @endphp
                        @endif
                        <option value="{{ $c->id }}" {{ $selectedCarrier === $c->id ? 'selected' : '' }}>
                            {{ $c->carrier_company_name }}
                        </option>
                    @endforeach
                    @if (!empty($selectedCarrier) && !$hasSelected)
                        <option value="{{ $selectedCarrier }}" selected>{{ $selectedCarrier }}</option>
                    @endif
                </select>
            </div>

            <!-- Container Type -->
            <x-form.form-select name="container_type" label="Container Type" placeholder="Select type..."
                :options="[
                    '20ft' => '20ft Standard',
                    '40ft' => '40ft Standard',
                    '40ft_hc' => '40ft High Cube',
                    '20ft_ref' => '20ft Reefer',
                    'open_top' => 'Open Top',
                    'flat_rack' => 'Flat Rack',
                ]" :selected="old('container_type', $booking?->items?->first()?->container_type ?? '')" />

            <!-- Dynamic Rows -->
            <div x-data="{
                rows: @js($fclRows),
                
                addRow() { 
                    this.rows.push({ 
                        package_type: '', 
                        container_number: '', 
                        qty: 1, 
                        pallet_count: 0,
                        pallets: []
                    }) 
                },
                
                removeRow(index) { 
                    if (this.rows.length > 1) this.rows.splice(index, 1) 
                },
                
                updatePallets(index) {
                    const row = this.rows[index];
                    const palletCount = parseInt(row.pallet_count) || 0;
                    const currentPallets = row.pallets || [];
                    const newPallets = [];
                    
                    for (let i = 0; i < palletCount; i++) {
                        newPallets.push({
                            pallet_number: i + 1,
                            description: currentPallets[i]?.description || ''
                        });
                    }
                    
                    row.pallets = newPallets;
                }
            }" class="space-y-6">
                
                <template x-for="(row, index) in rows" :key="index">
                    <div class="bg-gray-50/50 p-6 rounded-xl border border-gray-100 space-y-4">
                        <!-- Header with Remove Button -->
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700" x-text="'Container Group #' + (index + 1)"></h3>
                            <button type="button" @click="removeRow(index)" x-show="rows.length > 1"
                                class="text-red-400 hover:text-red-600 transition-colors text-sm">
                                <i class="fa-solid fa-trash-can"></i> Remove
                            </button>
                        </div>

                        <!-- Main Fields - All in One Row -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Package Type -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Package Type <span class="text-red-500">*</span></label>
                                <select x-model="row.package_type" :name="'fcl_details[' + index + '][package_type]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none">
                                    <option value="">Select...</option>
                                    <option value="container">Container</option>
                                    <option value="parcel">Parcel</option>
                                    <option value="small_parcel">Small Parcel</option>
                                    <option value="box">Box</option>
                                    <option value="small_box">Small Box</option>
                                    <option value="pallet">Pallet</option>
                                    <option value="crate">Crate</option>
                                    <option value="drum">Drum</option>
                                    <option value="barrel">Barrel</option>
                                    <option value="carton">Carton</option>
                                    <option value="bag">Bag</option>
                                    <option value="roll">Roll</option>
                                    <option value="bundle">Bundle</option>
                                    <option value="skid">Skid</option>
                                    <option value="bale">Bale</option>
                                    <option value="loose">Loose</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <!-- Container Number -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Container Number</label>
                                <input type="text" x-model="row.container_number" :name="'fcl_details[' + index + '][container_number]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="e.g. MSCU-1234567">
                            </div>

                            <!-- QTY -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">QTY <span class="text-red-500">*</span></label>
                                <input type="number" x-model="row.qty" :name="'fcl_details[' + index + '][qty]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="Quantity" min="1">
                            </div>

                            <!-- Pallet Count -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Pallet Count</label>
                                <input type="number" x-model="row.pallet_count" @input="setTimeout(() => updatePallets(index), 10)"
                                    :name="'fcl_details[' + index + '][pallet_count]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="Number of pallets" min="0">
                            </div>
                        </div>

                        <!-- Pallet Details - Shows when pallet_count > 0 -->
                        <div x-show="row.pallets && row.pallets.length > 0" 
                            class="border-t border-gray-200 pt-4">
                            <div class="space-y-2">
                                <template x-for="(pallet, palletIndex) in row.pallets" :key="palletIndex">
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm font-medium text-gray-600 w-24" x-text="'Pallet ' + pallet.pallet_number"></span>
                                        <input type="text" x-model="pallet.description"
                                            :name="'fcl_details[' + index + '][pallets][' + palletIndex + '][description]'"
                                            class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                            placeholder="Package description">
                                        <button type="button" @click="generateBarcode(pallet.description)"
                                            class="text-blue-500 hover:text-blue-700 transition-colors">
                                            <i class="fa-solid fa-barcode"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Add Row Button -->
                <div class="flex justify-end">
                    <button type="button" @click="addRow()"
                        class="text-[#0078C8] hover:text-[#006bb0] text-sm font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-plus-circle"></i> Add Another Container Group
                    </button>
                </div>
            </div>
        </div>
    </x-form.form-section>
</div>

<script>
    function generateBarcode(description) {
        if (description) {
            const barcode = 'BAR-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6);
            alert('Barcode generated: ' + barcode + ' for: ' + description);
        } else {
            alert('Please enter a description first');
        }
    }
</script> --}}


{{-- @props(['booking' => null])

@php
    $fclRows = old(
        'fcl_details',
        $booking?->items
            ? $booking->items
                ->filter(fn($item) => $item->freight_mode === 'fcl_sea' || $item->freight_sub_mode === 'fcl_sea')
                ->map(
                    fn($item) => [
                        'package_type' => $item->package_type ?? '',
                        'container_number' => $item->container_number ?? '',
                        'qty' => $item->qty ?? 1,
                        'pallet_count' => $item->pallet_count ?? 0,
                        'pallets' => $item->pallets ?? [],
                    ],
                )
                ->values()
                ->all()
            : [],
    );

    if (empty($fclRows)) {
        $fclRows = [[
            'package_type' => '',
            'container_number' => '',
            'qty' => 1,
            'pallet_count' => 0,
            'pallets' => [],
        ]];
    }
@endphp

<div class="space-y-6">
    <x-form.form-section title="FCL Sea Carrier and Equipment" :required="true">
        <div class="grid grid-cols-1 gap-6 p-6">
            <!-- Carrier Field -->
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
                <select id="carrier" name="carrier_id" x-init="$nextTick(() => { $($el).select2({ placeholder: 'Select carrier...' }) })"
                    class="select2 carrier-select w-full h-10 px-3 py-2 bg-white rounded-lg border border-slate-300 text-base text-gray-900 appearance-none bg-no-repeat bg-right pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m19 9-7 7-7-7'/%3E%3C/svg%3E&quot;); background-size: 1.2rem; background-position: right 0.5rem center;">
                    <option value="" disabled
                        {{ old('carrier_id', $booking->carrier_id ?? '') === '' ? 'selected' : '' }}>Select carrier...
                    </option>
                    @php
                        $selectedCarrier = old('carrier_id', $booking->carrier_id ?? '');
                        $approvedCarriers = \App\Models\LogisticsV2Carriers::approved()
                            ->where('transport_modes', 'ocean_liner')
                            ->orderBy('carrier_company_name')
                            ->get();
                        $approvedNames = $approvedCarriers->pluck('carrier_company_name')->toArray();
                        $hasSelected = false;
                    @endphp
                    @foreach ($approvedCarriers as $c)
                        @if ($c->id === $selectedCarrier)
                            @php $hasSelected = true; @endphp
                        @endif
                        <option value="{{ $c->id }}" {{ $selectedCarrier === $c->id ? 'selected' : '' }}>
                            {{ $c->carrier_company_name }}
                        </option>
                    @endforeach
                    @if (!empty($selectedCarrier) && !$hasSelected)
                        <option value="{{ $selectedCarrier }}" selected>{{ $selectedCarrier }}</option>
                    @endif
                </select>
            </div>

            <!-- Container Type -->
            <x-form.form-select name="container_type" label="Container Type" placeholder="Select type..."
                :options="[
                    '20ft' => '20ft Standard',
                    '40ft' => '40ft Standard',
                    '40ft_hc' => '40ft High Cube',
                    '20ft_ref' => '20ft Reefer',
                    'open_top' => 'Open Top',
                    'flat_rack' => 'Flat Rack',
                ]" :selected="old('container_type', $booking?->items?->first()?->container_type ?? '')" />

            <!-- Dynamic Rows -->
            <div x-data="{
                rows: @js($fclRows),
                
                addRow() { 
                    this.rows.push({ 
                        package_type: '', 
                        container_number: '', 
                        qty: 1, 
                        pallet_count: 0,
                        pallets: []
                    }) 
                },
                
                removeRow(index) { 
                    if (this.rows.length > 1) this.rows.splice(index, 1) 
                },
                
                updatePallets(index) {
                    const row = this.rows[index];
                    const palletCount = parseInt(row.pallet_count) || 0;
                    const currentPallets = row.pallets || [];
                    const newPallets = [];
                    
                    for (let i = 0; i < palletCount; i++) {
                        newPallets.push({
                            pallet_number: currentPallets[i]?.pallet_number || (i + 1).toString(),
                            description: currentPallets[i]?.description || ''
                        });
                    }
                    
                    row.pallets = newPallets;
                }
            }" class="space-y-6">
                
                <template x-for="(row, index) in rows" :key="index">
                    <div class="bg-gray-50/50 p-6 rounded-xl border border-gray-100 space-y-4">
                        <!-- Header with Remove Button -->
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700" x-text="'Container Group #' + (index + 1)"></h3>
                            <button type="button" @click="removeRow(index)" x-show="rows.length > 1"
                                class="text-red-400 hover:text-red-600 transition-colors text-sm">
                                <i class="fa-solid fa-trash-can"></i> Remove
                            </button>
                        </div>

                        <!-- Main Fields - All in One Row -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Package Type -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Package Type <span class="text-red-500">*</span></label>
                                <select x-model="row.package_type" :name="'fcl_details[' + index + '][package_type]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none">
                                    <option value="">Select...</option>
                                    <option value="container">Container</option>
                                    <option value="parcel">Parcel</option>
                                    <option value="small_parcel">Small Parcel</option>
                                    <option value="box">Box</option>
                                    <option value="small_box">Small Box</option>
                                    <option value="pallet">Pallet</option>
                                    <option value="crate">Crate</option>
                                    <option value="drum">Drum</option>
                                    <option value="barrel">Barrel</option>
                                    <option value="carton">Carton</option>
                                    <option value="bag">Bag</option>
                                    <option value="roll">Roll</option>
                                    <option value="bundle">Bundle</option>
                                    <option value="skid">Skid</option>
                                    <option value="bale">Bale</option>
                                    <option value="loose">Loose</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <!-- Container Number -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Container Number</label>
                                <input type="text" x-model="row.container_number" :name="'fcl_details[' + index + '][container_number]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="e.g. MSCU-1234567">
                            </div>

                            <!-- QTY -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">QTY <span class="text-red-500">*</span></label>
                                <input type="number" x-model="row.qty" :name="'fcl_details[' + index + '][qty]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="Quantity" min="1">
                            </div>

                            <!-- Pallet Count -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Pallet Count</label>
                                <input type="number" x-model="row.pallet_count" @input="setTimeout(() => updatePallets(index), 10)"
                                    :name="'fcl_details[' + index + '][pallet_count]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="Number of pallets" min="0">
                            </div>
                        </div>

                        <!-- Pallet Details - Shows when pallet_count > 0 -->
                        <div x-show="row.pallets && row.pallets.length > 0" 
                            class="border-t border-gray-200 pt-4">
                            <div class="grid grid-cols-12 gap-3">
                                <!-- Table Header -->
                                <div class="col-span-3">
                                    <label class="text-xs font-medium text-gray-600">Pallet Number</label>
                                </div>
                                <div class="col-span-8">
                                    <label class="text-xs font-medium text-gray-600">Package Description</label>
                                </div>
                                <div class="col-span-1"></div>

                                <!-- Pallet Rows -->
                                <template x-for="(pallet, palletIndex) in row.pallets" :key="palletIndex">
                                    <div class="col-span-12 grid grid-cols-12 gap-3 items-center mt-1">
                                        <!-- Pallet Number Input -->
                                        <div class="col-span-3">
                                            <input type="text" x-model="pallet.pallet_number"
                                                :name="'fcl_details[' + index + '][pallets][' + palletIndex + '][pallet_number]'"
                                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                                placeholder="Pallet #">
                                        </div>

                                        <!-- Description Input -->
                                        <div class="col-span-8">
                                            <input type="text" x-model="pallet.description"
                                                :name="'fcl_details[' + index + '][pallets][' + palletIndex + '][description]'"
                                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                                placeholder="Package description">
                                        </div>

                                        <!-- Barcode Button -->
                                        <div class="col-span-1">
                                            <button type="button" @click="generateBarcode(pallet.description)"
                                                class="text-blue-500 hover:text-blue-700 transition-colors">
                                                <i class="fa-solid fa-barcode"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Add Row Button -->
                <div class="flex justify-end">
                    <button type="button" @click="addRow()"
                        class="text-[#0078C8] hover:text-[#006bb0] text-sm font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-plus-circle"></i> Add Another Container Group
                    </button>
                </div>
            </div>
        </div>
    </x-form.form-section>
</div>

<script>
    function generateBarcode(description) {
        if (description) {
            const barcode = 'BAR-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6);
            alert('Barcode generated: ' + barcode + ' for: ' + description);
        } else {
            alert('Please enter a description first');
        }
    }
</script> --}}
{{-- 
@props(['booking' => null])

@php
    $fclRows = old(
        'fcl_details',
        $booking?->items
            ? $booking->items
                ->filter(fn($item) => $item->freight_mode === 'fcl_sea' || $item->freight_sub_mode === 'fcl_sea')
                ->map(
                    fn($item) => [
                        'package_type' => $item->package_type ?? '',
                        'container_number' => $item->container_number ?? '',
                        'qty' => $item->qty ?? 1,
                        'pallet_count' => $item->pallet_count ?? 0,
                        'pallets' => $item->pallets ?? [],
                    ],
                )
                ->values()
                ->all()
            : [],
    );

    if (empty($fclRows)) {
        $fclRows = [[
            'package_type' => '',
            'container_number' => '',
            'qty' => 1,
            'pallet_count' => 0,
            'pallets' => [],
        ]];
    }
@endphp

<div class="space-y-6">
    <x-form.form-section title="FCL Sea Carrier and Equipment" :required="true">
        <div class="grid grid-cols-1 gap-6 p-6">
            <!-- Carrier Field -->
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
                <select id="carrier" name="carrier_id" x-init="$nextTick(() => { $($el).select2({ placeholder: 'Select carrier...' }) })"
                    class="select2 carrier-select w-full h-10 px-3 py-2 bg-white rounded-lg border border-slate-300 text-base text-gray-900 appearance-none bg-no-repeat bg-right pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m19 9-7 7-7-7'/%3E%3C/svg%3E&quot;); background-size: 1.2rem; background-position: right 0.5rem center;">
                    <option value="" disabled
                        {{ old('carrier_id', $booking->carrier_id ?? '') === '' ? 'selected' : '' }}>Select carrier...
                    </option>
                    @php
                        $selectedCarrier = old('carrier_id', $booking->carrier_id ?? '');
                        $approvedCarriers = \App\Models\LogisticsV2Carriers::approved()
                            ->where('transport_modes', 'ocean_liner')
                            ->orderBy('carrier_company_name')
                            ->get();
                        $approvedNames = $approvedCarriers->pluck('carrier_company_name')->toArray();
                        $hasSelected = false;
                    @endphp
                    @foreach ($approvedCarriers as $c)
                        @if ($c->id === $selectedCarrier)
                            @php $hasSelected = true; @endphp
                        @endif
                        <option value="{{ $c->id }}" {{ $selectedCarrier === $c->id ? 'selected' : '' }}>
                            {{ $c->carrier_company_name }}
                        </option>
                    @endforeach
                    @if (!empty($selectedCarrier) && !$hasSelected)
                        <option value="{{ $selectedCarrier }}" selected>{{ $selectedCarrier }}</option>
                    @endif
                </select>
            </div>

            <!-- Container Type -->
            <x-form.form-select name="container_type" label="Container Type" placeholder="Select type..."
                :options="[
                    '20ft' => '20ft Standard',
                    '40ft' => '40ft Standard',
                    '40ft_hc' => '40ft High Cube',
                    '20ft_ref' => '20ft Reefer',
                    'open_top' => 'Open Top',
                    'flat_rack' => 'Flat Rack',
                ]" :selected="old('container_type', $booking?->items?->first()?->container_type ?? '')" />

            <!-- Dynamic Rows -->
            <div x-data="{
                rows: @js($fclRows),
                
                addRow() { 
                    this.rows.push({ 
                        package_type: '', 
                        container_number: '', 
                        qty: 1, 
                        pallet_count: 0,
                        pallets: []
                    }) 
                },
                
                removeRow(index) { 
                    if (this.rows.length > 1) this.rows.splice(index, 1) 
                },
                
                updatePallets(index) {
                    const row = this.rows[index];
                    const palletCount = parseInt(row.pallet_count) || 0;
                    const currentPallets = row.pallets || [];
                    const newPallets = [];
                    
                    // IMPORTANT: Only create exactly 'palletCount' number of rows
                    for (let i = 0; i < palletCount; i++) {
                        newPallets.push({
                            pallet_number: currentPallets[i]?.pallet_number || (i + 1).toString(),
                            description: currentPallets[i]?.description || ''
                        });
                    }
                    
                    row.pallets = newPallets;
                }
            }" class="space-y-6">
                
                <template x-for="(row, index) in rows" :key="index">
                    <div class="bg-gray-50/50 p-6 rounded-xl border border-gray-100 space-y-4">
                        <!-- Header with Remove Button -->
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700" x-text="'Container Group #' + (index + 1)"></h3>
                            <button type="button" @click="removeRow(index)" x-show="rows.length > 1"
                                class="text-red-400 hover:text-red-600 transition-colors text-sm">
                                <i class="fa-solid fa-trash-can"></i> Remove
                            </button>
                        </div>

                        <!-- Main Fields - All in One Row -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Package Type -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Package Type <span class="text-red-500">*</span></label>
                                <select x-model="row.package_type" :name="'fcl_details[' + index + '][package_type]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none">
                                    <option value="">Select...</option>
                                    <option value="container">Container</option>
                                    <option value="parcel">Parcel</option>
                                    <option value="small_parcel">Small Parcel</option>
                                    <option value="box">Box</option>
                                    <option value="small_box">Small Box</option>
                                    <option value="pallet">Pallet</option>
                                    <option value="crate">Crate</option>
                                    <option value="drum">Drum</option>
                                    <option value="barrel">Barrel</option>
                                    <option value="carton">Carton</option>
                                    <option value="bag">Bag</option>
                                    <option value="roll">Roll</option>
                                    <option value="bundle">Bundle</option>
                                    <option value="skid">Skid</option>
                                    <option value="bale">Bale</option>
                                    <option value="loose">Loose</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <!-- Container Number -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Container Number</label>
                                <input type="text" x-model="row.container_number" :name="'fcl_details[' + index + '][container_number]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="e.g. MSCU-1234567">
                            </div>

                            <!-- QTY -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">QTY <span class="text-red-500">*</span></label>
                                <input type="number" x-model="row.qty" :name="'fcl_details[' + index + '][qty]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="Quantity" min="1">
                            </div>

                            <!-- Pallet Count -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Pallet Count</label>
                                <input type="number" x-model="row.pallet_count" @input="setTimeout(() => updatePallets(index), 10)"
                                    :name="'fcl_details[' + index + '][pallet_count]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="Number of pallets" min="0">
                            </div>
                        </div>

                        <!-- Pallet Details - Shows exactly 'pallet_count' number of rows -->
                        <div x-show="row.pallets && row.pallets.length > 0" 
                            class="border-t border-gray-200 pt-4">
                            <div class="grid grid-cols-12 gap-3">
                                <!-- Table Header -->
                                <div class="col-span-3">
                                    <label class="text-xs font-medium text-gray-600">Pallet Number</label>
                                </div>
                                <div class="col-span-8">
                                    <label class="text-xs font-medium text-gray-600">Package Description</label>
                                </div>
                                <div class="col-span-1"></div>

                                <!-- Pallet Rows - EXACTLY 'pallet_count' rows -->
                                <template x-for="(pallet, palletIndex) in row.pallets" :key="palletIndex">
                                    <div class="col-span-12 grid grid-cols-12 gap-3 items-center mt-1">
                                        <!-- Pallet Number Input -->
                                        <div class="col-span-3">
                                            <input type="text" x-model="pallet.pallet_number"
                                                :name="'fcl_details[' + index + '][pallets][' + palletIndex + '][pallet_number]'"
                                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                                placeholder="Pallet #">
                                            <span class="text-[10px] text-gray-400">Pallet number</span>
                                        </div>

                                        <!-- Description Input -->
                                        <div class="col-span-8">
                                            <input type="text" x-model="pallet.description"
                                                :name="'fcl_details[' + index + '][pallets][' + palletIndex + '][description]'"
                                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                                placeholder="Package description">
                                            <span class="text-[10px] text-gray-400">Package description</span>
                                        </div>

                                        <!-- Barcode Button -->
                                        <div class="col-span-1">
                                            <button type="button" @click="generateBarcode(pallet.description)"
                                                class="text-blue-500 hover:text-blue-700 transition-colors">
                                                <i class="fa-solid fa-barcode"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Add Row Button -->
                <div class="flex justify-end">
                    <button type="button" @click="addRow()"
                        class="text-[#0078C8] hover:text-[#006bb0] text-sm font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-plus-circle"></i> Add Another Container Group
                    </button>
                </div>
            </div>
        </div>
    </x-form.form-section>
</div>

<script>
    function generateBarcode(description) {
        if (description) {
            const barcode = 'BAR-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6);
            alert('Barcode generated: ' + barcode + ' for: ' + description);
        } else {
            alert('Please enter a description first');
        }
    }
</script> --}}

{{-- @props(['booking' => null])

@php
    $fclRows = old(
        'fcl_details',
        $booking?->items
            ? $booking->items
                ->filter(fn($item) => $item->freight_mode === 'fcl_sea' || $item->freight_sub_mode === 'fcl_sea')
                ->map(
                    fn($item) => [
                        'package_type' => $item->package_type ?? '',
                        'container_number' => $item->container_number ?? '',
                        'qty' => $item->qty ?? 1,
                        'pallet_count' => $item->pallet_count ?? 0,
                        'pallets' => $item->pallets ?? [],
                    ],
                )
                ->values()
                ->all()
            : [],
    );

    if (empty($fclRows)) {
        $fclRows = [[
            'package_type' => '',
            'container_number' => '',
            'qty' => 1,
            'pallet_count' => 0,
            'pallets' => [],
        ]];
    }
@endphp

<div class="space-y-6">
    <x-form.form-section title="FCL Sea Carrier and Equipment" :required="true">
        <div class="grid grid-cols-1 gap-6 p-6">
            <!-- Carrier Field -->
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
                <select id="carrier" name="carrier_id" x-init="$nextTick(() => { $($el).select2({ placeholder: 'Select carrier...' }) })"
                    class="select2 carrier-select w-full h-10 px-3 py-2 bg-white rounded-lg border border-slate-300 text-base text-gray-900 appearance-none bg-no-repeat bg-right pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m19 9-7 7-7-7'/%3E%3C/svg%3E&quot;); background-size: 1.2rem; background-position: right 0.5rem center;">
                    <option value="" disabled
                        {{ old('carrier_id', $booking->carrier_id ?? '') === '' ? 'selected' : '' }}>Select carrier...
                    </option>
                    @php
                        $selectedCarrier = old('carrier_id', $booking->carrier_id ?? '');
                        $approvedCarriers = \App\Models\LogisticsV2Carriers::approved()
                            ->where('transport_modes', 'ocean_liner')
                            ->orderBy('carrier_company_name')
                            ->get();
                        $approvedNames = $approvedCarriers->pluck('carrier_company_name')->toArray();
                        $hasSelected = false;
                    @endphp
                    @foreach ($approvedCarriers as $c)
                        @if ($c->id === $selectedCarrier)
                            @php $hasSelected = true; @endphp
                        @endif
                        <option value="{{ $c->id }}" {{ $selectedCarrier === $c->id ? 'selected' : '' }}>
                            {{ $c->carrier_company_name }}
                        </option>
                    @endforeach
                    @if (!empty($selectedCarrier) && !$hasSelected)
                        <option value="{{ $selectedCarrier }}" selected>{{ $selectedCarrier }}</option>
                    @endif
                </select>
            </div>

            <!-- Container Type -->
            <x-form.form-select name="container_type" label="Container Type" placeholder="Select type..."
                :options="[
                    '20ft' => '20ft Standard',
                    '40ft' => '40ft Standard',
                    '40ft_hc' => '40ft High Cube',
                    '20ft_ref' => '20ft Reefer',
                    'open_top' => 'Open Top',
                    'flat_rack' => 'Flat Rack',
                ]" :selected="old('container_type', $booking?->items?->first()?->container_type ?? '')" />

            <!-- Dynamic Rows -->
            <div x-data="{
                rows: @js($fclRows),
                
                addRow() { 
                    this.rows.push({ 
                        package_type: '', 
                        container_number: '', 
                        qty: 1, 
                        pallet_count: 0,
                        pallets: []
                    }) 
                },
                
                removeRow(index) { 
                    if (this.rows.length > 1) this.rows.splice(index, 1) 
                },
                
                updatePallets(index) {
                    const row = this.rows[index];
                    const palletCount = parseInt(row.pallet_count) || 0;
                    const currentPallets = row.pallets || [];
                    const newPallets = [];
                    
                    // IMPORTANT: Only create exactly 'palletCount' number of rows
                    for (let i = 0; i < palletCount; i++) {
                        newPallets.push({
                            pallet_number: currentPallets[i]?.pallet_number || (i + 1).toString(),
                            description: currentPallets[i]?.description || ''
                        });
                    }
                    
                    row.pallets = newPallets;
                }
            }" class="space-y-6">
                
                <template x-for="(row, index) in rows" :key="index">
                    <div class="bg-gray-50/50 p-6 rounded-xl border border-gray-100 space-y-4">
                        <!-- Header with Remove Button -->
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700" x-text="'Container Group #' + (index + 1)"></h3>
                            <button type="button" @click="removeRow(index)" x-show="rows.length > 1"
                                class="text-red-400 hover:text-red-600 transition-colors text-sm">
                                <i class="fa-solid fa-trash-can"></i> Remove
                            </button>
                        </div>

                        <!-- Main Fields - All in One Row -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Package Type -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Package Type <span class="text-red-500">*</span></label>
                                <select x-model="row.package_type" :name="'fcl_details[' + index + '][package_type]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none">
                                    <option value="">Select...</option>
                                    <option value="container">Container</option>
                                    <option value="parcel">Parcel</option>
                                    <option value="small_parcel">Small Parcel</option>
                                    <option value="box">Box</option>
                                    <option value="small_box">Small Box</option>
                                    <option value="pallet">Pallet</option>
                                    <option value="crate">Crate</option>
                                    <option value="drum">Drum</option>
                                    <option value="barrel">Barrel</option>
                                    <option value="carton">Carton</option>
                                    <option value="bag">Bag</option>
                                    <option value="roll">Roll</option>
                                    <option value="bundle">Bundle</option>
                                    <option value="skid">Skid</option>
                                    <option value="bale">Bale</option>
                                    <option value="loose">Loose</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <!-- Container Number -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Container Number</label>
                                <input type="text" x-model="row.container_number" :name="'fcl_details[' + index + '][container_number]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="e.g. MSCU-1234567">
                            </div>

                            <!-- QTY -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">QTY <span class="text-red-500">*</span></label>
                                <input type="number" x-model="row.qty" :name="'fcl_details[' + index + '][qty]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="Quantity" min="1">
                            </div>

                            <!-- Pallet Count -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Pallet Count</label>
                                <input type="number" x-model="row.pallet_count" @input="setTimeout(() => updatePallets(index), 10)"
                                    :name="'fcl_details[' + index + '][pallet_count]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="Number of pallets" min="0">
                            </div>
                        </div>

                        <!-- Pallet Details - Shows exactly 'pallet_count' number of rows -->
                        <div x-show="row.pallets && row.pallets.length > 0" 
                            class="border-t border-gray-200 pt-4">
                            <div class="grid grid-cols-12 gap-3">
                                <!-- Table Header -->
                                <div class="col-span-3">
                                    <label class="text-xs font-medium text-gray-600">Pallet Number</label>
                                </div>
                                <div class="col-span-8">
                                    <label class="text-xs font-medium text-gray-600">Package Description</label>
                                </div>
                                <div class="col-span-1"></div>

                                <!-- Pallet Rows - EXACTLY 'pallet_count' rows -->
                                <template x-for="(pallet, palletIndex) in row.pallets" :key="palletIndex">
                                    <div class="col-span-12 grid grid-cols-12 gap-3 items-center mt-1">
                                        <!-- Pallet Number Input -->
                                        <div class="col-span-3">
                                            <input type="text" x-model="pallet.pallet_number"
                                                :name="'fcl_details[' + index + '][pallets][' + palletIndex + '][pallet_number]'"
                                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                                placeholder="Pallet #">
                                            <span class="text-[10px] text-gray-400">Pallet number</span>
                                        </div>

                                        <!-- Description Input -->
                                        <div class="col-span-8">
                                            <input type="text" x-model="pallet.description"
                                                :name="'fcl_details[' + index + '][pallets][' + palletIndex + '][description]'"
                                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                                placeholder="Package description">
                                            <span class="text-[10px] text-gray-400">Package description</span>
                                        </div>

                                        <!-- Barcode Button -->
                                        <div class="col-span-1">
                                            <button type="button" @click="generateBarcode(pallet.description)"
                                                class="text-blue-500 hover:text-blue-700 transition-colors">
                                                <i class="fa-solid fa-barcode"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Add Row Button -->
                <div class="flex justify-end">
                    <button type="button" @click="addRow()"
                        class="text-[#0078C8] hover:text-[#006bb0] text-sm font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-plus-circle"></i> Add Another Container Group
                    </button>
                </div>
            </div>
        </div>
    </x-form.form-section>
</div>

<script>
    function generateBarcode(description) {
        if (description) {
            const barcode = 'BAR-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6);
            alert('Barcode generated: ' + barcode + ' for: ' + description);
        } else {
            alert('Please enter a description first');
        }
    }
</script> --}}

{{-- @props(['booking' => null])

@php
    $fclRows = old(
        'fcl_details',
        $booking?->items
            ? $booking->items
                ->filter(fn($item) => $item->freight_mode === 'fcl_sea' || $item->freight_sub_mode === 'fcl_sea')
                ->map(
                    fn($item) => [
                        'package_type' => $item->package_type ?? '',
                        'container_number' => $item->container_number ?? '',
                        'qty' => $item->qty ?? 1,
                        'pallet_count' => $item->pallet_count ?? 0,
                        'pallets' => $item->pallets ?? [],
                    ],
                )
                ->values()
                ->all()
            : [],
    );

    if (empty($fclRows)) {
        $fclRows = [[
            'package_type' => '',
            'container_number' => '',
            'qty' => 1,
            'pallet_count' => 0,
            'pallets' => [],
        ]];
    }
@endphp

<div class="space-y-6">
    <x-form.form-section title="FCL Sea Carrier and Equipment" :required="true">
        <div class="grid grid-cols-1 gap-6 p-6">
            <!-- Carrier Field -->
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
                <select id="carrier" name="carrier_id" x-init="$nextTick(() => { $($el).select2({ placeholder: 'Select carrier...' }) })"
                    class="select2 carrier-select w-full h-10 px-3 py-2 bg-white rounded-lg border border-slate-300 text-base text-gray-900 appearance-none bg-no-repeat bg-right pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m19 9-7 7-7-7'/%3E%3C/svg%3E&quot;); background-size: 1.2rem; background-position: right 0.5rem center;">
                    <option value="" disabled
                        {{ old('carrier_id', $booking->carrier_id ?? '') === '' ? 'selected' : '' }}>Select carrier...
                    </option>
                    @php
                        $selectedCarrier = old('carrier_id', $booking->carrier_id ?? '');
                        $approvedCarriers = \App\Models\LogisticsV2Carriers::approved()
                            ->where('transport_modes', 'ocean_liner')
                            ->orderBy('carrier_company_name')
                            ->get();
                        $approvedNames = $approvedCarriers->pluck('carrier_company_name')->toArray();
                        $hasSelected = false;
                    @endphp
                    @foreach ($approvedCarriers as $c)
                        @if ($c->id === $selectedCarrier)
                            @php $hasSelected = true; @endphp
                        @endif
                        <option value="{{ $c->id }}" {{ $selectedCarrier === $c->id ? 'selected' : '' }}>
                            {{ $c->carrier_company_name }}
                        </option>
                    @endforeach
                    @if (!empty($selectedCarrier) && !$hasSelected)
                        <option value="{{ $selectedCarrier }}" selected>{{ $selectedCarrier }}</option>
                    @endif
                </select>
            </div>

            <!-- Container Type -->
            <x-form.form-select name="container_type" label="Container Type" placeholder="Select type..."
                :options="[
                    '20ft' => '20ft Standard',
                    '40ft' => '40ft Standard',
                    '40ft_hc' => '40ft High Cube',
                    '20ft_ref' => '20ft Reefer',
                    'open_top' => 'Open Top',
                    'flat_rack' => 'Flat Rack',
                ]" :selected="old('container_type', $booking?->items?->first()?->container_type ?? '')" />

            <!-- Dynamic Rows -->
            <div x-data="{
                rows: @js($fclRows),
                
                addRow() { 
                    this.rows.push({ 
                        package_type: '', 
                        container_number: '', 
                        qty: 1, 
                        pallet_count: 0,
                        pallets: []
                    }) 
                },
                
                removeRow(index) { 
                    if (this.rows.length > 1) this.rows.splice(index, 1) 
                },
                
                updatePallets(index) {
                    const row = this.rows[index];
                    const palletCount = parseInt(row.pallet_count) || 0;
                    const currentPallets = row.pallets || [];
                    const newPallets = [];
                    
                    // Create exactly 'palletCount' number of rows
                    // Pallet number fields start EMPTY - user will fill them manually
                    for (let i = 0; i < palletCount; i++) {
                        newPallets.push({
                            pallet_number: currentPallets[i]?.pallet_number || '', // EMPTY by default
                            description: currentPallets[i]?.description || ''
                        });
                    }
                    
                    row.pallets = newPallets;
                }
            }" class="space-y-6">
                
                <template x-for="(row, index) in rows" :key="index">
                    <div class="bg-gray-50/50 p-6 rounded-xl border border-gray-100 space-y-4">
                        <!-- Header with Remove Button -->
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700" x-text="'Container Group #' + (index + 1)"></h3>
                            <button type="button" @click="removeRow(index)" x-show="rows.length > 1"
                                class="text-red-400 hover:text-red-600 transition-colors text-sm">
                                <i class="fa-solid fa-trash-can"></i> Remove
                            </button>
                        </div>

                        <!-- Main Fields - All in One Row -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Package Type -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Package Type <span class="text-red-500">*</span></label>
                                <select x-model="row.package_type" :name="'fcl_details[' + index + '][package_type]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none">
                                    <option value="">Select...</option>
                                    <option value="container">Container</option>
                                    <option value="parcel">Parcel</option>
                                    <option value="small_parcel">Small Parcel</option>
                                    <option value="box">Box</option>
                                    <option value="small_box">Small Box</option>
                                    <option value="pallet">Pallet</option>
                                    <option value="crate">Crate</option>
                                    <option value="drum">Drum</option>
                                    <option value="barrel">Barrel</option>
                                    <option value="carton">Carton</option>
                                    <option value="bag">Bag</option>
                                    <option value="roll">Roll</option>
                                    <option value="bundle">Bundle</option>
                                    <option value="skid">Skid</option>
                                    <option value="bale">Bale</option>
                                    <option value="loose">Loose</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <!-- Container Number -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Container Number</label>
                                <input type="text" x-model="row.container_number" :name="'fcl_details[' + index + '][container_number]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="e.g. MSCU-1234567">
                            </div>

                            <!-- QTY -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">QTY <span class="text-red-500">*</span></label>
                                <input type="number" x-model="row.qty" :name="'fcl_details[' + index + '][qty]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="Quantity" min="1">
                            </div>

                            <!-- Pallet Count -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Pallet Count</label>
                                <input type="number" x-model="row.pallet_count" @input="setTimeout(() => updatePallets(index), 10)"
                                    :name="'fcl_details[' + index + '][pallet_count]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="Number of pallets" min="0">
                            </div>
                        </div>

                        <!-- Pallet Details - Shows exactly 'pallet_count' rows -->
                        <div x-show="row.pallets && row.pallets.length > 0" 
                            class="border-t border-gray-200 pt-4">
                            <div class="grid grid-cols-12 gap-3">
                                <!-- Table Header -->
                                <div class="col-span-3">
                                    <label class="text-xs font-medium text-gray-600">Pallet Number</label>
                                </div>
                                <div class="col-span-8">
                                    <label class="text-xs font-medium text-gray-600">Package Description</label>
                                </div>
                                <div class="col-span-1"></div>

                                <!-- Pallet Rows - EXACTLY 'pallet_count' rows, all empty initially -->
                                <template x-for="(pallet, palletIndex) in row.pallets" :key="palletIndex">
                                    <div class="col-span-12 grid grid-cols-12 gap-3 items-center mt-2">
                                        <!-- Pallet Number Input - STARTS EMPTY -->
                                        <div class="col-span-3">
                                            <input type="text" x-model="pallet.pallet_number"
                                                :name="'fcl_details[' + index + '][pallets][' + palletIndex + '][pallet_number]'"
                                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                                placeholder="Pallet number">
                                        </div>

                                        <!-- Description Input -->
                                        <div class="col-span-8">
                                            <input type="text" x-model="pallet.description"
                                                :name="'fcl_details[' + index + '][pallets][' + palletIndex + '][description]'"
                                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                                placeholder="Package description">
                                        </div>

                                        <!-- Barcode Button -->
                                        <div class="col-span-1">
                                            <button type="button" @click="generateBarcode(pallet.description)"
                                                class="text-blue-500 hover:text-blue-700 transition-colors">
                                                <i class="fa-solid fa-barcode"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Add Row Button -->
                <div class="flex justify-end">
                    <button type="button" @click="addRow()"
                        class="text-[#0078C8] hover:text-[#006bb0] text-sm font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-plus-circle"></i> Add Another Container Group
                    </button>
                </div>
            </div>
        </div>
    </x-form.form-section>
</div>

<script>
    function generateBarcode(description) {
        if (description) {
            const barcode = 'BAR-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6);
            alert('Barcode generated: ' + barcode + ' for: ' + description);
        } else {
            alert('Please enter a description first');
        }
    }
</script> --}}


{{-- @props(['booking' => null])

@php
    $fclRows = old(
        'fcl_details',
        $booking?->items
            ? $booking->items
                ->filter(fn($item) => $item->freight_mode === 'fcl_sea' || $item->freight_sub_mode === 'fcl_sea')
                ->map(
                    fn($item) => [
                        'package_type' => $item->package_type ?? '',
                        'container_number' => $item->container_number ?? '',
                        'qty' => $item->qty ?? 1,
                        'pallet_count' => $item->pallet_count ?? 0,
                        'pallets' => $item->pallets ?? [],
                    ],
                )
                ->values()
                ->all()
            : [],
    );

    if (empty($fclRows)) {
        $fclRows = [[
            'package_type' => '',
            'container_number' => '',
            'qty' => 1,
            'pallet_count' => 0,
            'pallets' => [],
        ]];
    }
@endphp

<div class="space-y-6">
    <x-form.form-section title="FCL Sea Carrier and Equipment" :required="true">
        <div class="grid grid-cols-1 gap-6 p-6">
            <!-- Carrier Field -->
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
                <select id="carrier" name="carrier_id" x-init="$nextTick(() => { $($el).select2({ placeholder: 'Select carrier...' }) })"
                    class="select2 carrier-select w-full h-10 px-3 py-2 bg-white rounded-lg border border-slate-300 text-base text-gray-900 appearance-none bg-no-repeat bg-right pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m19 9-7 7-7-7'/%3E%3C/svg%3E&quot;); background-size: 1.2rem; background-position: right 0.5rem center;">
                    <option value="" disabled
                        {{ old('carrier_id', $booking->carrier_id ?? '') === '' ? 'selected' : '' }}>Select carrier...
                    </option>
                    @php
                        $selectedCarrier = old('carrier_id', $booking->carrier_id ?? '');
                        $approvedCarriers = \App\Models\LogisticsV2Carriers::approved()
                            ->where('transport_modes', 'ocean_liner')
                            ->orderBy('carrier_company_name')
                            ->get();
                        $approvedNames = $approvedCarriers->pluck('carrier_company_name')->toArray();
                        $hasSelected = false;
                    @endphp
                    @foreach ($approvedCarriers as $c)
                        @if ($c->id === $selectedCarrier)
                            @php $hasSelected = true; @endphp
                        @endif
                        <option value="{{ $c->id }}" {{ $selectedCarrier === $c->id ? 'selected' : '' }}>
                            {{ $c->carrier_company_name }}
                        </option>
                    @endforeach
                    @if (!empty($selectedCarrier) && !$hasSelected)
                        <option value="{{ $selectedCarrier }}" selected>{{ $selectedCarrier }}</option>
                    @endif
                </select>
            </div>

            <!-- Container Type -->
            <x-form.form-select name="container_type" label="Container Type" placeholder="Select type..."
                :options="[
                    '20ft' => '20ft Standard',
                    '40ft' => '40ft Standard',
                    '40ft_hc' => '40ft High Cube',
                    '20ft_ref' => '20ft Reefer',
                    'open_top' => 'Open Top',
                    'flat_rack' => 'Flat Rack',
                ]" :selected="old('container_type', $booking?->items?->first()?->container_type ?? '')" />

            <!-- Dynamic Rows -->
            <div x-data="{
                rows: @js($fclRows),
                
                addRow() { 
                    this.rows.push({ 
                        package_type: '', 
                        container_number: '', 
                        qty: 1, 
                        pallet_count: 0,
                        pallets: []
                    }) 
                },
                
                removeRow(index) { 
                    if (this.rows.length > 1) this.rows.splice(index, 1) 
                },
                
                updatePallets(index) {
                    const row = this.rows[index];
                    const palletCount = parseInt(row.pallet_count) || 0;
                    const currentPallets = row.pallets || [];
                    const newPallets = [];
                    
                    // CRITICAL: Create exactly 'palletCount' rows
                    // Pallet number fields are EMPTY by default - user fills them
                    for (let i = 0; i < palletCount; i++) {
                        newPallets.push({
                            pallet_number: currentPallets[i]?.pallet_number || '',
                            description: currentPallets[i]?.description || ''
                        });
                    }
                    
                    row.pallets = newPallets;
                }
            }" class="space-y-6">
                
                <template x-for="(row, index) in rows" :key="index">
                    <div class="bg-gray-50/50 p-6 rounded-xl border border-gray-100 space-y-4">
                        <!-- Header -->
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700" x-text="'Container Group #' + (index + 1)"></h3>
                            <button type="button" @click="removeRow(index)" x-show="rows.length > 1"
                                class="text-red-400 hover:text-red-600 transition-colors text-sm">
                                <i class="fa-solid fa-trash-can"></i> Remove
                            </button>
                        </div>

                        <!-- Main Fields - 4 Columns -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Package Type -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Package Type <span class="text-red-500">*</span></label>
                                <select x-model="row.package_type" :name="'fcl_details[' + index + '][package_type]'"
                                    class="select2 w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none">
                                    <option value="">Select...</option>
                                    <option value="container">Container</option>
                                    <option value="parcel">Parcel</option>
                                    <option value="small_parcel">Small Parcel</option>
                                    <option value="box">Box</option>
                                    <option value="small_box">Small Box</option>
                                    <option value="pallet">Pallet</option>
                                    <option value="crate">Crate</option>
                                    <option value="drum">Drum</option>
                                    <option value="barrel">Barrel</option>
                                    <option value="carton">Carton</option>
                                    <option value="bag">Bag</option>
                                    <option value="roll">Roll</option>
                                    <option value="bundle">Bundle</option>
                                    <option value="skid">Skid</option>
                                    <option value="bale">Bale</option>
                                    <option value="loose">Loose</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <!-- Container Number -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Container Number</label>
                                <input type="text" x-model="row.container_number" :name="'fcl_details[' + index + '][container_number]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="e.g. MSCU-1234567">
                            </div>

                            <!-- QTY -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">QTY <span class="text-red-500">*</span></label>
                                <input type="number" x-model="row.qty" :name="'fcl_details[' + index + '][qty]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="Quantity" min="1">
                            </div>

                            <!-- Pallet Count -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Pallet Count</label>
                                <input type="number" x-model="row.pallet_count" @input="setTimeout(() => updatePallets(index), 10)"
                                    :name="'fcl_details[' + index + '][pallet_count]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="Number of pallets" min="0">
                                <span class="text-[10px] text-gray-400" x-show="row.pallet_count > 0" 
                                      x-text="'Total pallets: ' + row.pallet_count"></span>
                            </div>
                        </div>

                        <!-- Pallet Details - EXACTLY 'pallet_count' rows -->
                        <div x-show="row.pallets && row.pallets.length > 0" 
                            class="border-t border-gray-200 pt-4">
                            <!-- Header -->
                            <div class="grid grid-cols-12 gap-3 mb-2">
                                <div class="col-span-3">
                                    <label class="text-xs font-medium text-gray-600">Pallet Number</label>
                                </div>
                                <div class="col-span-8">
                                    <label class="text-xs font-medium text-gray-600">Package Description</label>
                                </div>
                                <div class="col-span-1"></div>
                            </div>

                            <!-- Pallet Rows - Fixed count based on pallet_count -->
                            <template x-for="(pallet, palletIndex) in row.pallets" :key="palletIndex">
                                <div class="grid grid-cols-12 gap-3 items-center mt-2">
                                    <!-- Pallet Number Input -->
                                    <div class="col-span-3">
                                        <input type="text" x-model="pallet.pallet_number"
                                            :name="'fcl_details[' + index + '][pallets][' + palletIndex + '][pallet_number]'"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                            placeholder="Pallet number">
                                    </div>

                                    <!-- Description Input -->
                                    <div class="col-span-8">
                                        <input type="text" x-model="pallet.description"
                                            :name="'fcl_details[' + index + '][pallets][' + palletIndex + '][description]'"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                            placeholder="Package description">
                                    </div>

                                    <!-- Barcode Button -->
                                    <div class="col-span-1">
                                        <button type="button" @click="generateBarcode(pallet.description)"
                                            class="text-blue-500 hover:text-blue-700 transition-colors">
                                            <i class="fa-solid fa-barcode"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Show count info -->
                            <div class="text-xs text-gray-400 mt-3" x-show="row.pallets.length > 0">
                                <span x-text="'Showing ' + row.pallets.length + ' of ' + row.pallet_count + ' pallets'"></span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Add Row Button -->
                <div class="flex justify-end">
                    <button type="button" @click="addRow()"
                        class="text-[#0078C8] hover:text-[#006bb0] text-sm font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-plus-circle"></i> Add Another Container Group
                    </button>
                </div>
            </div>
        </div>
    </x-form.form-section>
</div>

<script>
    function generateBarcode(description) {
        if (description) {
            const barcode = 'BAR-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6);
            alert('Barcode generated: ' + barcode + ' for: ' + description);
        } else {
            alert('Please enter a description first');
        }
    }
</script> --}}


@props(['booking' => null])

@php
    $fclRows = old(
        'fcl_details',
        $booking?->items
            ? $booking->items
                ->filter(fn($item) => $item->freight_mode === 'fcl_sea' || $item->freight_sub_mode === 'fcl_sea')
                ->map(
                    fn($item) => [
                        'package_type' => $item->package_type ?? '',
                        'container_number' => $item->container_number ?? '',
                        'qty' => $item->quantity ?? ($item->qty ?? 1),
                        'pallet_count' => $item->pallet_count ?? 0,
                        'pallets' => is_string($item->pallets) ? json_decode($item->pallets, true) : ($item->pallets ?? []),
                    ],
                )
                ->values()
                ->all()
            : [],
    );

    if (empty($fclRows)) {
        $fclRows = [[
            'package_type' => '',
            'container_number' => '',
            'qty' => 1,
            'pallet_count' => 0,
            'pallets' => [],
        ]];
    }
@endphp

<div class="space-y-6">
    <x-form.form-section title="FCL Sea Carrier and Equipment" :required="true">
        <div class="grid grid-cols-1 gap-6 p-6">
            <!-- Carrier Field -->
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
                <select id="carrier" name="carrier_id" x-init="$nextTick(() => { $($el).select2({ placeholder: 'Select carrier...' }) })"
                    class="select2 carrier-select w-full h-10 px-3 py-2 bg-white rounded-lg border border-slate-300 text-base text-gray-900 appearance-none bg-no-repeat bg-right pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m19 9-7 7-7-7'/%3E%3C/svg%3E&quot;); background-size: 1.2rem; background-position: right 0.5rem center;">
                    <option value="" disabled
                        {{ old('carrier_id', $booking->carrier_id ?? '') === '' ? 'selected' : '' }}>Select carrier...
                    </option>
                    @php
                        $selectedCarrier = old('carrier_id', $booking->carrier_id ?? '');
                        $approvedCarriers = \App\Models\LogisticsV2Carriers::approved()
                            ->where('transport_modes', 'ocean_liner')
                            ->orderBy('carrier_company_name')
                            ->get();
                        $approvedNames = $approvedCarriers->pluck('carrier_company_name')->toArray();
                        $hasSelected = false;
                    @endphp
                    @foreach ($approvedCarriers as $c)
                        @if ($c->id === $selectedCarrier)
                            @php $hasSelected = true; @endphp
                        @endif
                        <option value="{{ $c->id }}" {{ $selectedCarrier === $c->id ? 'selected' : '' }}>
                            {{ $c->carrier_company_name }}
                        </option>
                    @endforeach
                    @if (!empty($selectedCarrier) && !$hasSelected)
                        <option value="{{ $selectedCarrier }}" selected>{{ $selectedCarrier }}</option>
                    @endif
                </select>
            </div>

            <!-- Container Type -->
            <x-form.form-select name="container_type" label="Container Type" placeholder="Select type..."
                :options="[
                    '20ft' => '20ft Standard',
                    '40ft' => '40ft Standard',
                    '40ft_hc' => '40ft High Cube',
                    '20ft_ref' => '20ft Reefer',
                    'open_top' => 'Open Top',
                    'flat_rack' => 'Flat Rack',
                ]" :selected="old('container_type', $booking?->items?->first()?->container_type ?? '')" />

            <!-- Dynamic Rows -->
            <div x-data="{
                rows: @js($fclRows),
                containerGroupQty: 1,

                init() {
                    this.rows = this.rows.map(row => this.prepareRow(row));
                    this.containerGroupQty = this.getInitialContainerQty();

                    if (this.rows.some(row => this.isContainer(row)) && this.containerGroupQty > this.rows.length) {
                        for (let i = this.rows.length; i < this.containerGroupQty; i++) {
                            this.rows.push(this.defaultRow({
                                package_type: 'container',
                                qty: 1
                            }));
                        }
                    }

                    this.rows.forEach((row, index) => {
                        if (this.isContainer(row)) {
                            row.qty = 1;
                            this.recalculatePallets(index);
                        } else {
                            this.clearContainerFields(row);
                        }
                    });
                },

                prepareRow(row = {}) {
                    return {
                        package_type: row.package_type || '',
                        container_number: row.container_number || '',
                        qty: this.normalizeQty(row.qty),
                        pallet_count: parseInt(row.pallet_count) || 0,
                        pallets: Array.isArray(row.pallets) ? row.pallets : []
                    };
                },

                defaultRow(overrides = {}) {
                    return this.prepareRow({
                        package_type: '',
                        container_number: '',
                        qty: 1,
                        pallet_count: 0,
                        pallets: [],
                        ...overrides
                    });
                },

                normalizeQty(value) {
                    const qty = parseInt(value);
                    return Number.isFinite(qty) && qty > 0 ? qty : 1;
                },

                isContainer(row) {
                    return row.package_type === 'container';
                },

                addRow() {
                    this.rows.push(this.defaultRow())
                },

                removeRow(index) {
                    if (this.rows.length > 1) {
                        this.rows.splice(index, 1);
                        this.containerGroupQty = this.getContainerRowsCount();
                        this.syncContainerHiddenQty();
                    }
                },

                getInitialContainerQty() {
                    const containerRows = this.rows.filter(row => this.isContainer(row));
                    const maxContainerQty = containerRows.reduce((max, row) => {
                        return Math.max(max, this.normalizeQty(row.qty));
                    }, 0);

                    return Math.max(1, containerRows.length, maxContainerQty || this.normalizeQty(this.rows[0]?.qty));
                },

                getContainerRowsCount() {
                    return Math.max(1, this.rows.filter(row => this.isContainer(row)).length || this.rows.length);
                },

                clearContainerFields(row) {
                    row.container_number = '';
                    row.pallet_count = 0;
                    row.pallets = [];
                },

                hasContainerDetails(row) {
                    const hasContainerNumber = String(row.container_number || '').trim() !== '';
                    const hasPalletCount = (parseInt(row.pallet_count) || 0) > 0;
                    const hasPalletDetails = (row.pallets || []).some(pallet => {
                        return String(pallet.pallet_number || '').trim() !== '' ||
                            String(pallet.description || '').trim() !== '';
                    });

                    return hasContainerNumber || hasPalletCount || hasPalletDetails;
                },

                handlePackageTypeChange(index) {
                    const row = this.rows[index];

                    if (!this.isContainer(row)) {
                        this.clearContainerFields(row);
                        return;
                    }

                    const requestedQty = Math.max(this.rows.length, this.normalizeQty(row.qty));
                    row.qty = 1;
                    this.setContainerGroupQty(requestedQty);
                },

                setContainerGroupQty(value) {
                    const requestedQty = this.normalizeQty(value);
                    const currentQty = this.rows.length;

                    if (requestedQty === currentQty) {
                        this.containerGroupQty = requestedQty;
                        this.syncContainerHiddenQty();
                        return;
                    }

                    if (requestedQty < currentQty) {
                        const rowsToRemove = this.rows.slice(requestedQty);
                        const shouldConfirm = rowsToRemove.some(row => this.hasContainerDetails(row));

                        if (shouldConfirm && !confirm('Reducing QTY will remove container group details you already entered. Continue?')) {
                            this.containerGroupQty = currentQty;
                            return;
                        }

                        this.rows.splice(requestedQty);
                    } else {
                        for (let i = currentQty; i < requestedQty; i++) {
                            this.rows.push(this.defaultRow({
                                package_type: 'container',
                                qty: 1
                            }));
                        }
                    }

                    this.containerGroupQty = requestedQty;
                    this.syncContainerHiddenQty();
                },

                syncContainerHiddenQty() {
                    this.rows.forEach(row => {
                        if (this.isContainer(row)) {
                            row.qty = 1;
                        }
                    });
                },

                updatePallets(index) {
                    this.recalculatePallets(index);
                },

                recalculatePallets(index) {
                    const row = this.rows[index];
                    const totalPallets = parseInt(row.pallet_count) || 0;
                    
                    if (totalPallets === 0) {
                        row.pallets = [];
                        return;
                    }

                    let currentPallets = row.pallets || [];
                    let newPallets = [];
                    let sum = 0;

                    for (let i = 0; i < currentPallets.length; i++) {
                        let val = parseInt(currentPallets[i].pallet_number);
                        let pNum = isNaN(val) || val < 1 ? 1 : val; 
                        
                        if (sum + pNum > totalPallets) {
                            let remaining = totalPallets - sum;
                            if (remaining > 0) {
                                newPallets.push({
                                    pallet_number: remaining,
                                    description: currentPallets[i].description
                                });
                                sum += remaining;
                            }
                            break;
                        } else {
                            newPallets.push({
                                pallet_number: currentPallets[i].pallet_number,
                                description: currentPallets[i].description
                            });
                            sum += pNum;
                        }
                    }

                    while (sum < totalPallets) {
                        newPallets.push({
                            pallet_number: '',
                            description: ''
                        });
                        sum += 1;
                    }

                    row.pallets = newPallets;
                },
                
                validatePalletNumber(index, palletIndex) {
                    const row = this.rows[index];
                    const pallet = row.pallets[palletIndex];
                    const maxPallet = parseInt(row.pallet_count) || 0;
                    const currentValue = parseInt(pallet.pallet_number) || 0;
                    
                    if (currentValue > maxPallet && maxPallet > 0) {
                        alert('Pallet number cannot be greater than ' + maxPallet);
                        pallet.pallet_number = maxPallet;
                    }
                    
                    setTimeout(() => {
                        this.recalculatePallets(index);
                    }, 10);
                }
            }" class="space-y-6">
                
                <template x-for="(row, index) in rows" :key="index">
                    <div class="bg-gray-50/50 p-6 rounded-xl border border-gray-100 space-y-4">
                        <!-- Header -->
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-700" x-text="'Container Group #' + (index + 1)"></h3>
                            <button type="button" @click="removeRow(index)" x-show="rows.length > 1"
                                class="text-red-400 hover:text-red-600 transition-colors text-sm">
                                <i class="fa-solid fa-trash-can"></i> Remove
                            </button>
                        </div>

                        <!-- Main Fields - 4 Columns -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Package Type -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Package Type <span class="text-red-500">*</span></label>
                                <select x-model="row.package_type" @change="handlePackageTypeChange(index)"
                                    :name="'fcl_details[' + index + '][package_type]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none">
                                    <option value="">Select...</option>
                                    <option value="container">Container</option>
                                    <option value="parcel">Parcel</option>
                                    <option value="small_parcel">Small Parcel</option>
                                    <option value="box">Box</option>
                                    <option value="small_box">Small Box</option>
                                    <option value="pallet">Pallet</option>
                                    <option value="crate">Crate</option>
                                    <option value="drum">Drum</option>
                                    <option value="barrel">Barrel</option>
                                    <option value="carton">Carton</option>
                                    <option value="bag">Bag</option>
                                    <option value="roll">Roll</option>
                                    <option value="bundle">Bundle</option>
                                    <option value="skid">Skid</option>
                                    <option value="bale">Bale</option>
                                    <option value="loose">Loose</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <!-- Container Number -->
                            <div x-show="isContainer(row)" x-cloak>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Container Number</label>
                                <input type="text" x-model="row.container_number" :name="'fcl_details[' + index + '][container_number]'"
                                    :disabled="!isContainer(row)"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="e.g. MSCU-1234567">
                            </div>

                            <!-- QTY -->
                            <div>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">QTY <span class="text-red-500">*</span></label>
                                <input type="number"
                                    :value="isContainer(row) ? containerGroupQty : row.qty"
                                    @input="isContainer(row) ? setContainerGroupQty($event.target.value) : row.qty = $event.target.value"
                                    :name="isContainer(row) ? null : 'fcl_details[' + index + '][qty]'"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="Quantity" min="1">
                                <input type="hidden" :name="'fcl_details[' + index + '][qty]'" value="1"
                                    :disabled="!isContainer(row)">
                            </div>

                            <!-- Pallet Count -->
                            <div x-show="isContainer(row)" x-cloak>
                                <label class="text-xs font-medium text-gray-600 mb-1 block">Pallet Count</label>
                                <input type="number" x-model="row.pallet_count" @input="setTimeout(() => updatePallets(index), 10)"
                                    :name="'fcl_details[' + index + '][pallet_count]'"
                                    :disabled="!isContainer(row)"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                    placeholder="Number of pallets" min="0">
                                <span class="text-[10px] text-gray-400" x-show="row.pallet_count > 0" 
                                      x-text="'Max pallet number: ' + row.pallet_count"></span>
                            </div>
                        </div>

                        <!-- Pallet Details -->
                        <div x-show="isContainer(row) && row.pallets && row.pallets.length > 0"
                            class="border-t border-gray-200 pt-4">
                            <!-- Header -->
                            <div class="grid grid-cols-12 gap-3 mb-2">
                                <div class="col-span-3">
                                    <label class="text-xs font-medium text-gray-600">Pallet Number</label>
                                </div>
                                <div class="col-span-8">
                                    <label class="text-xs font-medium text-gray-600">Package Description</label>
                                </div>
                                <div class="col-span-1"></div>
                            </div>

                            <!-- Pallet Rows -->
                            <template x-for="(pallet, palletIndex) in row.pallets" :key="palletIndex">
                                <div class="grid grid-cols-12 gap-3 items-center mt-2">
                                    <!-- Pallet Number Input with Validation -->
                                    <div class="col-span-3">
                                        <input type="number" x-model="pallet.pallet_number" 
                                            @input="validatePalletNumber(index, palletIndex)"
                                            :name="'fcl_details[' + index + '][pallets][' + palletIndex + '][pallet_number]'"
                                            :disabled="!isContainer(row)"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                            placeholder="Pallet number"
                                            min="1"
                                            :max="row.pallet_count">
                                        <span class="text-[10px] text-red-500" 
                                              x-show="parseInt(pallet.pallet_number) > parseInt(row.pallet_count) && row.pallet_count > 0">
                                            Cannot exceed <span x-text="row.pallet_count"></span>
                                        </span>
                                    </div>

                                    <!-- Description Input -->
                                    <div class="col-span-8">
                                        <input type="text" x-model="pallet.description"
                                            :name="'fcl_details[' + index + '][pallets][' + palletIndex + '][description]'"
                                            :disabled="!isContainer(row)"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#0078C8]/20 outline-none"
                                            placeholder="Package description">
                                    </div>

                                    <!-- Barcode Button -->
                                    <div class="col-span-1">
                                        <button type="button" @click="generateBarcode(pallet.description)"
                                            class="text-blue-500 hover:text-blue-700 transition-colors">
                                            <i class="fa-solid fa-barcode"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Show count info -->
                            <div class="text-xs text-gray-400 mt-3" x-show="row.pallets.length > 0">
                                <span x-text="'Showing ' + row.pallets.length + ' of ' + row.pallet_count + ' pallets'"></span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Add Row Button -->
                <div class="flex justify-end">
                    <button type="button" @click="addRow()"
                        class="text-[#0078C8] hover:text-[#006bb0] text-sm font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-plus-circle"></i> Add Another Container Group
                    </button>
                </div>
            </div>
        </div>
    </x-form.form-section>
</div>

<script>
    function generateBarcode(description) {
        if (description) {
            const barcode = 'BAR-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6);
            alert('Barcode generated: ' + barcode + ' for: ' + description);
        } else {
            alert('Please enter a description first');
        }
    }
</script>
