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
                            Purchase Orders - {{ $status == 2 ? 'Good Receive' : ($status == 1 ? 'Approved' : 'Pending') }}
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-2 items-center">

                            <div></div>
                            <div class="relative w-full">
                                <button onclick="toggleDropdown('statusDropdown')"
                                    class="px-4 py-2 border border-[#0078C8] w-full text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal">
                                    @if ($status == 2)
                                        Approved
                                    @elseif($status == 1)
                                        Cancelled
                                    @elseif($status == 3)
                                        Fully Paid
                                    @else
                                        Partial Paid
                                    @endif
                                    ▼
                                </button>
                                <div id="statusDropdown"
                                    class="hidden absolute left-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-md z-10">
                                    <ul class="py-1 text-sm text-gray-700">
                                        <li><a href="{{ route('account.purchase-order-quotation.good-recieve', ['status' => 1]) }}"
                                                class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white">Cancelled</a>
                                        </li>
                                        <li><a href="{{ route('account.purchase-order-quotation.good-recieve', ['status' => 2]) }}"
                                                class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white">Approved</a>
                                        </li>
                                        <li><a href="{{ route('account.purchase-order-quotation.good-recieve', ['status' => 3]) }}"
                                                class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white">Fully Paid</a>
                                        </li>
                                        <li><a href="{{ route('account.purchase-order-quotation.good-recieve', ['status' => 4]) }}"
                                                class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white">Partial Paid</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <!-- Select All Checkbox (centered vertically) -->
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
                            <form method="GET" action="{{ route('account.purchase-order-quotation.good-recieve') }}"
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

                    <!-- Search and Filters -->
                    <!-- Search and Reset -->
                    <form method="GET" action="{{ route('account.purchase-order-quotation.good-recieve') }}"
                        class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <input type="hidden" name="per_page" value="{{ $perPage }}" id="perPageHidden">

                        <div class="flex-1"></div>
                        <div class="flex space-x-2">
                            <input type="text" name="search" placeholder="Search by PO No, Vendor, Requested By..."
                                value="{{ request('search') }}"
                                class="px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:outline-none w-64">
                            <button type="submit"
                                class="px-4 py-1.5 border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">SEARCH</button>
                            <a href="{{ route('account.purchase-order-quotation.good-recieve', ['status' => $status]) }}"
                                class="px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">RESET</a>
                        </div>
                    </form>

                    <!-- Purchase Orders Grid -->
                    <div class="mt-6">
                        @if ($purchaseOrders->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                @foreach ($purchaseOrders as $order)
                                    <div
                                        class="w-full border border-gray-300 rounded-md overflow-hidden bg-white shadow-sm">
                                        <!-- Header -->
                                        <div class="bg-[#0078C8] flex justify-between items-center px-3 py-1.5">
                                            <span class="text-white text-xs font-semibold">PO No:
                                                {{ $order->unique_no }}</span>
                                            <span
                                                class="text-red-500 text-xs font-bold cursor-pointer leading-none delete-btn"
                                                data-id="{{ $order->id }}">x</span>
                                        </div>

                                        <!-- Body -->
                                        <div class="p-3 text-sm text-gray-800">
                                            <p><span class="font-semibold">Vendor:</span>
                                                {{ $order->vendor->name ?? 'N/A' }}</p>
                                            <p><span class="font-semibold">Requested by:</span>
                                                {{ $order->requested_by ?? 'N/A' }}</p>
                                            <p><span class="font-semibold">Ref No:</span> {{ $order->ref_no ?? 'N/A' }}</p>
                                            <p><span class="font-semibold">Invoice No:</span>
                                                {{ $order->invoice_no ?? 'N/A' }}</p>
                                            <p><span class="font-semibold">Total Amount:</span>
                                                {{ number_format($order->total, 2) }}</p>
                                            <p><span class="font-semibold">Currency:</span>
                                                {{ $order->currency->code ?? 'N/A' }}</p>
                                            <p><span class="font-semibold">Order Date:</span>
                                                {{ \Carbon\Carbon::parse($order->order_date)->format('M d, Y') }}</p>
                                            <p><span class="font-semibold">Status:</span>
                                                @if ($order->status == 3)
                                                    <span class="text-green-600 font-medium">Fully Paid</span>
                                                @elseif($order->status == 2)
                                                    <span class="text-blue-600 font-medium">Approved</span>
                                                @elseif($order->status == 4)
                                                    <span class="text-blue-600 font-medium">Partial Paid</span>
                                                @else
                                                    <span class="text-yellow-600 font-medium">Cancelled</span>
                                                @endif
                                            </p>
                                        </div>

                                        <!-- Footer -->
                                        <div class="flex justify-between items-center border-t border-gray-200 px-3 py-2">
                                            <a class="px-3 py-1 bg-[#0078C8] text-white text-xs font-medium rounded hover:bg-[#006bb0] transition "
                                               href="{{ route('account.purchase-order-quotation.gd_edit', $order->id) }}">
                                                VIEW
                                            </a>
                                            <input type="checkbox" class="w-4 h-4 cursor-pointer order-checkbox"
                                                value="{{ $order->id }}">
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Pagination -->
                            <div class="mt-6 p-5 bg-white rounded-lg border border-black/10">
                                <div class="flex flex-col md:flex-row justify-between items-center">
                                    <div class="mb-4 md:mb-0">
                                        <p class="text-sm text-gray-700">
                                            Showing {{ $purchaseOrders->firstItem() }} to
                                            {{ $purchaseOrders->lastItem() }} of {{ $purchaseOrders->total() }} results
                                        </p>
                                    </div>
                                    <div>
                                        {{ $purchaseOrders->links() }}
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="p-5 bg-white rounded-lg border border-black/10 text-center">
                                <p class="text-gray-500">No purchase orders found.</p>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <x-delete-confirmation />

    <!-- View Order Modal -->
    <div id="viewOrderModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-semibold text-gray-900">Purchase Order Details</h3>
                    <button id="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="orderDetails" class="mt-4">
                    <!-- Order details will be loaded here via AJAX -->
                </div>
                <div class="flex justify-end mt-4">
                    <button id="closeModalBtn"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">
                        CLOSE
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // Get modal elements
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDelete = document.getElementById('confirmDelete');
        const viewOrderModal = document.getElementById('viewOrderModal');
        const closeModal = document.getElementById('closeModal');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const orderDetails = document.getElementById('orderDetails');

        let selectedOrderId = null;

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
                selectedOrderId = e.target.closest('.delete-btn').getAttribute('data-id');
                // Show modal
                deleteModal.classList.remove('hidden');
                // Close all dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        // View button click handler
        document.addEventListener('click', function(e) {
            if (e.target.closest('.view-btn')) {
                e.preventDefault();
                e.stopPropagation();
                selectedOrderId = e.target.closest('.view-btn').getAttribute('data-id');
                loadOrderDetails(selectedOrderId);
                viewOrderModal.classList.remove('hidden');
            }
        });

        // Load order details via AJAX
        function loadOrderDetails(orderId) {
            // Show loading state
            orderDetails.innerHTML = '<div class="text-center py-4">Loading...</div>';

            // In a real application, you would make an AJAX request to fetch order details
            // For this example, we'll simulate it with a timeout
            setTimeout(() => {
                orderDetails.innerHTML = `
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="font-semibold">PO Number:</p>
                                <p>PO-${orderId}</p>
                            </div>
                            <div>
                                <p class="font-semibold">Vendor:</p>
                                <p>Vendor ${orderId}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="font-semibold">Requested By:</p>
                                <p>User ${orderId}</p>
                            </div>
                            <div>
                                <p class="font-semibold">Total Amount:</p>
                                <p>${(425000 + parseInt(orderId) * 1000).toLocaleString()}.00</p>
                            </div>
                        </div>
                        <div>
                            <p class="font-semibold">Description:</p>
                            <p>This is a detailed description of purchase order ${orderId}.</p>
                        </div>
                    </div>
                `;
            }, 500);
        }

        // Cancel delete
        if (cancelDelete) {
            cancelDelete.addEventListener('click', () => {
                deleteModal.classList.add('hidden');
                selectedOrderId = null;
            });
        }

        // Confirm delete
        if (confirmDelete) {
            confirmDelete.addEventListener('click', () => {
                if (selectedOrderId) {
                    // In a real application, you would submit a form or make an AJAX request to delete the order
                    alert(`Order ${selectedOrderId} deleted successfully!`);
                    // Reload the page or remove the element from DOM
                    location.reload();
                }
                deleteModal.classList.add('hidden');
                selectedOrderId = null;
            });
        }

        // Close modals when clicking outside
        if (deleteModal) {
            deleteModal.addEventListener('click', (e) => {
                if (e.target === deleteModal) {
                    deleteModal.classList.add('hidden');
                    selectedOrderId = null;
                }
            });
        }

        if (viewOrderModal) {
            viewOrderModal.addEventListener('click', (e) => {
                if (e.target === viewOrderModal) {
                    viewOrderModal.classList.add('hidden');
                    selectedOrderId = null;
                }
            });
        }

        // Close view modal with buttons
        if (closeModal) {
            closeModal.addEventListener('click', () => {
                viewOrderModal.classList.add('hidden');
                selectedOrderId = null;
            });
        }

        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => {
                viewOrderModal.classList.add('hidden');
                selectedOrderId = null;
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

        // Select All functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Bulk delete functionality
        document.getElementById('bulkDeleteBtn').addEventListener('click', function() {
            const selectedOrders = Array.from(document.querySelectorAll('.order-checkbox:checked'))
                .map(checkbox => checkbox.value);

            if (selectedOrders.length === 0) {
                alert('Please select at least one order to delete.');
                return;
            }

            if (confirm(`Are you sure you want to delete ${selectedOrders.length} order(s)?`)) {
                // In a real application, you would submit a form or make an AJAX request
                alert(`Orders ${selectedOrders.join(', ')} deleted successfully!`);
                location.reload();
            }
        });
    </script>
@endpush
