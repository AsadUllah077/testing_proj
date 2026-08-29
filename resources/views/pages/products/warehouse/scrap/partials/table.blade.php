{{-- resources/views/pages/products/warehouse/scrap/partials/table.blade.php --}}
<div class="overflow-x-auto">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="py-3 px-4 text-left">
                    <input type="checkbox" id="checkAllScrap" class="h-4 w-4 text-[#0078C8] rounded cursor-pointer">
                </th>
                <th data-col="scrap_id" class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scrap ID</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Description</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Category</th>
                <th data-col="type_of_material" class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Type</th>
                <th data-col="source" class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Source</th>
                <th data-col="weight" class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Weight</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Quantity</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th data-col="created_at" class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Created At</th>
                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($scraps as $scrap)
                @php
                    $statusBadge = match ($scrap->status) {
                        'in_storage'        => 'bg-blue-100 text-blue-800',
                        'pending_disposal'  => 'bg-yellow-100 text-yellow-800',
                        'pending_recycling' => 'bg-orange-100 text-orange-800',
                        'transferred'       => 'bg-purple-100 text-purple-800',
                        'disposed'          => 'bg-gray-100 text-gray-700',
                        default             => 'bg-gray-100 text-gray-700',
                    };
                    $statusText = match ($scrap->status) {
                        'in_storage'        => 'In Storage',
                        'pending_disposal'  => 'Pending Disposal',
                        'pending_recycling' => 'Pending Recycling',
                        'transferred'       => 'Transferred',
                        'disposed'          => 'Disposed',
                        default             => $scrap->status,
                    };
                @endphp

                <tr class="hover:bg-gray-50" data-scrap-id="{{ $scrap->id }}">

                    {{-- Checkbox --}}
                    <td class="py-3 px-4">
                        @if ($scrap->canDelete())
                            <input type="checkbox"
                                   class="rowCheckScrap h-4 w-4 text-[#0078C8] rounded cursor-pointer"
                                   value="{{ $scrap->id }}">
                        @endif
                    </td>

                    {{-- Scrap ID --}}
                    <td data-col="scrap_id" class="py-3 px-4">
                        <a href="{{ route('warehouse.scrap.details', $scrap->id) }}"
                           class="text-sm font-medium text-gray-900 hover:text-[#0078C8]">
                            {{ $scrap->scrap_id }}
                        </a>
                    </td>

                    {{-- Item Description --}}
                    <td class="py-3 px-4">
                        <div class="text-sm text-gray-900">{{ $scrap->item_description }}</div>
                    </td>

                    {{-- Category --}}
                    <td class="py-3 px-4 hidden sm:table-cell">
                        <div class="text-sm text-gray-900">{{ $scrap->scrapCategory?->name ?? 'N/A' }}</div>
                    </td>

                    {{-- Type --}}
                    <td data-col="type_of_material" class="py-3 px-4 hidden sm:table-cell">
                        <div class="text-sm text-gray-900">{{ $scrap->scrapType?->name ?? 'N/A' }}</div>
                    </td>

                    {{-- Source --}}
                    <td data-col="source" class="py-3 px-4 hidden lg:table-cell">
                        <div class="text-sm text-gray-900 capitalize">{{ str_replace('_', ' ', $scrap->source) }}</div>
                    </td>

                    {{-- Weight --}}
                    <td data-col="weight" class="py-3 px-4 hidden sm:table-cell">
                        <div class="text-sm font-medium text-gray-900">{{ number_format($scrap->weight, 2) }} kg</div>
                    </td>

                    {{-- Quantity --}}
                    <td class="py-3 px-4 hidden sm:table-cell">
                        <div class="text-sm font-medium text-gray-900">{{$scrap->quantity ?? 0 }}</div>
                    </td>

                    {{-- Status --}}
                    <td class="py-3 px-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusBadge }}">
                            {{ $statusText }}
                        </span>
                    </td>

                    {{-- Created At --}}
                    <td data-col="created_at" class="py-3 px-4 hidden lg:table-cell">
                        <div class="text-sm text-gray-500">{{ $scrap->created_at->format('d M Y') }}</div>
                    </td>

                    {{-- Actions --}}
                    <td class="py-3 px-4">
                        <div class="relative">
                            <button type="button"
                                    class="dropdown-btn text-gray-400 hover:text-gray-600 focus:outline-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                </svg>
                            </button>

                            <div class="dropdown-menu hidden absolute right-0 mt-1 w-48 bg-white border border-gray-200 rounded shadow-md z-10">
                                <ul class="py-1">

                                    {{-- View --}}
                                    <li>
                                        <a href="{{ route('warehouse.scrap.details', $scrap->id) }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            View Details
                                        </a>
                                    </li>

                                    {{-- Edit (only if not disposed) --}}
                                    @if (!$scrap->isDisposed())
                                        @if(auth()->user()->hasPermission('warehouse.scrap.edit'))
                                        <li>
                                            <a href="{{ route('warehouse.scrap.edit', $scrap->id) }}"
                                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                Edit
                                            </a>
                                        </li>
                                        @endif
                                    @endif

                                    {{-- Delete (only if allowed) --}}
                                    @if ($scrap->canDelete())
                                        @if(auth()->user()->hasPermission('warehouse.scrap.delete'))
                                        <li>
                                            <button type="button"
                                                    onclick="deleteScrap({{ $scrap->id }}, '{{ route('warehouse.scrap.destroy', $scrap->id) }}')"
                                                    class="w-full text-left block px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700">
                                                Delete
                                            </button>
                                        </li>
                                        @endif
                                    @endif

                                </ul>
                            </div>
                        </div>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="10" class="py-12 px-4 text-center">
                        <div class="flex flex-col items-center justify-center gap-3">
                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <p class="text-sm font-medium text-gray-500">No scrap materials found</p>
                            <a href="{{ route('warehouse.scrap.create') }}"
                               class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded hover:bg-[#006bb0] transition-colors">
                                Add New Scrap Material
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if (isset($scraps) && $scraps->hasPages())
    <div id="pagination-wrapper" class="p-6 border-t border-gray-200">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-sm text-gray-600">
                Showing <span class="font-medium">{{ $scraps->firstItem() }}</span>
                to <span class="font-medium">{{ $scraps->lastItem() }}</span>
                of <span class="font-medium">{{ $scraps->total() }}</span> results
            </div>
            <div>
                {{ $scraps->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endif
