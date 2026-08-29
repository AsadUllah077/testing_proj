<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <div class="flex items-center">
                        <input id="checkAllParts" type="checkbox"
                            class="h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 rounded">
                        <label for="checkAllParts" class="ml-2 text-xs text-gray-600">Select</label>
                    </div>
                </th>
                <th scope="col" data-col="part_number" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                    Part Number
                </th>
                <th scope="col" data-col="name" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Part Name
                </th>
                <th scope="col" data-col="category" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                    Category
                </th>
                <th scope="col" data-col="location" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                    Location
                </th>
                <th scope="col" data-col="quantity" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                    Quantity
                </th>
                <th scope="col" data-col="status" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                </th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($parts as $part)
            <tr class="hover:bg-gray-50 transition-colors" data-part-id="{{ $part->id }}">
                <td class="px-4 py-3 whitespace-nowrap">
                    <input type="checkbox" class="rowCheckPart h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 rounded"
                           value="{{ $part->id }}">
                </td>
                <td data-col="part_number" class="px-4 py-3 whitespace-nowrap text-sm font-mono text-gray-900 hidden sm:table-cell">
                    <a href="{{ route('warehouse.parts.show', $part->id) }}" class="hover:text-[#0078C8]">
                        {{ $part->part_number }}
                    </a>
                </td>
                <td data-col="name" class="px-4 py-3 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">
                        <a href="{{ route('warehouse.parts.show', $part->id) }}" class="hover:text-[#0078C8]">
                            {{ $part->name }}
                        </a>
                    </div>
                    @if($part->description)
                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ Str::limit($part->description, 50) }}</div>
                    @endif
                </td>
                <td data-col="category" class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 hidden sm:table-cell">
                    {{ $part->category ?? 'N/A' }}
                </td>
                <td data-col="location" class="px-4 py-3 whitespace-nowrap hidden lg:table-cell">
                    <div class="text-sm text-gray-900">{{ $part->warehouseLocation->name ?? 'N/A' }}</div>
                    @if($part->rack_position)
                        <div class="text-xs text-gray-500">
                            {{ $part->rack_position }}
                            @if($part->shelf)/ {{ $part->shelf }}@endif
                        </div>
                    @endif
                </td>
                <td data-col="quantity" class="px-4 py-3 whitespace-nowrap hidden sm:table-cell">
                    <div class="text-sm font-medium text-gray-900">{{ $part->quantity }}</div>
                    @if($part->needsReorder())
                        <div class="text-xs text-orange-600">Reorder at {{ $part->reorder_level }}</div>
                    @endif
                </td>
                <td data-col="status" class="px-4 py-3 whitespace-nowrap">
                    @php
                        $statusClass = match($part->status) {
                            'IN_STOCK' => 'bg-green-100 text-green-800',
                            'LOW_STOCK' => 'bg-yellow-100 text-yellow-800',
                            'OUT_OF_STOCK' => 'bg-red-100 text-red-800',
                            'REQUESTED' => 'bg-blue-100 text-blue-800',
                            'RESERVED' => 'bg-purple-100 text-purple-800',
                            default => 'bg-gray-100 text-gray-800'
                        };
                    @endphp
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusClass }}">
                        {{ str_replace('_', ' ', $part->status) }}
                    </span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                    <div class="flex items-center space-x-2">
                        @if($part->quantity > 0)
                            <button type="button" onclick="showAddToProductModal({{ $part->id }}, '{{ addslashes($part->name) }}', {{ $part->quantity }})"
                                class="px-3 py-1.5 bg-[#0078C8] text-white text-xs font-medium rounded hover:bg-[#006bb0] whitespace-nowrap">
                                ADD TO PRODUCT
                            </button>
                        @else
                            <button type="button" disabled
                                class="px-3 py-1.5 bg-gray-300 text-gray-500 text-xs font-medium rounded cursor-not-allowed whitespace-nowrap">
                                OUT OF STOCK
                            </button>
                        @endif

                        <!-- Fixed Dropdown Menu -->
                        <div class="relative inline-block text-left">
                            <button type="button"
                                class="dropdown-btn inline-flex items-center text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none p-1"
                                onclick="toggleDropdownMenu(this)">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                </svg>
                            </button>

                            <!-- Dropdown menu - positioned relative to its container -->
                            <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg z-50">
                                <div class="py-1">
                                    <a href="{{ route('warehouse.parts.show', $part->id) }}"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-eye mr-2 text-blue-600"></i>View Details
                                    </a>
                                    @if(auth()->user()->hasPermission('warehouse.parts.edit'))
                                    <a href="{{ route('warehouse.parts.edit', $part->id) }}"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-edit mr-2 text-green-600"></i>Edit Part
                                    </a>
                                    @endif
                                    <div class="border-t border-gray-100 my-1"></div>
                                    @if(auth()->user()->hasPermission('warehouse.parts.delete'))
                                    <button onclick="confirmDelete(this)"
                                        data-url="{{ route('warehouse.parts.destroy', $part->id) }}"
                                        data-row="tr[data-part-id='{{ $part->id }}']"
                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                        <i class="fas fa-trash mr-2"></i>Delete
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                    <div class="flex flex-col items-center justify-center">
                        <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p class="text-gray-600 font-medium text-lg">No parts found</p>
                        <p class="text-gray-500 text-sm mt-1 mb-4">Click the ADD NEW button to create your first part</p>
                        <a href="{{ route('warehouse.parts.create') }}"
                            class="px-5 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors inline-flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            ADD NEW PART
                        </a>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
@if (isset($parts) && $parts->hasPages())
    <div id="pagination-wrapper" class="px-4 py-3 border-t border-gray-200 bg-white">
        {{ $parts->withQueryString()->links() }}
    </div>
@endif

<script>
    // Simple function to toggle dropdown menus
    function toggleDropdownMenu(button) {
        // Close all other dropdowns first
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            if (menu !== button.nextElementSibling) {
                menu.classList.add('hidden');
            }
        });

        // Toggle current dropdown
        const dropdown = button.nextElementSibling;
        dropdown.classList.toggle('hidden');

        // Stop propagation to prevent document click handler from closing it immediately
        event.stopPropagation();
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.dropdown-btn') && !event.target.closest('.dropdown-menu')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
        }
    });

    // Re-initialize checkboxes after table reload
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof initializeCheckboxes === 'function') {
            initializeCheckboxes();
            updateSelectedCount();
        }
    });

    // Transfer modal function (if needed)
    function showTransferModal(partId, partName, currentLocation, availableQty) {
        alert('Transfer functionality - Part: ' + partName + '\nCurrent Location: ' + currentLocation + '\nAvailable: ' + availableQty);
    }
</script>
