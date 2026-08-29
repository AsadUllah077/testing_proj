{{-- resources/views/pages/products/warehouse/parts/detail.blade.php --}}
@extends('layouts.auth')

@section('title', 'Part Detail - ' . $part->name)

@section('content')
<x-acccount.loader />
<div class="mt-4">
    <div class="mx-auto px-3">
        <div class="overflow-hidden">
            <!-- Header -->
            <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">
                <div>
                    <h2 class="text-stone-950 text-xl font-normal">Part Detail</h2>
                    <div class="text-sm text-gray-600 mt-1">Part Number: {{ $part->part_number }}</div>
                </div>
                <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                    <a href="{{ route('warehouse.parts.index') }}"
                        class="px-4 py-2 border border-gray-300 text-gray-700 text-xs font-normal rounded hover:bg-gray-50">
                        BACK TO LIST
                    </a>
                    <a href="{{ route('warehouse.parts.edit', $part->id) }}"
                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0]">
                        EDIT PART
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-4">
                <div class="p-6">
                    <!-- Part Info Section -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <!-- Part Name Card -->
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ $part->name }}</h3>
                            <p class="text-sm text-gray-600 mb-3">{{ $part->description ?? 'No description' }}</p>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">Category: {{ $part->category ?? 'N/A' }}</span>
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
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ str_replace('_', ' ', $part->status) }}
                                </span>
                            </div>
                        </div>

                        <!-- Location Card -->
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Location Details</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Warehouse:</span>
                                    <span class="text-sm font-medium">{{ $part->warehouseLocation->name ?? 'N/A' }}</span>
                                </div>
                                @if($part->rack_position)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Rack Position:</span>
                                    <span class="text-sm font-medium">{{ $part->rack_position }}</span>
                                </div>
                                @endif
                                @if($part->shelf)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Shelf:</span>
                                    <span class="text-sm font-medium">{{ $part->shelf }}</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Quantity Card -->
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Stock Information</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Current Quantity:</span>
                                    <span class="text-sm font-medium {{ $part->quantity <= $part->min_stock_level ? 'text-red-600' : 'text-gray-900' }}">
                                        {{ $part->quantity }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Min Stock Level:</span>
                                    <span class="text-sm font-medium">{{ $part->min_stock_level ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Reorder Level:</span>
                                    <span class="text-sm font-medium">{{ $part->reorder_level ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-blue-600 font-semibold uppercase">Total Used</p>
                                    <p class="text-2xl font-bold text-blue-800">{{ $totalUsed ?? 0 }}</p>
                                </div>
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-green-600 font-semibold uppercase">Total Received</p>
                                    <p class="text-2xl font-bold text-green-800">{{ $totalReceived ?? 0 }}</p>
                                </div>
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-purple-600 font-semibold uppercase">Products Using</p>
                                    <p class="text-2xl font-bold text-purple-800">{{ $part->products->count() }}</p>
                                </div>
                                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs Section -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="flex space-x-8">
                            <button id="stock-tab" onclick="switchTab('stock')"
                                class="py-2 px-1 border-b-2 font-medium text-sm focus:outline-none border-[#0078C8] text-[#0078C8]">
                                Stock Movements
                            </button>
                            <button id="usage-tab" onclick="switchTab('usage')"
                                class="py-2 px-1 border-b-2 font-medium text-sm focus:outline-none border-transparent text-gray-500 hover:text-gray-700">
                                Part Usage
                            </button>
                            <button id="history-tab" onclick="switchTab('history')"
                                class="py-2 px-1 border-b-2 font-medium text-sm focus:outline-none border-transparent text-gray-500 hover:text-gray-700">
                                History
                            </button>
                        </nav>
                    </div>

                    <!-- Stock Movements Tab Content -->
                    <div id="stock-content" class="space-y-6">
                        <!-- Add to Product Section -->
                        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-medium text-blue-800 mb-1">{{ $part->location }}</h4>
                                    <p class="text-xs text-blue-600">Current available quantity</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-blue-800">{{ $part->quantity }}</div>
                                    @if($part->quantity > 0)
                                        <button onclick="showAddToProductModal({{ $part->id }}, '{{ $part->name }}', {{ $part->quantity }})"
                                            class="mt-2 px-4 py-1.5 bg-[#0078C8] text-white text-xs font-medium rounded hover:bg-[#006bb0]">
                                            Add to Product
                                        </button>
                                    @else
                                        <button disabled
                                            class="mt-2 px-4 py-1.5 bg-gray-300 text-gray-500 text-xs font-medium rounded cursor-not-allowed">
                                            Out of Stock
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-4 text-xs text-gray-500">
                                Last updated: {{ $part->updated_at ? $part->updated_at->format('d M Y H:i') : 'N/A' }}
                            </div>
                        </div>

                        <!-- Recent Extractions -->
                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                                <h4 class="text-sm font-medium text-gray-700">Recent Extractions</h4>
                            </div>
                            <div class="p-4">
                                @php
                                    $extractions = $part->movements()->where('movement_type', 'OUT')->with('product', 'createdBy')->latest()->limit(5)->get();
                                @endphp

                                @forelse($extractions as $movement)
                                <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $movement->product->unique_id ?? 'N/A' }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $movement->createdBy->username ?? 'System' }} •
                                                {{ $movement->created_at->format('d M Y') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium text-red-600">-{{ $movement->quantity }}</div>
                                        <div class="text-xs text-gray-500">{{ $movement->reason ?? 'Added to product' }}</div>
                                    </div>
                                </div>
                                @empty
                                <div class="text-center py-4 text-gray-500">
                                    No extraction records found
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Part Usage Tab Content -->
                    <div id="usage-content" class="space-y-6 hidden">
                        <!-- Products using this part -->
                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                                <h4 class="text-sm font-medium text-gray-700">Products Using This Part</h4>
                            </div>
                            <div class="p-4">
                                @forelse($part->products as $product)
                                <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <a href="{{ route('warehouse.products.show', $product->id) }}" class="text-sm font-medium text-gray-900 hover:text-[#0078C8]">
                                                {{ $product->title }}
                                            </a>
                                            <div class="text-xs text-gray-500">Product Code: {{ $product->unique_id }}</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium text-gray-900">{{ $product->pivot->quantity }} unit(s)</div>
                                        <div class="text-xs text-gray-500">Last used: {{ $product->pivot->created_at->format('M d, Y') }}</div>
                                    </div>
                                </div>
                                @empty
                                <div class="text-center py-4 text-gray-500">
                                    This part is not used in any products yet
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- History Tab Content -->
                    <div id="history-content" class="space-y-6 hidden">
                        <!-- History Timeline -->
                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                                <h4 class="text-sm font-medium text-gray-700">Part History</h4>
                            </div>
                            <div class="p-4">
                                <div class="space-y-4">
                                    @forelse($part->movements as $movement)
                                    <div class="flex items-start gap-3">
                                        <div class="mt-1">
                                            @php
                                                $historyIcon = match($movement->movement_type) {
                                                    'IN' => 'bg-green-100 text-green-600',
                                                    'OUT' => 'bg-red-100 text-red-600',
                                                    'TRANSFER' => 'bg-blue-100 text-blue-600',
                                                    default => 'bg-gray-100 text-gray-600'
                                                };
                                            @endphp
                                            <div class="w-6 h-6 {{ $historyIcon }} rounded-full flex items-center justify-center">
                                                @if($movement->movement_type == 'IN')
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                    </svg>
                                                @elseif($movement->movement_type == 'OUT')
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-sm text-gray-900">
                                                @if($movement->movement_type == 'IN')
                                                    Stock added: +{{ $movement->quantity }} units
                                                @elseif($movement->movement_type == 'OUT')
                                                    Stock removed: -{{ $movement->quantity }} units
                                                @elseif($movement->movement_type == 'TRANSFER')
                                                    Transferred {{ $movement->quantity }} units from {{ $movement->from_location }} to {{ $movement->to_location }}
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $movement->created_at->format('d M Y, H:i') }} by {{ $movement->createdBy->username ?? 'System' }}
                                                @if($movement->reason)
                                                    • {{ $movement->reason }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="text-center py-4 text-gray-500">
                                        No history records found
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Toast --}}
<div id="toast" class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
    <div id="toast-content" class="text-sm text-gray-800"></div>
</div>

{{-- Add to Product Modal --}}
<div id="addToProductModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black/50 transition-opacity">
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Add to Product</h3>
                        <p class="text-sm text-gray-500 mt-1" id="addToProductPartName"></p>
                    </div>
                    <form id="addToProductForm">
                        @csrf
                        <div class="space-y-4">
                            <input type="hidden" name="part_id" id="modalPartId">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Product *</label>
                                <select name="product_id" id="selectProduct"
                                    class="select2
                                    -full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]">
                                    <option value="">Select product</option>
                                    @foreach(App\Models\Product::with('category')->limit(20)->get() as $product)
                                        <option value="{{ $product->id }}">
                                            {{ $product->unique_id }} - {{ $product->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Quantity *</label>
                                <input type="number" name="quantity" id="productQuantity" value="1" min="1"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]">
                                <p class="text-xs text-gray-500 mt-1" id="availableQuantity"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Notes</label>
                                <textarea name="notes" rows="2"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]"
                                    placeholder="Optional notes..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="addToProduct()"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#0078C8] text-sm font-medium text-white hover:bg-[#006bb0] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0078C8] sm:ml-3 sm:w-auto">
                        ADD TO PRODUCT
                    </button>
                    <button type="button" onclick="hideAddToProductModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto">
                        CANCEL
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Toast function
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const content = document.getElementById('toast-content');
        toast.classList.remove('hidden');
        content.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="mt-0.5">
                    ${type === 'success'
                        ? '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>'
                        : '<svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9l-3-3m0 0l3 3m-3-3v6m6-6v6m0 0l-3-3m3 3l-3-3" clip-rule="evenodd"/></svg>'
                    }
                </div>
                <div class="flex-1 text-sm">${message}</div>
                <button class="text-gray-500 hover:text-gray-700" onclick="hideToast()">✕</button>
            </div>
        `;
        setTimeout(hideToast, 3000);
    }

    function hideToast() {
        document.getElementById('toast').classList.add('hidden');
    }

    // Tab switching
    function switchTab(tabName) {
        // Hide all content
        document.getElementById('stock-content').classList.add('hidden');
        document.getElementById('usage-content').classList.add('hidden');
        document.getElementById('history-content').classList.add('hidden');

        // Remove active state from all tabs
        document.getElementById('stock-tab').classList.remove('border-[#0078C8]', 'text-[#0078C8]');
        document.getElementById('stock-tab').classList.add('border-transparent', 'text-gray-500');
        document.getElementById('usage-tab').classList.remove('border-[#0078C8]', 'text-[#0078C8]');
        document.getElementById('usage-tab').classList.add('border-transparent', 'text-gray-500');
        document.getElementById('history-tab').classList.remove('border-[#0078C8]', 'text-[#0078C8]');
        document.getElementById('history-tab').classList.add('border-transparent', 'text-gray-500');

        // Show selected content and update tab
        document.getElementById(tabName + '-content').classList.remove('hidden');
        document.getElementById(tabName + '-tab').classList.add('border-[#0078C8]', 'text-[#0078C8]');
        document.getElementById(tabName + '-tab').classList.remove('border-transparent', 'text-gray-500');
    }

    // Add to Product Modal
    function showAddToProductModal(partId, partName, availableQty) {
        document.getElementById('modalPartId').value = partId;
        document.getElementById('addToProductPartName').textContent = `Add ${partName} to a product (Available: ${availableQty})`;
        document.getElementById('availableQuantity').textContent = `Available: ${availableQty}`;
        document.getElementById('productQuantity').max = availableQty;
        document.getElementById('productQuantity').value = 1;
        document.getElementById('addToProductModal').classList.remove('hidden');
    }

    function hideAddToProductModal() {
        document.getElementById('addToProductModal').classList.add('hidden');
        document.getElementById('addToProductForm').reset();
    }

    function addToProduct() {
        const form = document.getElementById('addToProductForm');
        const formData = new FormData(form);

        fetch('{{ route('warehouse.parts.add-to-product') }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                hideAddToProductModal();
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showToast(data.message || 'Failed to add part to product', 'error');
            }
        })
        .catch(error => {
            showToast('Network error. Please try again.', 'error');
        });
    }

    // Close modals when clicking outside
    document.addEventListener('click', (e) => {
        const addToProductModal = document.getElementById('addToProductModal');
        if (addToProductModal && !addToProductModal.contains(e.target) && !e.target.closest('button[onclick*="showAddToProductModal"]')) {
            hideAddToProductModal();
        }
    });

    @if(session('success'))
        showToast('{{ session('success') }}', 'success');
    @endif

    @if(session('error'))
        showToast('{{ session('error') }}', 'error');
    @endif
</script>
@endpush

