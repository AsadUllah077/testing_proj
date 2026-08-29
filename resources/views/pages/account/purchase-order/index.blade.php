@extends('layouts.auth')

@section('title', 'Purchase Order')

@section('content')
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.purchase-order') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden ">

                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Purchase Order</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-2 relative ">
                            <x-columns :columns="[
                                'po_order_date' => 'Order Date',
                                'po_order_no' => 'Order No',
                                'po_departure_date' => 'Departure Date',
                                'po_reference_no' => 'Reference No',
                                'po_vendor' => 'Vendor',
                                'po_description' => 'Description',
                                'po_total_amount' => 'Total Amount',
                                'po_remaining_amount' => 'Remaining Amount',
                            ]" name="account_purchase_order_columns" />

                            <!-- Export Dropdown -->
                            <div class="relative">
                                <button onclick="toggleDropdown('exportDropdown')"
                                    class="px-5 py-1.5 border border-[#0078C8] text-[#0078C8] rounded-lg hover:bg-[#0078C8] hover:text-white text-sm font-medium transition-all duration-200 flex items-center gap-2">
                                    Export
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div id="exportDropdown"
                                    class="hidden absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                                    <ul class="py-1 text-sm">
                                        <li>
                                            <button onclick="startExport('pdf','purchase-order')" data-url=""
                                                class="w-full text-left block px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                PDF
                                            </button>
                                        </li>
                                        <li>
                                            <button onclick="startExport('xlsx','purchase-order')"
                                                class="w-full text-left block px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Excel
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Add New Button -->
                            <a href="{{ route('account.purchase-order.create') }}"
                                class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                ADD NEW
                            </a>
                        </div>
                    </div>

                    <!-- Per Page Selector -->
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 my-4">
                        <div class="col-span-full flex justify-end">
                            <form method="GET" action="{{ route('account.purchase-order.index') }}"
                                class="flex items-center gap-2">
                                <label class="text-sm text-gray-600">Show:</label>
                                <select name="per_page" onchange="this.form.submit()"
                                    class="w-28 bg-white py-2 px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                                    <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </form>
                        </div>
                    </div>

                    <div class="bg-white rounded-md border border-[#C0BFBF]">
                        <!-- Filters -->
                        <form method="GET" action="{{ route('account.purchase-order.index') }}">
                            <div class="p-4 border-b border-gray-200">
                                <div class="flex flex-wrap items-end gap-3">
                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">From</label>
                                        <input class="date" type="date" name="from_date" value="{{ $request->from_date }}"
                                            class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">To</label>
                                        <input class="date" type="date" name="to_date" value="{{ $request->to_date }}"
                                            class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">Select Vendor</label>
                                        <select name="vendor_id"
                                            class="ajax-vendor-select w-full py-[9px] px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                                        </select>
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">Select Ref No</label>
                                        <select name="ref_no"
                                            class="select2 w-full py-[9px] px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                                            <option value="">-- select ref no --</option>
                                            @foreach ($refNos as $refNo)
                                                <option value="{{ $refNo }}"
                                                    {{ $request->ref_no == $refNo ? 'selected' : '' }}>
                                                    {{ $refNo }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">Amount</label>
                                        <input type="number" name="amount" value="{{ $request->amount }}"
                                            placeholder="amount..."
                                            class="w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">Search</label>
                                        <input type="search" name="search" value="{{ $request->search }}"
                                            placeholder="Search..."
                                            class="w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                                    </div>

                                    <div class="flex gap-2 items-end mt-1">
                                        <button type="submit"
                                            class="px-4 py-1.5 border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">
                                            SEARCH
                                        </button>
                                        <a href="{{ route('account.purchase-order.index') }}"
                                            class="px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">
                                            RESET
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Table -->
                        <div class="overflow-x-auto w-full p-4">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        {{-- <th class="px-4 py-3 text-left"><input type="checkbox" id="select-all"></th> --}}
                                        {{-- @if ($visibleColumns['order_date']) --}}
                                        <th class="px-4 py-3 text-left" data-col="po_order_date">Order Date</th>
                                        {{-- @endif --}}
                                        {{-- @if ($visibleColumns['order_no']) --}}
                                        <th class="px-4 py-3 text-left" data-col="po_order_no">Order No</th>
                                        {{-- @endif --}}
                                        {{-- @if ($visibleColumns['departure_date']) --}}
                                        <th class="px-4 py-3 text-left" data-col="po_departure_date">Departure Date</th>
                                        {{-- @endif --}}
                                        {{-- @if ($visibleColumns['reference_no']) --}}
                                        <th class="px-4 py-3 text-left" data-col="po_reference_no">Reference No</th>
                                        {{-- @endif --}}
                                        {{-- @if ($visibleColumns['vendor']) --}}
                                        <th class="px-4 py-3 text-left" data-col="po_vendor">Vendor</th>
                                        {{-- @endif --}}
                                        {{-- @if ($visibleColumns['description']) --}}
                                        <th class="px-4 py-3 text-left" data-col="po_description">Description</th>
                                        {{-- @endif --}}
                                        {{-- @if ($visibleColumns['total_amount']) --}}
                                        <th class="px-4 py-3 text-left" data-col="po_total_amount">Total Amount</th>
                                        {{-- @endif --}}
                                        {{-- @if ($visibleColumns['remaining_amount']) --}}
                                        <th class="px-4 py-3 text-left" data-col="po_remaining_amount">Remaining Amount
                                        </th>
                                        {{-- @endif --}}
                                        <th class="px-4 py-3 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($purchaseOrders as $purchaseOrder)
                                        <tr class="hover:bg-gray-50 border-b">
                                            {{-- <td class="px-4 py-2">
                                                <input type="checkbox" class="row-checkbox" value="{{ $purchaseOrder->id }}">
                                            </td> --}}
                                            {{-- @if ($visibleColumns['order_date']) --}}
                                            <td class="px-4 py-2" data-col="po_order_date">
                                                {{ $purchaseOrder->order_date->format('d/m/Y') }}</td>
                                            {{-- @endif --}}
                                            {{-- @if ($visibleColumns['order_no']) --}}
                                            <td class="px-4 py-2" data-col="po_order_no">{{ $purchaseOrder->order_no }}
                                            </td>
                                            {{-- @endif --}}
                                            {{-- @if ($visibleColumns['departure_date']) --}}
                                            <td class="px-4 py-2" data-col="po_departure_date">
                                                {{ $purchaseOrder->departure_date->format('d/m/Y') }}</td>
                                            {{-- @endif --}}
                                            {{-- @if ($visibleColumns['reference_no']) --}}
                                            <td class="px-4 py-2" data-col="po_reference_no">
                                                {{ $purchaseOrder->ref_no ?? 'N/A' }}</td>
                                            {{-- @endif --}}
                                            {{-- @if ($visibleColumns['vendor']) --}}
                                            <td class="px-4 py-2" data-col="po_vendor">
                                                {{ $purchaseOrder->vendor->name ?? 'N/A' }}</td>
                                            {{-- @endif --}}
                                            {{-- @if ($visibleColumns['description']) --}}
                                            <td class="px-4 py-2" data-col="po_description">
                                                {{ Str::limit($purchaseOrder->note, 50) }}</td>
                                            {{-- @endif --}}
                                            {{-- @if ($visibleColumns['total_amount']) --}}
                                            <td class="px-4 py-2 text-left" data-col="po_total_amount">
                                                {{ number_format($purchaseOrder->total, 2) }}
                                                ({{ $purchaseOrder->currency->code ?? 'PKR' }})
                                            </td>
                                            {{-- @endif --}}
                                            {{-- @if ($visibleColumns['remaining_amount']) --}}
                                            <td class="px-4 py-2 text-center" data-col="po_remaining_amount">
                                                {{-- <span class="bg-[#B1AFAF] px-3 py-1 text-white rounded-full text-xs">
                                                        {{ number_format($purchaseOrder->remaining_amount, 2) }}
                                                    </span> --}}
                                                @if ($purchaseOrder->remaining_amount > 0)
                                                    <button
                                                        onclick="openPaymentModal({{ $purchaseOrder->id }}, '{{ $purchaseOrder->invoice_no }}', '{{ $purchaseOrder->vendor->name ?? 'N/A' }}', {{ $purchaseOrder->remaining_amount }})"
                                                        class="bg-[#B1AFAF] hover:bg-[#0078C8] px-3 py-1 text-white rounded-full text-xs transition-colors cursor-pointer"
                                                        title="Click to record payment">
                                                        {{ number_format($purchaseOrder->remaining_amount, 2) }}
                                                    </button>
                                                @else
                                                    <span class="bg-[#0078C8] px-3 py-1 text-white rounded-full text-xs ">
                                                        Paid
                                                    </span>
                                                @endif
                                            </td>
                                            {{-- @endif --}}
                                            <td class="px-4 py-2 text-center relative">
                                                <button class="text-gray-500 hover:text-gray-800 dropdown-btn">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                                    </svg>
                                                </button>
                                                <div
                                                    class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-10">
                                                    <a href="{{ route('account.purchase-order.show', $purchaseOrder->id) }}"
                                                        class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2">
                                                        <span>▶</span>
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                            <path fill-rule="evenodd"
                                                                d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                        Details
                                                    </a>
                                                    <a href="{{ route('account.purchase-order.edit', $purchaseOrder->id) }}"
                                                        class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2">
                                                        <span>▶</span>
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path
                                                                d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                        </svg>
                                                        Edit
                                                    </a>
                                                    <button
                                                        class="delete-btn w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2"
                                                        data-id="{{ $purchaseOrder->id }}">
                                                        <span>▶</span>
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                        Delete
                                                    </button>
                                                    <a href="{{ route('account.purchase-order.audit-logs', $purchaseOrder->id) }}"
                                                        class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2">
                                                        <span>▶</span>
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                        History
                                                    </a>
                                                    <a href="{{ route('account.purchase-order.export-pdf', $purchaseOrder->id) }}"
                                                        class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-2">
                                                        <span>▶</span>
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                        Export Pdf
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                                No purchase orders found.
                                            </td>
                                        </tr>
                                    @endforelse

                                    @if ($purchaseOrders->count() > 0)
                                        <tr class="bg-gray-50 font-semibold">
                                            <td colspan="{{ 6 }}"
                                                class="px-4 py-2 text-[#0078C8] text-right">TOTAL:</td>
                                            {{-- @if ($visibleColumns['total_amount']) --}}
                                            <td class="px-4 py-2 text-left text-[#0078C8]">
                                                {{ number_format($totalAmount, 2) }}</td>
                                            {{-- @endif --}}
                                            {{-- @if ($visibleColumns['remaining_amount']) --}}
                                            <td class="px-4 py-2 text-center text-[#0078C8]">
                                                {{ number_format($totalRemaining, 2) }}</td>
                                            {{-- @endif --}}
                                            <td></td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if ($purchaseOrders->hasPages())
                            <div class="p-4 border-t border-gray-200">
                                {{ $purchaseOrders->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-delete-confirmation />
    <x-acccount.remaining-amount-purchase-order />

@endsection

@push('scripts')
    <script>
        // Get modal element
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDelete = document.getElementById('confirmDelete');
        let currentDeleteId = null;

        // Dropdown button handlers
        document.querySelectorAll('.dropdown-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const menu = this.nextElementSibling;
                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(m => {
                    if (m !== menu) m.classList.add('hidden');
                });
                // Toggle current dropdown
                menu.classList.toggle('hidden');
            });
        });

        // Delete button click handler
        document.addEventListener('click', function(e) {
            if (e.target.closest('.delete-btn')) {
                e.preventDefault();
                e.stopPropagation();
                currentDeleteId = e.target.closest('.delete-btn').dataset.id;
                // Show modal
                deleteModal.classList.remove('hidden');
                // Close all dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        // Cancel delete
        if (cancelDelete) {
            cancelDelete.addEventListener('click', () => {
                deleteModal.classList.add('hidden');
                currentDeleteId = null;
            });
        }

        // Confirm delete
        if (confirmDelete) {
            confirmDelete.addEventListener('click', () => {
                if (currentDeleteId) {
                    // Submit delete form
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/purchase-orders/${currentDeleteId}/destroy`;

                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';

                    const methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    methodField.value = 'DELETE';

                    form.appendChild(csrfToken);
                    form.appendChild(methodField);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Close modal when clicking outside
        if (deleteModal) {
            deleteModal.addEventListener('click', (e) => {
                if (e.target === deleteModal) {
                    deleteModal.classList.add('hidden');
                    currentDeleteId = null;
                }
            });
        }

        // Close all dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        // Export dropdown toggle
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            // Close all other dropdowns
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            // Toggle current dropdown
            dropdown.classList.toggle('hidden');
        }

        // Close export dropdown when clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.closest('button') && !e.target.closest('[id$="Dropdown"]')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(el => el.classList.add('hidden'));
            }
        });

        // Select all checkbox
        document.getElementById('select-all')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Initialize AJAX Vendor Select
        $('.ajax-vendor-select').select2({
            placeholder: '-- select vendor --',
            allowClear: true,
            ajax: {
                url: '{{ route('account.bill.vendors.paginate') }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.data.map(vendor => ({
                            id: vendor.id,
                            text: vendor.name
                        })),
                        pagination: {
                            more: !!data.next_page
                        }
                    };
                },
                cache: true
            }
        });
    </script>
@endpush
