<div class="overflow-x-auto">
    <table class="w-full text-left">
        <thead class="bg-slate-50 border-b border-slate-100">
            <tr>
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center w-16">ID
                </th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Carrier</th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Mode
                </th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Pickup
                </th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Route</th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Status
                </th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">ETA
                </th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Actions
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @if (isset($dynamic) && $dynamic)
                <template x-for="shipment in shipments" :key="shipment.id">
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-5 py-4 text-xs font-black text-slate-900 text-center" x-text="shipment.id"></td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-slate-100 rounded flex items-center justify-center">
                                    <i class="fa-solid text-slate-400 text-xs"
                                        :class="{
                                            'fa-plane': shipment.mode === 'Air',
                                            'fa-ship': shipment.mode === 'Ocean',
                                            'fa-truck': shipment.mode === 'Inland'
                                        }"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-900" x-text="shipment.carrier"></p>
                                    <p class="text-[9px] text-slate-400 font-medium" x-text="'REF: ' + shipment.ref">
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <div class="inline-flex items-center justify-center w-8 h-8 rounded-full"
                                :class="{
                                    'bg-cyan-50 text-cyan-600': shipment.mode === 'Air',
                                    'bg-blue-50 text-[#0078C8]': shipment.mode === 'Ocean',
                                    'bg-orange-50 text-orange-600': shipment.mode === 'Inland'
                                }">
                                <i class="fa-solid text-xs"
                                    :class="{
                                        'fa-plane-up': shipment.mode === 'Air',
                                        'fa-anchor': shipment.mode === 'Ocean',
                                        'fa-truck': shipment.mode === 'Inland'
                                    }"></i>
                            </div>
                            <p class="text-[8px] font-bold uppercase mt-1" x-text="shipment.mode"></p>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <span
                                class="px-3 py-1 bg-[#0078C8] text-white text-[10px] font-black rounded uppercase">Schedule</span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold text-slate-600" x-text="shipment.origin"></span>
                                <i class="fa-solid fa-arrow-right text-slate-300 text-[9px]"></i>
                                <span class="text-[10px] font-bold text-slate-900" x-text="shipment.destination"></span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <span class="px-2 py-1 rounded-full text-[9px] font-black uppercase tracking-tighter border"
                                :class="{
                                    'bg-slate-100 text-slate-500 border-slate-200':     shipment.status === 'Draft',
                                    'bg-blue-50 text-blue-600 border-blue-100':          shipment.status === 'Booked',
                                    'bg-violet-50 text-violet-600 border-violet-100':    shipment.status === 'Picked Up',
                                    'bg-amber-50 text-amber-600 border-amber-100':       shipment.status === 'In Transit',
                                    'bg-emerald-50 text-emerald-600 border-emerald-100': shipment.status === 'Delivered'
                                }"
                                x-text="shipment.status"></span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <p class="text-[10px] font-bold text-slate-900" x-text="shipment.eta"></p>
                            <p class="text-[9px] text-green-500 font-medium">On-Time</p>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a
                                    :href="'{{ url('/logistics/manage-booking') }}/' + shipment.db_id"
                                    class="w-7 h-7 bg-white border border-slate-200 rounded flex items-center justify-center text-slate-400 hover:border-[#0078C8] hover:text-[#0078C8] transition-all shadow-sm">
                                    <i class="fa-solid fa-eye text-[10px]"></i>
                                </a>
                                <a
                                    :href="'{{ url('/logistics/manage-booking') }}/' + shipment.db_id + '/edit'"
                                    class="w-7 h-7 bg-white border border-slate-200 rounded flex items-center justify-center text-slate-400 hover:border-[#0078C8] hover:text-[#0078C8] transition-all shadow-sm">
                                    <i class="fa-solid fa-pen text-[10px]"></i>
                                </a>
                                <button
                                    @click="deleteShipment(shipment.db_id)"
                                    class="w-7 h-7 bg-white border border-slate-200 rounded flex items-center justify-center text-rose-400 hover:border-rose-500 hover:text-rose-500 transition-all shadow-sm">
                                    <i class="fa-solid fa-trash-can text-[10px]"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
                {{-- Show empty state if no shipments --}}
                <template x-if="shipments.length === 0 && !loading">
                    <tr>
                        <td colspan="9" class="px-5 py-20 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                    <i class="fa-solid fa-box-open text-slate-300 text-2xl"></i>
                                </div>
                                <h3 class="text-slate-900 font-bold">No Shipments Found</h3>
                                <p class="text-slate-500 text-xs mt-1">There are no bookings for this shipping mode yet.
                                </p>
                            </div>
                        </td>
                    </tr>
                </template>
                {{-- Show loading state --}}
                <template x-if="loading">
                    <tr>
                        <td colspan="9" class="px-5 py-20 text-center">
                            <div class="flex flex-col items-center">
                                <div
                                    class="w-10 h-10 border-4 border-[#0078C8]/20 border-t-[#0078C8] rounded-full animate-spin">
                                </div>
                                <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mt-4">Loading
                                    Data...</p>
                            </div>
                        </td>
                    </tr>
                </template>
            @else
                {{-- Static Placeholder Row for {{ $mode }} --}}
                <tr class="hover:bg-slate-50/50 transition-colors group">
                    <td class="px-5 py-4 text-xs font-black text-slate-900 text-center">
                        SHP-100{{ $mode == 'Air' ? '1' : ($mode == 'Ocean' ? '2' : '3') }}</td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-slate-100 rounded flex items-center justify-center">
                                @if ($mode == 'Air')
                                    <i class="fa-solid fa-plane text-slate-400 text-xs"></i>
                                @elseif($mode == 'Ocean')
                                    <i class="fa-solid fa-ship text-slate-400 text-xs"></i>
                                @else
                                    <i class="fa-solid fa-truck text-slate-400 text-xs"></i>
                                @endif
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900">
                                    @if ($mode == 'Air')
                                        Emirates SkyCargo
                                    @elseif($mode == 'Ocean')
                                        Maersk Line
                                    @else
                                        Swift Logistics
                                    @endif
                                </p>
                                <p class="text-[9px] text-slate-400 font-medium">REF: {{ $mode }}-992019</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-center">
                        <div
                            class="inline-flex items-center justify-center w-8 h-8 rounded-full 
                            {{ $mode == 'Air' ? 'bg-cyan-50 text-cyan-600' : ($mode == 'Ocean' ? 'bg-blue-50 text-[#0078C8]' : 'bg-orange-50 text-orange-600') }}">
                            @if ($mode == 'Air')
                                <i class="fa-solid fa-plane-up text-xs"></i>
                            @elseif($mode == 'Ocean')
                                <i class="fa-solid fa-anchor text-xs"></i>
                            @else
                                <i class="fa-solid fa-truck text-xs"></i>
                            @endif
                        </div>
                        <p class="text-[8px] font-bold uppercase mt-1">{{ $mode }}</p>
                    </td>
                    <td class="px-5 py-4 text-center">
                        <span
                            class="px-3 py-1 bg-[#0078C8] text-white text-[10px] font-black rounded uppercase">Schedule</span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold text-slate-600">ORIGIN</span>
                            <i class="fa-solid fa-arrow-right text-slate-300 text-[9px]"></i>
                            <span class="text-[10px] font-bold text-slate-900">DESTINATION</span>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-center">
                        <span
                            class="px-2 py-1 bg-amber-50 text-amber-600 rounded-full text-[9px] font-black uppercase tracking-tighter border border-amber-100">In-Transit</span>
                    </td>
                    <td class="px-5 py-4 text-center">
                        <p class="text-[10px] font-bold text-slate-900">Mar 15, 2024</p>
                        <p class="text-[9px] text-green-500 font-medium">On-Time</p>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button
                                class="w-7 h-7 bg-white border border-slate-200 rounded flex items-center justify-center text-slate-400 hover:border-[#0078C8] hover:text-[#0078C8] transition-all shadow-sm">
                                <i class="fa-solid fa-eye text-[10px]"></i>
                            </button>
                            <button
                                class="w-7 h-7 bg-white border border-slate-200 rounded flex items-center justify-center text-slate-400 hover:border-[#0078C8] hover:text-[#0078C8] transition-all shadow-sm">
                                <i class="fa-solid fa-pen text-[10px]"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="px-5 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between mt-4 rounded-b-lg">
    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
        @if (isset($dynamic) && $dynamic)
            Showing <span x-text="shipments.length"></span> Results for <span x-text="activeMode"></span> Booking
        @else
            Showing 1 Result for {{ $mode }} Booking
        @endif
    </p>
</div>
