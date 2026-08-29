<table class="min-w-full divide-y divide-gray-100 text-sm">
    <thead class="bg-gray-50 text-xs uppercase text-gray-500 tracking-wider">
        <tr>
            <th class="px-5 py-3 text-left">#</th>
            <th class="px-5 py-3 text-left">Barcode</th>
            <th class="px-5 py-3 text-left">Location</th>
            <th class="px-5 py-3 text-left">Warehouse Location</th>
            <th class="px-5 py-3 text-left">Color</th>
            <th class="px-5 py-3 text-left">Product</th>
            <th class="px-5 py-3 text-left">Scanned By</th>
            <th class="px-5 py-3 text-left">Scanned At</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-50">
        @forelse ($logs as $log)
            <tr class="hover:bg-gray-50 transition-colors">

                {{-- Row # --}}
                <td class="px-5 py-3 text-gray-400 text-xs">{{ $logs->firstItem() + $loop->index }}</td>

                {{-- Barcode --}}
                <td class="px-5 py-3">
                    <span class="font-mono font-semibold text-gray-800">{{ $log->barcode }}</span>
                </td>

                {{-- Location A / B badge --}}
                <td class="px-5 py-3">
                    @if ($log->location === 'A')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-900 border border-yellow-300">
                            <span class="w-2 h-2 rounded-full bg-yellow-400 inline-block"></span>
                            Location A
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-900 border border-green-300">
                            <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                            Location B
                        </span>
                    @endif
                </td>

                {{-- Warehouse Location Name (Photography / Warehouse#2) --}}
                <td class="px-5 py-3">
                    @if ($log->warehouse_location_name)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                            {{ $log->location === 'A'
                                ? 'bg-yellow-50 text-yellow-900 border border-yellow-200'
                                : 'bg-green-50 text-green-900 border border-green-200' }}">
                            <i class="fa-solid fa-warehouse text-xs"></i>
                            {{ $log->warehouse_location_name }}
                        </span>
                    @else
                        <span class="text-gray-400 text-xs italic">—</span>
                    @endif
                </td>

                {{-- Color Badge --}}
                <td class="px-5 py-3">
                    @if ($log->color === 'Yellow')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-yellow-400 text-yellow-950">
                            ● Yellow
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-green-500 text-white">
                            ● Green
                        </span>
                    @endif
                </td>

                {{-- Product --}}
                <td class="px-5 py-3">
                    @if ($log->product)
                        <a href="{{ route('warehouse.products.show', $log->product_id) }}"
                            class="text-[#0078C8] hover:underline font-medium">
                            {{ $log->product->title }}
                        </a>
                        <span class="text-gray-400 text-xs ml-1">({{ $log->product->unique_id }})</span>
                    @else
                        <span class="text-gray-400 italic text-xs">No match</span>
                    @endif
                </td>

                {{-- Scanned By --}}
                <td class="px-5 py-3 text-gray-600">
                    {{ $log->scannedBy?->first_name }} {{ $log->scannedBy?->last_name ?? '' }}
                </td>

                {{-- Scanned At --}}
                <td class="px-5 py-3 text-gray-500 whitespace-nowrap">
                    {{ $log->scanned_at->format('d M Y, h:i:s A') }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="px-5 py-12 text-center text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5h2.25V6.75m12.75-2.25h2.25V6.75M3.75 19.5h2.25v-2.25m12.75 2.25h2.25v-2.25M9 8.25h6M9 12h6m-6 3.75h6" />
                    </svg>
                    <p class="font-medium">No scan logs found</p>
                    <p class="text-xs mt-1">Adjust your filters or start scanning at one of the scan stations.</p>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
