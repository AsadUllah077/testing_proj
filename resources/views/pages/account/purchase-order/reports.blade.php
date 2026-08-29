@extends('layouts.auth')

@section('title', 'Purchase Order Report')

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
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Purchase Order Report</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-2 relative">
                            <div></div>
                            <x-columns :columns="[
                                'por_order_date' => 'Order Date',
                                'por_departure_date' => 'Departure Date',
                                'por_reference_no' => 'Reference No',
                                'por_requested_by' => 'Requested By',
                                'por_product' => 'Product',
                                'por_status' => 'Status',
                                'por_account' => 'Account',
                                'por_qty' => 'Qty',
                                'por_price' => 'Price',
                                'por_total_amount' => 'Total Amount',
                            ]" name="account_purchase_order_reports_columns" />

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
                                            <button onclick="startExport('pdf','purchase-order-report')" data-url=""
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
                                            <button onclick="startExport('xlsx','purchase-order-report')"
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
                        </div>
                    </div>

                    <!-- Filters Form -->
                    <form action="{{ route('account.purchase-order-quotation.reports') }}" method="GET" id="filterForm">
                        <div class="bg-white rounded-md border border-[#C0BFBF] mt-5">
                            <!-- Filters -->
                            <div class="p-4 border-b border-gray-200">
                                <div class="flex flex-wrap items-end gap-3">
                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">From</label>
                                        <input type="date" name="from_date" value="{{ request('from_date') }}"
                                            class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">To</label>
                                        <input type="date" name="to_date" value="{{ request('to_date') }}"
                                            class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">Select Account</label>
                                        <x-acccount.coa.coa-select name="account_id" />
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">Select Vendor</label>
                                        <select name="vendor_id"
                                            class="ajax-vendor-select w-full py-[9px] px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                                        </select>
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">Requested By</label>
                                        <input type="text" name="requested_by" value="{{ request('requested_by') }}"
                                            placeholder="request by..."
                                            class="w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">Product Name</label>
                                        <input type="search" name="product_name" value="{{ request('product_name') }}"
                                            placeholder="product name..."
                                            class="w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:outline-none">
                                    </div>

                                    <div class="flex gap-2 items-end mt-1">
                                        <button type="submit"
                                            class="px-4 py-1.5 border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">
                                            SEARCH
                                        </button>
                                        <button type="button" onclick="resetFilters()"
                                            class="px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">
                                            RESET
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Table -->
                            <div class="overflow-x-auto w-full p-4">
                                @if ($items->count() > 0)
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left" data-col="por_order_date">Order Date</th>
                                                <th class="px-4 py-3 text-left" data-col="por_departure_date">Departure Date
                                                </th>
                                                <th class="px-4 py-3 text-left" data-col="por_reference_no">Reference No
                                                </th>
                                                <th class="px-4 py-3 text-left" data-col="por_requested_by">Requested By
                                                </th>
                                                <th class="px-4 py-3 text-left" data-col="por_product">Product</th>
                                                <th class="px-4 py-3 text-left" data-col="por_status">Status</th>
                                                <th class="px-4 py-3 text-left" data-col="por_account">Account</th>
                                                <th class="px-4 py-3 text-left" data-col="por_qty">Qty</th>
                                                <th class="px-4 py-3 text-left" data-col="por_price">Price</th>
                                                <th class="px-4 py-3 text-center" data-col="por_total_amount">Total Amount
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($items as $item)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2" data-col="por_order_date">
                                                        {{ $item->purchaseOrderQuotation?->order_date->format('d/m/Y') }}
                                                    </td>
                                                    <td class="px-4 py-2" data-col="por_departure_date">
                                                        {{ $item->purchaseOrderQuotation?->departure_date ? $item->purchaseOrderQuotation->departure_date->format('d/m/Y') : 'N/A' }}
                                                    </td>
                                                    <td class="px-4 py-2" data-col="por_reference_no">
                                                        {{ $item->purchaseOrderQuotation?->ref_no ?? 'N/A' }}
                                                    </td>
                                                    <td class="px-4 py-2" data-col="por_requested_by">
                                                        {{ $item->purchaseOrderQuotation?->createdBy->fullName ?? 'N/A' }}
                                                    </td>
                                                    <td class="px-4 py-2" data-col="por_product">{{ $item->item }}</td>
                                                    <td class="px-4 py-2" data-col="por_status">
                                                        @php
                                                            $statuses = [
                                                                1 => 'Cancelled',
                                                                2 => 'Approved',
                                                                3 => 'Fully Paid',
                                                                4 => 'Partial Paid',
                                                                0 => 'Pending',
                                                            ];
                                                        @endphp
                                                        {{ $statuses[$item->purchaseOrderQuotation->status] ?? 'Unknown' }}
                                                    </td>
                                                    <td class="px-4 py-2" data-col="por_account">
                                                        {{ $item->account->name ?? 'N/A' }}</td>
                                                    <td class="px-4 py-2" data-col="por_qty">{{ $item->quantity }}</td>
                                                    <td class="px-4 py-2 text-left" data-col="por_price">
                                                        {{ number_format($item->price, 2) }}</td>
                                                    <td class="px-4 py-2 text-center" data-col="por_total_amount">
                                                        {{ number_format($item->total, 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <!-- Totals Footer -->
                                        @if ($totals['total_quantity'] > 0 || $totals['total_amount'] > 0)
                                            <tfoot class="bg-gray-50 font-semibold">
                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="px-4 py-3 text-left text-[#0078C8]">Total</td>
                                                    <td class="px-4 py-3 text-left text-[#0078C8]">
                                                        {{ $totals['total_quantity'] }}</td>
                                                    <td class="px-4 py-3 text-left text-[#0078C8]">
                                                        {{ number_format($totals['total_price'], 2) }}</td>
                                                    <td class="px-4 py-3 text-center text-[#0078C8]">
                                                        {{ number_format($totals['total_amount'], 2) }}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>

                                    <!-- Pagination -->
                                    <div class="mt-4">
                                        {{ $items->links() }}
                                    </div>
                                @else
                                    <div class="text-center py-8 text-gray-500">
                                        No records found.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Dropdown toggle function
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            // Close all other dropdowns
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            // Toggle current dropdown
            dropdown.classList.toggle('hidden');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[id$="Dropdown"]') && !e.target.closest('button')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                    el.classList.add('hidden');
                });
            }
        });

        // Reset filters
        function resetFilters() {
            const form = document.getElementById('filterForm');
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.type === 'text' || input.type === 'date' || input.type === 'search') {
                    input.value = '';
                } else if (input.tagName === 'SELECT') {
                    input.selectedIndex = 0;
                }
            });
            form.submit();
        }

        // Close export dropdown when clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.closest('button') && !e.target.closest('[id$="Dropdown"]')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(el => el.classList.add('hidden'));
            }
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
