@extends('layouts.auth')

@section('title', 'List Of Purchase Order')

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
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">
                            List Of Purchase Order
                            <span class="text-sm font-normal text-gray-600">
                                ({{ $status == 0 ? 'Pending' : 'Approved & Delivered' }})
                            </span>
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-2 items-center">
                            <!-- Approve & Delivered Button -->
                            <a href="{{ route('account.purchase-order.order.list', ['status' => 2] + request()->except('status')) }}"
                                class="px-4 py-2 border border-[#0078C8] w-full text-center rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal transition-all
                                    {{ $status == 1 ? 'bg-[#0078C8] text-white' : 'text-[#0078C8]' }}">
                                Approve & Delivered
                            </a>

                            <!-- Pending Button -->
                            <a href="{{ route('account.purchase-order.order.list', ['status' => 0] + request()->except('status')) }}"
                                class="px-3 py-2 w-full text-center text-xs font-normal rounded hover:bg-[#006bb0] transition-all
                                    {{ $status == 0 ? 'bg-[#0078C8] text-white' : 'bg-gray-300 text-gray-700 hover:bg-gray-400' }}">
                                Pending
                            </a>

                            <!-- Select All Checkbox -->
                            <div class="flex justify-center items-center">
                                <input type="checkbox" id="selectAll" class="rounded mr-2">
                                <label for="selectAll" class="text-sm text-gray-700">Select All To Delete</label>
                            </div>
                        </div>
                    </div>

                    <!-- Per Page Selector and Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-2 my-4">
                        <div class="flex items-center">
                            <span class="text-sm text-gray-600 mr-3">
                                Total Orders: <strong>{{ $totalOrders }}</strong> |
                                Total Amount: <strong>{{ number_format($totalAmount, 2) }}</strong>
                            </span>
                        </div>
                        <div class="flex justify-end items-center space-x-2">
                            <form method="GET" action="{{ route('account.purchase-order.order.list') }}"
                                class="flex items-center gap-2">
                                <label class="text-sm text-gray-600">Show:</label>
                                <select name="per_page" onchange="this.form.submit()"
                                    class="w-28 bg-white py-2 px-3 border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                                    <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </form>
                        </div>
                    </div>

                    <!-- Search and Reset -->
                    <form method="GET" action="{{ route('account.purchase-order.order.list') }}"
                        class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <input type="hidden" name="per_page" value="{{ $perPage }}" id="perPageHidden">

                        <div class="flex-1"></div>
                        <div class="flex space-x-2">
                            <input type="text" name="search" placeholder="Search by PO No, Vendor, Requested By..."
                                value="{{ request('search') }}"
                                class="px-3 py-2 border border-gray-300 rounded text-sm  focus:ring-1 focus:ring-[#0078C8] focus:outline-none w-64">
                            <button type="submit"
                                class="px-4 py-1.5 border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">SEARCH</button>
                            <a href="{{ route('account.purchase-order.order.list', ['status' => $status]) }}"
                                class="px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">RESET</a>
                        </div>
                    </form>

                    <!-- Purchase Orders Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mt-5">
                        @forelse ($purchaseOrders as $purchaseorder)
                            <div
                                class="border border-gray-300 rounded-md overflow-hidden bg-white shadow-sm hover:shadow-md transition-shadow">
                                <!-- Header -->
                                <div class="bg-[#0078C8] flex justify-between items-center px-3 py-1.5">
                                    <span class="text-white text-xs font-semibold">PO No :
                                        {{ $purchaseorder->unique_no }}</span>
                                    <span
                                        class="text-red-500 text-xs font-bold cursor-pointer leading-none delete-btn">x</span>
                                </div>

                                <!-- Body -->
                                <div class="p-3 text-sm text-gray-800">
                                    <p class="mb-1"><span class="font-semibold">Vendor:</span>
                                        {{ $purchaseorder->vendor->name ?? 'N/A' }}</p>
                                    <p class="mb-1"><span class="font-semibold">Requested by:</span>
                                        {{ $purchaseorder->requested_by ?? 'N/A' }}</p>
                                    <p class="mb-1"><span class="font-semibold">Order Date:</span>
                                        {{ $purchaseorder->order_date->format('M d, Y') }}</p>
                                    <p class="mb-1"><span class="font-semibold">Status:</span>
                                        <span
                                            class="px-2 py-1 rounded text-xs font-medium
                                            {{ $purchaseorder->status == 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                            {{ $purchaseorder->status == 0 ? 'Pending' : 'Approved' }}
                                        </span>
                                    </p>
                                    <p class="mt-2 font-semibold text-[#0078C8]">
                                        Total Amount: {{ number_format($purchaseorder->total, 2) }}
                                    </p>
                                </div>

                                <!-- Footer -->
                                <div class="flex justify-between items-center border-t border-gray-200 px-3 py-2">
                                    @if ($purchaseorder->status == 0)
                                        <a href="{{ route('account.purchase-order-quotation.edit', $purchaseorder->id) }}"
                                            class="px-3 py-1 bg-[#0078C8] text-white text-xs font-medium rounded hover:bg-[#006bb0] transition">
                                            VIEW
                                        </a>
                                    @endif

                                    @if ($purchaseorder->status == 2)
                                        <a href="{{ route('account.purchase-order-quotation.show', $purchaseorder->unique_no) }}"
                                            class="px-3 py-1 bg-[#0078C8] text-white text-xs font-medium rounded hover:bg-[#006bb0] transition">
                                            VIEW
                                        </a>
                                    @endif

                                    <input type="checkbox" class="w-4 h-4 cursor-pointer select-checkbox"
                                        name="selected_orders[]" value="{{ $purchaseorder->id }}">
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full flex items-center justify-center py-12">
                                <div class="text-center max-w-md">
                                    <!-- Icon -->
                                    <div
                                        class="mx-auto w-20 h-20 mb-6 rounded-full bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center">
                                        <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>

                                    <!-- Heading -->
                                    <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                        No {{ $status == 0 ? 'Pending' : 'Approved' }} Purchase Orders
                                    </h3>

                                    <!-- Description -->
                                    <p class="text-gray-600 mb-6">
                                        @if (request('search'))
                                            We couldn't find any {{ $status == 0 ? 'pending' : 'approved' }} purchase
                                            orders matching "<span
                                                class="font-medium text-gray-900">{{ request('search') }}</span>".
                                        @else
                                            You don't have any {{ $status == 0 ? 'pending' : 'approved' }} purchase orders
                                            at the moment.
                                        @endif
                                    </p>

                                    <!-- Action Buttons -->
                                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                                        @if (request('search'))
                                            <a href="{{ request()->url() }}"
                                                class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                Clear Search
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <!-- Pagination -->
                    @if ($purchaseOrders->hasPages())
                        <div class="mt-6 bg-white p-4 rounded-lg border border-black/10">
                            {{ $purchaseOrders->appends(request()->except('page'))->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
    <x-delete-confirmation />
    <x-history-model />
@endsection

@push('scripts')
    <script>
        // Get modal element
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDelete = document.getElementById('confirmDelete');

        // Per page selector
        document.getElementById('perPageSelect').addEventListener('change', function() {
            document.getElementById('perPageHidden').value = this.value;
            this.closest('form').submit();
        });

        // Delete button click handler
        document.addEventListener('click', function(e) {
            if (e.target.closest('.delete-btn')) {
                e.preventDefault();
                e.stopPropagation();
                deleteModal.classList.remove('hidden');
            }
        });

        // Cancel delete
        if (cancelDelete) {
            cancelDelete.addEventListener('click', () => {
                deleteModal.classList.add('hidden');
            });
        }

        // Confirm delete
        if (confirmDelete) {
            confirmDelete.addEventListener('click', () => {
                alert('Item deleted successfully!');
                deleteModal.classList.add('hidden');
            });
        }

        // Close modal when clicking outside
        if (deleteModal) {
            deleteModal.addEventListener('click', (e) => {
                if (e.target === deleteModal) {
                    deleteModal.classList.add('hidden');
                }
            });
        }

        // Select All functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.select-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Close all dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        // Export dropdown toggle
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            dropdown.classList.toggle('hidden');
        }

        // Close export dropdown when clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.closest('button') && !e.target.closest('[id$="Dropdown"]')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(el => el.classList.add('hidden'));
            }
        });
    </script>
@endpush
