{{-- resources/views/pages/products/warehouse/gate-pass/create.blade.php --}}
@extends('layouts.auth')

@section('title', 'Create Gate Pass')

@section('content')
    <div class="min-h-screen bg-gray-100 py-6">
        <div class="max-w-12xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header Bar -->
            <div
                class="mb-4 bg-white rounded-md border border-gray-200 px-5 py-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <h2 class="text-gray-800 text-base font-semibold tracking-wide">Gate Pass</h2>
                <a href="{{ route('warehouse.gate-pass.index') }}"
                    class="px-5 py-1.5 bg-[#0078C8] text-white text-sm font-semibold rounded hover:bg-[#0066a0] transition-colors tracking-wide w-full sm:w-auto text-center">
                    BACK
                </a>
            </div>

            @if (session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if (($unavailableSelectedProductCount ?? 0) > 0)
                <div class="mb-4 bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-md text-sm">
                    {{ $unavailableSelectedProductCount }}
                    selected {{ Str::plural('product', $unavailableSelectedProductCount) }}
                    could not be added because they are unavailable or already included in an active gate pass.
                </div>
            @endif

            <!-- Main Form -->
            <form id="formSubmit" action_url="{{ route('warehouse.gate-pass.store') }}"
                back_url="{{ route('warehouse.gate-pass.index') }}" method="POST">
                @csrf

                <!-- Two-Column Layout -->
                <div class="flex gap-4 items-start">

                    <!-- LEFT COLUMN -->
                    <div class="flex-1 flex flex-col gap-4">

                        <!-- Gate Pass Details Card -->
                        <div class="bg-white rounded-md border border-gray-200  p-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">

                                <!-- Gate Pass ID -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Gate Pass ID
                                    </label>
                                    <input type="text" name="gate_pass_id" value="{{ old('gate_pass_id') }}"
                                        placeholder="Enter gate pass id....." readonly disabled
                                        class="w-full px-3 py-2 border @error('gate_pass_id') border-red-400 @else border-gray-300 @enderror rounded text-sm text-gray-700 focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none placeholder:text-gray-400">
                                    @error('gate_pass_id')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                    <p class="text-gray-400 text-xs mt-1">Leave empty to auto-generate</p>
                                </div>

                                <!-- Invoice Number -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Invoice Number
                                    </label>
                                    <input type="text" name="invoice_no" value="{{ old('invoice_no') }}"
                                        placeholder="Enter invoice number..."
                                        class="w-full px-3 py-2 border @error('invoice_no') border-red-400 @else border-gray-300 @enderror rounded text-sm text-gray-700 focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none placeholder:text-gray-400">
                                    @error('invoice_no')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Customer Info -->
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Customer Info
                                    </label>
                                    <textarea name="customer_info" rows="3"
                                        placeholder="Enter customer name, phone, address or any customer details..."
                                        class="w-full px-3 py-2 border @error('customer_info') border-red-400 @else border-gray-300 @enderror rounded text-sm text-gray-700 focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none resize-none placeholder:text-gray-400">{{ old('customer_info') }}</textarea>
                                    @error('customer_info')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                               <!-- Source Location (plain text input) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                    <select name="warehouse_location_id"
                                        class="select2 w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent bg-white">
                                        <option value="">Select Location</option>
                                        @foreach ($locations as $location)
                                            <option value="{{ $location->id }}">
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Date & Time -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Date & Time <span class="text-red-500">*</span>
                                    </label>
                                    <input type="datetime-local" name="departure_date" value="{{ old('departure_date') }}"
                                        required
                                        class="date w-full px-3 py-2 border @error('departure_date') border-red-400 @else border-gray-300 @enderror rounded text-sm text-gray-700 focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                                    @error('departure_date')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Warranty -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Warranty (Days)
                                    </label>
                                    <input type="number" name="warranty_days" value="{{ old('warranty_days') }}"
                                        placeholder="Enter total warranty days..." min="0" step="1"
                                        class="w-full px-3 py-2 border @error('warranty_days') border-red-400 @else border-gray-300 @enderror rounded text-sm text-gray-700 focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none placeholder:text-gray-400">
                                    @error('warranty_days')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                            </div>
                        </div>

                        <!-- Selected Products Card -->
                        <div class="bg-white rounded-md border border-gray-200  p-5">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-800">Selected Products</h3>
                                <button type="button" id="showSearchBtn"
                                    class="inline-flex items-center px-3 py-1.5 bg-[#0078C8] text-white text-xs font-medium rounded hover:bg-[#0066a0] transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Products
                                </button>
                            </div>

                            @error('selected_products')
                                <p class="text-red-500 text-xs mb-3">{{ $message }}</p>
                            @enderror

                            @include('pages.products.warehouse.gatepass.partials.barcode-scanner')

                            <!-- Search Panel -->
                            <div id="searchPanel" class="hidden mb-4 bg-gray-50 rounded border border-gray-200 p-3">
                                <div class="flex justify-between items-center mb-3">
                                    <h4 class="text-xs font-medium text-gray-700">Search Products</h4>
                                    <button type="button" id="hideSearchBtn" class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex gap-2">
                                    <input type="text" id="productSearch" placeholder="Search products by ID, name or category..."
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                                    <button type="button" id="searchProductsBtn"
                                        class="px-4 py-2 bg-[#0078C8] text-white text-xs font-medium rounded hover:bg-[#0066a0] transition-colors whitespace-nowrap">
                                        Search
                                    </button>
                                </div>

                                <!-- Search Results -->
                                <div id="searchResults" class="hidden mt-3">
                                    <div class="border border-gray-200 rounded overflow-hidden max-h-48 overflow-y-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-100 sticky top-0">
                                                <tr>
                                                    <th
                                                        class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">
                                                        Unique ID</th>
                                                    <th
                                                        class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">
                                                        Product Name</th>
                                                    <th
                                                        class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">
                                                        Category Name</th>
                                                    <th
                                                        class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">
                                                        Qty</th>
                                                    <th
                                                        class="px-3 py-2 text-left text-xs font-medium text-gray-600 uppercase">
                                                        Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="searchResultsBody" class="bg-white divide-y divide-gray-200"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Products Table -->
                            <div class="border border-gray-200 rounded overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium text-[#0078C8]">Unique ID
                                            </th>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium text-[#0078C8]">Product
                                                Name</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium text-[#0078C8]">Category
                                                Name</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium text-[#0078C8]">Qty</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-medium text-[#0078C8] w-16">Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="selectedProductsBody" class="bg-white divide-y divide-gray-100">
                                        <tr id="emptyRow">
                                            <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">
                                                No products selected
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Hidden input: stores JSON array of selected products --}}
                            <input type="hidden" name="selected_products" id="selectedProductsInput" value="[]">
                        </div>

                    </div>
                    <!-- END LEFT COLUMN -->

                    <!-- RIGHT COLUMN -->
                    <div class="w-72 flex flex-col gap-4">

                        <!-- QC Approval Card -->
                        <div class="bg-white rounded-md border border-gray-200  p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">QC Approval</h3>
                                <button type="button" id="qcToggleBtn" onclick="toggleApproval('qc')"
                                    class="relative inline-flex h-5 w-10 items-center rounded-full transition-colors duration-200 focus:outline-none bg-gray-300"
                                    role="switch" aria-checked="false">
                                    <span id="qcToggleKnob"
                                        class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200 translate-x-1">
                                    </span>
                                </button>
                                <input type="hidden" name="qc_approval_enabled" id="qcApprovalEnabled" value="0">
                            </div>
                            <div id="qcApprovalContent" class="hidden">
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">QC Remarks <span class="text-red-500">*</span></label>
                                <textarea name="qc_remarks" rows="4" placeholder="QC Remarks"
                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none resize-none placeholder:text-gray-400">{{ old('qc_remarks') }}</textarea>
                            </div>
                        </div>

                        <!-- Security Approval Card -->
                        <div class="bg-white rounded-md border border-gray-200  p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-800">Security Approval</h3>
                                <button type="button" id="securityToggleBtn" onclick="toggleApproval('security')"
                                    class="relative inline-flex h-5 w-10 items-center rounded-full transition-colors duration-200 focus:outline-none bg-gray-300"
                                    role="switch" aria-checked="false">
                                    <span id="securityToggleKnob"
                                        class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200 translate-x-1">
                                    </span>
                                </button>
                                <input type="hidden" name="security_approval_enabled" id="securityApprovalEnabled"
                                    value="0">
                            </div>
                            <div id="securityApprovalContent" class="hidden">
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Security In charge Name <span class="text-red-500">*</span></label>
                                <input type="text" name="security_incharge_name"
                                    value="{{ old('security_incharge_name') }}" placeholder="Security In charge Name"
                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none placeholder:text-gray-400">
                            </div>
                        </div>

                    </div>
                    <!-- END RIGHT COLUMN -->

                </div>

                <!-- Form Actions -->
                <div class="mt-4 flex flex-col-reverse sm:flex-row justify-end gap-3">
                    <a href="{{ route('warehouse.gate-pass.index') }}"
                        class="px-5 py-2 border border-gray-300 rounded text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" id="submitBtn"
                        class="px-5 py-2 bg-[#0078C8] text-white text-sm font-medium rounded hover:bg-[#0066a0] transition-colors">
                        Create Gate Pass
                    </button>
                </div>

            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            let selectedProducts = @json(($preselectedProducts ?? collect())->values());
            let lastSearchResults = [];

            // -------------------------------------------------------
            // Toggle QC / Security approval sections
            // -------------------------------------------------------
            function toggleApproval(type) {
                const btn     = document.getElementById(type + 'ToggleBtn');
                const knob    = document.getElementById(type + 'ToggleKnob');
                const content = document.getElementById(type + 'ApprovalContent');
                const input   = document.getElementById(type + 'ApprovalEnabled');
                const field   = content.querySelector('textarea, input[type="text"]');
                const isOn    = btn.getAttribute('aria-checked') === 'true';

                if (isOn) {
                    btn.setAttribute('aria-checked', 'false');
                    btn.classList.replace('bg-[#0078C8]', 'bg-gray-300');
                    knob.classList.replace('translate-x-5', 'translate-x-1');
                    content.classList.add('hidden');
                    input.value = '0';
                    if (field) field.removeAttribute('required');
                } else {
                    btn.setAttribute('aria-checked', 'true');
                    btn.classList.replace('bg-gray-300', 'bg-[#0078C8]');
                    knob.classList.replace('translate-x-1', 'translate-x-5');
                    content.classList.remove('hidden');
                    input.value = '1';
                    if (field) field.setAttribute('required', '');
                }
            }

            // -------------------------------------------------------
            // Search panel toggle
            // -------------------------------------------------------
            document.getElementById('showSearchBtn').addEventListener('click', function() {
                document.getElementById('searchPanel').classList.remove('hidden');
                document.getElementById('productSearch').focus();
            });

            document.getElementById('hideSearchBtn').addEventListener('click', function() {
                closeSearchPanel();
            });

            function closeSearchPanel() {
                document.getElementById('searchPanel').classList.add('hidden');
                document.getElementById('searchResults').classList.add('hidden');
                document.getElementById('productSearch').value = '';
            }

            // -------------------------------------------------------
            // Product search
            // -------------------------------------------------------
            document.getElementById('searchProductsBtn').addEventListener('click', doSearch);

            document.getElementById('productSearch').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    doSearch();
                }
            });

            function doSearch() {
                const searchTerm = document.getElementById('productSearch').value.trim();

                if (searchTerm.length < 2) {
                    alert('Please enter at least 2 characters');
                    return;
                }

                const btn = document.getElementById('searchProductsBtn');
                btn.textContent = 'Searching...';
                btn.disabled = true;

                fetch(`{{ route('warehouse.gate-pass.search.products') }}?search=${encodeURIComponent(searchTerm)}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ??
                                '{{ csrf_token() }}'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displaySearchResults(data.products);
                        } else {
                            alert(data.message || 'No products found');
                        }
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        alert('Failed to search products. Please try again.');
                    })
                    .finally(() => {
                        btn.textContent = 'Search';
                        btn.disabled = false;
                    });
            }

            function displaySearchResults(products) {
                lastSearchResults = products;
                const tbody = document.getElementById('searchResultsBody');
                const resultsDiv = document.getElementById('searchResults');

                if (products.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" class="px-3 py-4 text-center text-gray-500 text-sm">No products found</td></tr>`;
                } else {
                    tbody.innerHTML = products.map(product => {
                        const isSelected = selectedProducts.some(p => p.id === product.id);
                        const qtyCell = product.manual_status == 1
                            ? `<td class="px-3 py-2 text-sm">
                                ${isSelected
                                    ? `<span class="text-gray-400 text-xs">—</span>`
                                    : `<input type="number" id="qty_${product.id}" min="1" max="${product.count}" value="1"
                                        class="w-16 px-2 py-1 border border-gray-300 rounded text-xs text-center focus:ring-1 focus:ring-[#0078C8] outline-none">`
                                }
                               </td>`
                            : `<td class="px-3 py-2 text-sm text-gray-900">1</td>`;
                        const actionCell = isSelected
                            ? `<span class="text-gray-400 text-xs font-medium">Added</span>`
                            : (product.manual_status == 1
                                ? `<button type="button" onclick='addManualProduct(${JSON.stringify(product)})' class="text-[#0078C8] hover:text-[#005a9e] text-xs font-medium">Add</button>`
                                : `<button type="button" onclick='addProduct(${JSON.stringify(product)})' class="text-[#0078C8] hover:text-[#005a9e] text-xs font-medium">Add</button>`
                            );
                        return `<tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-sm text-gray-900">${escapeHtml(product.unique_id)}</td>
                            <td class="px-3 py-2 text-sm text-gray-900">${escapeHtml(product.product_name)}</td>
                            <td class="px-3 py-2 text-sm text-gray-900">${escapeHtml(product.category_name)}</td>
                            ${qtyCell}
                            <td class="px-3 py-2 text-sm">${actionCell}</td>
                        </tr>`;
                    }).join('');
                }

                resultsDiv.classList.remove('hidden');
            }

            function addManualProduct(product) {
                const qtyInput = document.getElementById(`qty_${product.id}`);
                const qty = qtyInput ? parseInt(qtyInput.value) : 1;
                if (isNaN(qty) || qty < 1) {
                    alert('Please enter a valid quantity (minimum 1).');
                    return;
                }
                if (qty > product.count) {
                    alert(`Quantity cannot exceed available stock (${product.count}).`);
                    return;
                }
                addProduct({ ...product, quantity: qty });
            }

            // -------------------------------------------------------
            // Add / Remove products
            // -------------------------------------------------------
            function addProduct(product) {
                if (selectedProducts.some(p => p.id === product.id)) return;

                selectedProducts.push(product);
                updateSelectedProductsTable();
                // Re-render search results to show updated "Added" state without closing the panel
                if (lastSearchResults.length > 0) {
                    displaySearchResults(lastSearchResults);
                }
            }

            function removeProduct(productId) {
                selectedProducts = selectedProducts.filter(p => p.id !== productId);
                updateSelectedProductsTable();
            }

            @include('pages.products.warehouse.gatepass.partials.barcode-scanner-script')

            function updateSelectedProductsTable() {
                const tbody = document.getElementById('selectedProductsBody');
                const input = document.getElementById('selectedProductsInput');

                if (selectedProducts.length === 0) {
                    tbody.innerHTML = `<tr id="emptyRow"><td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">No products selected</td></tr>`;
                } else {
                    tbody.innerHTML = selectedProducts.map(product => `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-900">${escapeHtml(product.unique_id)}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">${escapeHtml(product.product_name)}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">${escapeHtml(product.category_name)}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">${product.manual_status == 1 ? (product.quantity || 1) : 1}</td>
                    <td class="px-4 py-3 text-sm">
                        <button type="button"
                                onclick="removeProduct(${product.id})"
                                class="text-red-500 hover:text-red-700 transition-colors"
                                title="Remove product">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </td>
                </tr>`).join('');
                }

                input.value = JSON.stringify(selectedProducts);
            }

            // -------------------------------------------------------
            // Pre-submit: ensure hidden input is up to date
            // (validation & loader handled by the global formSubmit JS)
            // -------------------------------------------------------
            document.getElementById('formSubmit').addEventListener('submit', function(e) {
                if (selectedProducts.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one product before submitting.');
                    return;
                }
                // Ensure hidden input is up to date before the loader script submits
                document.getElementById('selectedProductsInput').value = JSON.stringify(selectedProducts);
            });

            // -------------------------------------------------------
            // Utility
            // -------------------------------------------------------
            function escapeHtml(text) {
                if (text === null || text === undefined) return '';
                const div = document.createElement('div');
                div.textContent = String(text);
                return div.innerHTML;
            }

            updateSelectedProductsTable();
        </script>
    @endpush
@endsection
