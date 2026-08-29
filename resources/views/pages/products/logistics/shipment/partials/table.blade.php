@php
    $statusConfig = [
        'Pending Confirm' => 'bg-amber-50 text-amber-600 border-amber-100',
        'Confirmed' => 'bg-blue-50 text-blue-600 border-blue-100',
        'In-Transit' => 'bg-purple-50 text-purple-600 border-purple-100',
        'Doc Missing' => 'bg-red-50 text-red-600 border-red-100',
        'Draft' => 'bg-gray-50 text-gray-500 border-gray-200',
    ];

    $typeConfig = [
        'export' => 'bg-blue-50 text-blue-600 border-blue-100',
        'import' => 'bg-green-50 text-green-700 border-green-100',
        'cross_trade' => 'bg-purple-50 text-purple-600 border-purple-100',
    ];

    $typeLabel = [
        'export' => 'Export',
        'import' => 'Import',
        'cross_trade' => 'Cross-Trade',
    ];

    $modeIcon = [
        'fcl_sea' => '<i class="fa-solid fa-ship text-xs mr-1 text-gray-400"></i>',
        'lcl_sea' => '<i class="fa-solid fa-ship text-xs mr-1 text-gray-400"></i>',
        'air' => '<i class="fa-solid fa-plane text-xs mr-1 text-gray-400"></i>',
        'road_truck' => '<i class="fa-solid fa-truck text-xs mr-1 text-gray-400"></i>',
        'multimodal' => '<i class="fa-solid fa-route text-xs mr-1 text-gray-400"></i>',
    ];

    $modeLabel = [
        'fcl_sea' => 'FCL Sea',
        'lcl_sea' => 'LCL Sea',
        'air' => 'Air',
        'road_truck' => 'Road',
        'multimodal' => 'Multimodal',
    ];
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse" style="table-layout: fixed;">
        <thead>
            <tr class="bg-gray-100/50 text-gray-500 border-b border-gray-200">
                <th class="px-3 py-4 text-[10px] font-semibold uppercase tracking-wider" style="width:11%">Booking #</th>
                <th class="px-3 py-4 text-[10px] font-semibold uppercase tracking-wider" style="width:7%">Type</th>
                <th class="px-3 py-4 text-[10px] font-semibold uppercase tracking-wider" style="width:10%">Route</th>
                <th class="px-3 py-4 text-[10px] font-semibold uppercase tracking-wider" style="width:8%">Mode</th>
                <th class="px-3 py-4 text-[10px] font-semibold uppercase tracking-wider" style="width:9%">Carrier</th>
                <th class="px-3 py-4 text-[10px] font-semibold uppercase tracking-wider" style="width:10%">Shipper</th>
                <th class="px-3 py-4 text-[10px] font-semibold uppercase tracking-wider" style="width:9%">ETD</th>
                <th class="px-3 py-4 text-[10px] font-semibold uppercase tracking-wider" style="width:9%">ETA</th>
                <th class="px-3 py-4 text-[10px] font-semibold uppercase tracking-wider" style="width:14%">Cargo</th>
                <th class="px-3 py-4 text-[10px] font-semibold uppercase tracking-wider" style="width:9%">Status</th>
                <th class="px-3 py-4 text-[10px] font-semibold uppercase tracking-wider text-right" style="width:4%">
                    Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($shipments as $b)
                @php
                    $sc = $statusConfig[$b->status] ?? 'bg-gray-50 text-gray-600 border-gray-200';
                    $tc = $typeConfig[$b->booking_type] ?? 'bg-gray-50 text-gray-600 border-gray-200';
                    $typeText = $typeLabel[$b->booking_type] ?? ucfirst($b->booking_type);
                    $icon = $modeIcon[$b->freight_mode] ?? '<i class="fa-solid fa-box text-xs mr-1 text-gray-400"></i>';
                    $modeText = $modeLabel[$b->freight_mode] ?? ucfirst($b->freight_mode);
                @endphp
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-3 py-4 text-sm font-bold text-gray-900 truncate">
                        <a href="{{ route('logistics.manage-booking.v2.show', $b->id) }}"
                            class="hover:text-[#0078C8] hover:underline">
                            {{ $b->booking_number }}
                        </a>
                    </td>
                    <td class="px-3 py-4">
                        <span
                            class="inline-block whitespace-nowrap px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border {{ $tc }}">
                            {{ $typeText }}
                        </span>
                    </td>
                    <td class="px-3 py-4 text-sm font-medium text-gray-800 truncate">{{ $b->origin_port }} →
                        {{ $b->destination_port }}</td>
                    <td class="px-3 py-4 text-sm text-gray-600 whitespace-nowrap">
                        {!! $icon !!}{{ $modeText }}</td>
                    <td class="px-3 py-4 text-sm text-gray-600 truncate">{{ $b->carrier?->carrier_company_name ?: '—' }}
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-600 truncate">{{ $b->exporter ?: '—' }}</td>
                    <td class="px-3 py-4 text-sm text-gray-600 whitespace-nowrap">
                        {{ $b->estimated_time_departure ? \Carbon\Carbon::parse($b->estimated_time_departure)->format('M d, Y') : '—' }}
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-600 whitespace-nowrap">
                        {{ $b->estimated_time_delivery ? \Carbon\Carbon::parse($b->estimated_time_delivery)->format('M d, Y') : '—' }}
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-600 truncate" title="{{ $b->hsCode?->description }}">
                        {{ $b->hsCode?->description ?: '—' }}
                    </td>
                    <td class="px-3 py-4">
                        <span
                            class="inline-block whitespace-nowrap px-2.5 py-1 text-[9px] font-black uppercase tracking-tighter rounded-full border {{ $sc }}">
                            {{ $b->status }}
                        </span>
                    </td>
                    <td class="px-3 py-4 text-right">
                        <div class="relative inline-block" x-data="{ open: false }">
                            <button @click="open = !open" @click.outside="open = false"
                                class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:border-gray-300 hover:bg-gray-50 text-gray-500 transition-all">
                                <i class="fa-solid fa-ellipsis-vertical text-xs"></i>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute right-0 mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-100 z-20 overflow-hidden"
                                style="display:none;">
                                <a href="{{ route('logistics.manage-booking.v2.show', $b->id) }}"
                                    class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                    <i class="fa-solid fa-eye w-3.5 text-gray-400"></i> View
                                </a>
                                <a href="{{ route('logistics.manage-booking.v2.edit', $b->id) }}"
                                    class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors">
                                    <i class="fa-solid fa-pen w-3.5 text-gray-400"></i> Edit
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="px-5 py-14 text-center">
                        <i class="fa-solid fa-ship text-gray-200 text-4xl mb-3 block"></i>
                        <p class="text-sm text-gray-400 font-medium">No bookings found</p>
                        <p class="text-xs text-gray-300 mt-1">Try adjusting your filters</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between gap-4">
    <p class="text-xs text-gray-500">
        Showing <span class="font-semibold text-gray-700">{{ $shipments->firstItem() ?? 0 }}</span>–<span
            class="font-semibold text-gray-700">{{ $shipments->lastItem() ?? 0 }}</span>
        of <span class="font-semibold text-gray-700">{{ $shipments->total() }}</span> bookings
    </p>
    <div class="text-xs">
        {{ $shipments->links() }}
    </div>
</div>
