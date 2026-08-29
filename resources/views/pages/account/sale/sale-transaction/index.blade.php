@extends('layouts.auth')

@section('title', 'Sale Transaction Report')

@section('content')
    <x-acccount.loader />
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.sale') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['route'] . '*') }}" />
                @endforeach
            </div>
        </div>
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden ">

                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Sale Transaction Report</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-2 relative ">
                            <x-columns :columns="[
                                'st_date' => 'Date',
                                'st_reference_no' => 'Reference No',
                                'st_customer' => 'Customer',
                                'st_product' => 'Product',
                                'st_account' => 'Account',
                                'st_kg' => 'KG',
                                'st_quantity' => 'Qty',
                                'st_price' => 'Price',
                                'st_total_amount' => 'Total Amount',
                            ]" name="account_sale_transaction_columns" />

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
                                            <button onclick="startExport('pdf','sale-transaction')"
                                                class="w-full text-left px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                PDF
                                            </button>
                                        </li>
                                        <li>
                                            <button onclick="startExport('xlsx','sale-transaction')"
                                                class="w-full text-left px-4 py-2 hover:bg-[#0078C8] hover:text-white transition-colors flex items-center gap-2">
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

                    <form method="GET" id="filter-form" class="mt-3">
                        <div class="bg-white rounded-md border border-[#C0BFBF] ">
                            <!-- Filters -->
                            <div class="p-4 border-b border-gray-200 ">
                                <div class="flex flex-wrap items-end gap-3">
                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">From</label>
                                        <input type="date" name="from_date" value="{{ request('from_date') }}"
                                            data-filter
                                            class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent focus:outline-none">
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">To</label>
                                        <input type="date" name="to_date" value="{{ request('to_date') }}" data-filter
                                            class="date w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent focus:outline-none">
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">Select Account</label>
                                        <x-acccount.coa.coa-select name="account_id" />
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">Select Customer</label>
                                        <select name="customer_id" data-filter
                                            class="ajax-customer-select select2 w-full py-[9px] px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-[#0078C8] focus:border-transparent focus:outline-none">
                                        </select>
                                    </div>

                                    <div class="flex-1">
                                        <label class="block text-xs text-gray-500 mb-1">Product Name</label>
                                        <input type="text" name="product_name" value="{{ request('product_name') }}"
                                            data-filter
                                            class="w-full py-2 px-3 border border-gray-300 rounded text-sm focus:ring-1 focus:outline-none focus:ring-[#0078C8] focus:border-transparent"
                                            placeholder="product name">
                                    </div>

                                    <div class="flex-1">
                                        <x-default-page />
                                    </div>

                                    <div class="flex gap-2 items-end mt-1">
                                        <button type="button" id="search-btn"
                                            class=" px-4 py-1.5 border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8]">
                                            <span id="search-text">SEARCH</span>
                                            <span id="search-loader" class="hidden ml-2">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </span>
                                        </button>
                                        <button type="button" id="reset-btn"
                                            class=" px-4 py-1.5 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4]">RESET</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Table -->
                            <div id="sale-transaction-table">
                                @include('pages.account.sale.sale-transaction.table')
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
    </script>
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter
        ReportFilter.init({
            url: '{{ route('account.sale.sale-transaction.index') }}',
            tableSelector: '#sale-transaction-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });

        // Initialize AJAX Account Select
        $('.ajax-account-select').select2({
            placeholder: "-- Select Account --",
            allowClear: true,
            ajax: {
                url: '{{ route('account.organization-settings.account.search') }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function(data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data,
                        pagination: {
                            more: (params.page * 20) < data.total_count
                        }
                    };
                },
                cache: true
            },
            minimumInputLength: 0
        });

        // Initialize AJAX Customer Select
        $('.ajax-customer-select').select2({
            placeholder: '-- Select Customer --',
            allowClear: true,
            ajax: {
                url: '{{ route('account.sale.customers.paginate') }}',
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
                        results: data.data.map(customer => ({
                            id: customer.id,
                            text: customer.name
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
