@php
    $showSoldQuantity = request('status') === 'sold';
    $productListUser = auth()->user();
    $canEditProducts = $productListUser?->hasPermission('warehouse.products.edit') ?? false;
    $canShowProducts = $productListUser?->hasPermission('warehouse.products.show') ?? false;
    $canViewProductHistory = $productListUser?->hasPermission('warehouse.products.history') ?? false;
    $canPrintProductQr = $productListUser?->hasAllPermissions(['warehouse.products.qrcode', 'warehouse.products.print-qrcode']) ?? false;
    $canPrintProductSpecification = $productListUser?->hasPermission('warehouse.products.show') ?? false;
    $canExtractProductPart = $productListUser?->hasPermission('warehouse.products.extract-part') ?? false;
    $canDeleteProducts = $productListUser?->hasPermission('warehouse.products.delete') ?? false;
@endphp

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <div class="flex items-center">
                        <input id="checkAll" type="checkbox"
                            class="h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 rounded">
                        <label for="checkAll" class="ml-2 text-xs text-gray-600">Select</label>
                    </div>
                </th>
                <th scope="col"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">
                    #
                </th>
                <th scope="col" data-col="unique_id"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    @php
                        $isActive = request('sort') === 'unique_id';
                        $nextDir = $isActive && request('direction') === 'asc' ? 'desc' : 'asc';
                        $sortUrl = request()->fullUrlWithQuery(['sort' => 'unique_id', 'direction' => $nextDir]);
                    @endphp
                    <a href="{{ $sortUrl }}"
                        class="flex items-center gap-1 hover:text-[#0078C8] transition-colors {{ $isActive ? 'text-[#0078C8]' : '' }}">
                        Unique ID
                        @if ($isActive)
                            @if (request('direction') === 'asc')
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M5 15l7-7 7 7" />
                                </svg>
                            @else
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            @endif
                        @else
                            <svg class="w-3 h-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4" />
                            </svg>
                        @endif
                    </a>
                </th>
                <th scope="col" data-col="dpn_sn_number"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                    DPN/SN Number
                </th>
                <th scope="col" data-col="category"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                    Category
                </th>
                <th scope="col" data-col="title"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Title
                </th>
                <th scope="col" data-col="location"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                    Location
                </th>
                <th scope="col" data-col="assigned_to"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                    Assigned To
                </th>
                <th scope="col" data-col="disposition"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                    Disposition
                </th>
                <th scope="col" data-col="work_flow_stage"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                    Work Flow Stage
                </th>
                {{-- <th scope="col" data-col="specification"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                    Specification
                </th> --}}
                <th scope="col"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                </th>
                @if ($showSoldQuantity)
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Sold Qty
                    </th>
                @endif
                <th scope="col" data-col="last_action"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Last Action
                </th>
                <th scope="col"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Action
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($products as $product)
                <tr class="hover:bg-gray-50 transition-colors" data-id="{{ $product->id }}"
                    data-status="{{ $product->status }}" data-assigned="{{ $product->assigned_to ? '1' : '0' }}"
                    data-manual="{{ $product->manual_status ?? 0 }}" data-unique-id="{{ $product->unique_id }}"
                    data-title="{{ $product->title }}">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <input type="checkbox"
                            class="rowCheck h-4 w-4 text-[#0078C8] focus:ring-[#0078C8] border-gray-300 rounded"
                            value="{{ $product->id }}">
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-400 font-medium">
                        {{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}
                    </td>
                    <td data-col="unique_id" class="px-4 py-3 whitespace-nowrap text-sm font-mono text-gray-900">
                        @if ($canShowProducts)
                            <a href="{{ route('warehouse.products.show', $product->id) }}"
                                class="font-semibold  hover:underline">
                                {{ $product->unique_id }}
                            </a>
                        @else
                            {{ $product->unique_id }}
                        @endif
                        <div class="sm:hidden mt-0.5 text-xs text-gray-500 font-sans">
                            {{ $product->category->name ?? '' }}{{ $product->category && $product->warehouseLocation ? ' · ' : '' }}{{ $product->warehouseLocation->name ?? '' }}
                        </div>
                    </td>
                    <td data-col="dpn_sn_number"
                        class="px-4 py-3 whitespace-nowrap text-sm font-mono text-gray-700 hidden md:table-cell">
                        {{ $product->dpn_sn_number ?? 'N/A' }}
                    </td>
                    <td data-col="category"
                        class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 hidden sm:table-cell">
                        {{ $product->category->name ?? 'N/A' }}
                    </td>
                    <td data-col="title" class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                        <div class="max-w-xs truncate" title="{{ $product->title }}">
                            {{ $product->title }}
                        </div>
                    </td>
                    <td data-col="location"
                        class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 hidden sm:table-cell">
                        {{ $product->warehouseLocation->name ?? 'N/A' }}
                    </td>
                    <td data-col="assigned_to"
                        class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 hidden sm:table-cell">
                        @if ($product->assignedTo)
                            <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                                {{ $product->assignedTo->full_name }}
                            </span>
                        @else
                            <span class="text-gray-400">Unassigned</span>
                        @endif
                    </td>
                    <td data-col="disposition" class="px-4 py-3 whitespace-nowrap hidden sm:table-cell">
                        @if ($product->disposition)
                            @php
                                $dispositionClass = match (strtolower($product->disposition->name)) {
                                    'pass', 'approved', 'completed' => 'bg-green-100 text-green-800',
                                    'fail', 'rejected', 'failed' => 'bg-red-100 text-red-800',
                                    'pending', 'waiting' => 'bg-yellow-100 text-yellow-800',
                                    'ab', 'abandoned' => 'bg-blue-100 text-blue-800',
                                    default => 'bg-gray-100 text-gray-800',
                                };
                            @endphp
                            <span class="px-2 py-1 text-xs font-medium {{ $dispositionClass }} rounded-full">
                                {{ $product->disposition->name }}
                            </span>
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </td>
                    <td data-col="work_flow_stage"
                        class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 hidden sm:table-cell">
                        {{ $product->workflowStage->name ?? 'N/A' }}
                    </td>
                    {{-- <td data-col="specification"
                        class="px-4 py-3 text-sm text-gray-700 hidden sm:table-cell max-w-[180px]">
                        @if($product->specifications && count($product->specifications) > 0)
                            <div class="space-y-0.5">
                                @foreach($product->specifications as $spec)
                                    @php
                                        $sKey   = is_array($spec) ? ($spec['key']   ?? '') : $spec;
                                        $sValue = is_array($spec) ? ($spec['value'] ?? '') : '';
                                    @endphp
                                    @if($sKey)
                                        <div class="text-xs">
                                            <span class="font-medium text-gray-600">{{ $sKey }}:</span>
                                            <span class="text-gray-800">{{ $sValue ?: '—' }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td> --}}
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                        @php
                            $status = strtolower($product->status ?? '');
                            $isWarehouseIssued = $status == 'issued'
                                || ((int) ($product->manual_status ?? 0) === 1
                                    && ! empty($product->assigned_to)
                                    && $status !== 'hold');
                        @endphp
                        @if ($product->sold_status == 1)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                                Sold
                            </span>
                        @elseif($showSoldQuantity && $product->manual_status == 1 && (int) ($product->sold_quantity ?? 0) > 0)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700">
                                Partially Sold
                            </span>
                        @elseif($product->scrap_status == 1)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">
                                Scrap
                            </span>
                        @elseif($isWarehouseIssued)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">
                                Issued
                            </span>
                        @elseif(in_array($status, ['active', 'ready for sale']))
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">
                                Ready for Sale
                            </span>
                        @elseif($status == 'hold')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">
                                Hold
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
                                Not Ready for Sale
                            </span>
                        @endif
                    </td>
                    @if ($showSoldQuantity)
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                            {{ number_format($product->manual_status == 1 ? (int) ($product->sold_quantity ?? 0) : 1) }}
                        </td>
                    @endif
                    <td data-col="last_action" class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                        {{ $product->last_action ?? 'N/A' }}
                    </td>
                    @php
                        $canEditProduct = $canEditProducts && !$product->sold_status;
                        $canShowProduct = $canShowProducts;
                        $canViewHistory = $canViewProductHistory;
                        $canPrintQr = $canPrintProductQr && $product->manual_status == 1;
                        $canPrintSpecification = $canPrintProductSpecification;
                        $canExtractPart = $canExtractProductPart && !$product->sold_status;
                        $canDeleteProduct = $canDeleteProducts;
                        $canAnyProductAction = $canEditProduct || $canShowProduct || $canViewHistory || $canPrintQr || $canPrintSpecification || $canExtractPart || $canDeleteProduct;
                    @endphp
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                        @if ($canAnyProductAction)
                            <div class="relative inline-block">
                                <button
                                    class="dropdown-btn inline-flex items-center text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none">
                                    <span class="sr-only">Actions</span>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                    </svg>
                                </button>
                                <div
                                    class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded shadow-lg z-10">
                                    <div class="py-1">
                                        @if ($canEditProduct)
                                        <a href="{{ route('warehouse.products.edit', $product->id) }}"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Edit
                                        </a>
                                        @endif
                                        @if ($canShowProduct)
                                            <a href="{{ route('warehouse.products.show', $product->id) }}"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                View Details
                                            </a>
                                        @endif
                                        @if ($canViewHistory)
                                            <a href="{{ route('warehouse.products.history', $product->id) }}"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                View History
                                            </a>
                                        @endif
                                        @if ($canPrintQr || $canPrintSpecification || $canExtractPart || $canDeleteProduct)
                                            <div class="border-t border-gray-100 my-1"></div>
                                        @endif
                                        @if ($canPrintQr)
                                            <button onclick="printProductQrCode({{ $product->id }}, '{{ $product->unique_id }}')"
                                                class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-indigo-600 hover:bg-indigo-50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 3.5V16m-8 0v.5m0-3.5v.5m0 0h.01M12 16h.01M8 20h.01M4 20h.01M4 4h4v4H4V4zm12 0h4v4h-4V4zm0 12h4v4h-4v-4zM4 16h4v4H4v-4z" />
                                                </svg>
                                                Print QRCode
                                            </button>
                                        @endif
                                        @if ($canPrintSpecification)
                                            <a href="{{ route('warehouse.products.print-specification', $product) }}"
                                                target="_blank" rel="noopener"
                                                class="flex items-center gap-2 px-4 py-2 text-sm text-emerald-700 hover:bg-emerald-50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-12-4h12v8H6v-8z" />
                                                </svg>
                                                Print Specification
                                            </a>
                                        @endif
                                        @if ($canExtractPart)
                                        <a href="{{ route('warehouse.products.extract-part', $product->id) }}"
                                            class="flex items-center gap-2 px-4 py-2 text-sm text-orange-600 hover:bg-orange-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 16V4m0 0L3 8m4-4l4 4m6 4v8m0 0l4-4m-4 4l-4-4" />
                                            </svg>
                                            Extract Part
                                        </a>
                                        @endif
                                        @if ($canDeleteProduct)
                                            @if (strtolower($product->status ?? '') !== 'used' && !$product->sold_status)
                                                <button onclick="confirmDelete(this)"
                                                    data-url="{{ route('warehouse.products.destroy', $product->id) }}"
                                                    data-row="tr[data-id='{{ $product->id }}']"
                                                    data-status="{{ $product->status }}"
                                                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                                    Delete
                                                </button>
                                            @else
                                                <button disabled
                                                    class="w-full text-left px-4 py-2 text-sm text-gray-400 cursor-not-allowed bg-gray-50"
                                                    title="Cannot delete products with status '{{ $product->sold_status ? 'Sold' : ucfirst(strtolower($product->status ?? '')) }}'">
                                                    Delete
                                                    ({{ $product->sold_status ? 'Sold' : ucfirst(strtolower($product->status ?? '')) }})
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <span class="text-xs text-gray-400">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $showSoldQuantity ? 14 : 13 }}" class="px-4 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                </path>
                            </svg>
                            <p class="text-gray-600 font-medium text-lg">No products found</p>
                            <p class="text-gray-500 text-sm mt-1 mb-4">Generate barcodes or create products to get
                                started</p>
                            <a href="{{ route('warehouse.barcode') }}"
                                class="px-5 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors inline-flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Generate Barcodes
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
@if (isset($products) && $products->hasPages())
    <div id="pagination-wrapper" class="px-4 py-3 border-t border-gray-200">
        {{ $products->withQueryString()->links() }}
    </div>
@endif

<script>
    // Re-initialize checkboxes after table reload
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof initializeCheckboxes === 'function') {
            initializeCheckboxes();
            updateSelectedCount();
        }
    });
</script>
