@props(['legs' => null])

<div x-data="{ 
    legs: {{ json_encode(collect($legs)->map(function($leg) {
        return [
            'origin' => $leg['origin'] ?? '',
            'destination' => $leg['destination'] ?? '',
            'mode' => $leg['mode'] ?? '',
            'etd' => isset($leg['etd']) ? \Carbon\Carbon::parse($leg['etd'])->format('Y-m-d') : '',
            'eta' => isset($leg['eta']) ? \Carbon\Carbon::parse($leg['eta'])->format('Y-m-d') : '',
            'estimated_delivery_date' => isset($leg['estimated_delivery_date']) ? \Carbon\Carbon::parse($leg['estimated_delivery_date'])->format('Y-m-d') : '',
        ];
    })->toArray() ?: [['origin' => '', 'destination' => '', 'etd' => '', 'eta' => '', 'estimated_delivery_date' => '', 'mode' => '']]) }} 
}" class="space-y-4">
    <div class="flex items-center justify-between px-6 pt-6">
        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Routing Legs / Multimodal Tracking</h4>
        <button type="button" @click="legs.push({ origin: '', destination: '', etd: '', eta: '', estimated_delivery_date: '', mode: '' })" 
            class="flex items-center gap-2 px-3 py-1.5 text-[10px] font-bold text-[#0078C8] bg-blue-50 hover:bg-blue-100 rounded-lg transition-all">
            <i class="fa-solid fa-plus-circle"></i> ADD ROUTING LEG
        </button>
    </div>

    <div class="p-6 space-y-4">
        <template x-for="(leg, index) in legs" :key="index">
            <div class="relative bg-gray-50/50 p-4 rounded-xl border border-gray-100 group transition-all hover:shadow-sm">
                {{-- Remove Button --}}
                <button type="button" x-show="legs.length > 1" @click="legs.splice(index, 1)" 
                    class="absolute -top-2 -right-2 w-6 h-6 bg-white border border-red-100 text-red-400 rounded-full flex items-center justify-center hover:bg-red-50 hover:text-red-600 shadow-sm transition-all">
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                </button>

                <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                    {{-- Origin --}}
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block tracking-tight">Origin</label>
                        <input type="text" :name="'legs['+index+'][origin]'" x-model="leg.origin"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-4 focus:ring-[#0078C8]/5 focus:border-[#0078C8] outline-none transition-all" 
                            placeholder="e.g. USA, Port Qasim">
                    </div>

                    {{-- Destination --}}
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block tracking-tight">Destination</label>
                        <input type="text" :name="'legs['+index+'][destination]'" x-model="leg.destination"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-4 focus:ring-[#0078C8]/5 focus:border-[#0078C8] outline-none transition-all" 
                            placeholder="e.g. Port Qasim, Peshawar">
                    </div>

                    {{-- Mode --}}
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block tracking-tight">Mode</label>
                        <select :name="'legs['+index+'][mode]'" x-model="leg.mode"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-4 focus:ring-[#0078C8]/5 focus:border-[#0078C8] outline-none bg-white transition-all">
                            <option value="">Select mode...</option>
                            <option value="sea">Sea Freight</option>
                            <option value="air">Air Freight</option>
                            <option value="road">Road / Truck</option>
                            <option value="rail">Rail</option>
                        </select>
                    </div>

                    {{-- ETD --}}
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block tracking-tight">ETD</label>
                        <input type="date" :name="'legs['+index+'][etd]'" x-model="leg.etd"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-4 focus:ring-[#0078C8]/5 focus:border-[#0078C8] outline-none transition-all">
                    </div>

                    {{-- ETA --}}
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block tracking-tight">ETA</label>
                        <input type="date" :name="'legs['+index+'][eta]'" x-model="leg.eta"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-4 focus:ring-[#0078C8]/5 focus:border-[#0078C8] outline-none transition-all">
                    </div>

                    {{-- Est. Delivery --}}
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase mb-1 block tracking-tight">Est. Delivery</label>
                        <input type="date" :name="'legs['+index+'][estimated_delivery_date]'" x-model="leg.estimated_delivery_date"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-4 focus:ring-[#0078C8]/5 focus:border-[#0078C8] outline-none transition-all">
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
