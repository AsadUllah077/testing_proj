@extends('layouts.auth')

@section('title', 'Manage Customer')

@section('content')
    <x-acccount.loader />
    <div class="mb-8">
        <!-- Navigation Tabs -->
        <div class="p-4 mb-6">
            <div class="flex items-center gap-2 flex-wrap">
                @foreach (config('modules.sale') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['route'] . '*') }}" />
                @endforeach
            </div>
        </div>

        <div class="mx-auto px-3">
            <div class="overflow-hidden">
                <!-- Header -->
                <div
                    class="bg-white rounded-lg shadow-sm border border-black/10 p-5 mb-6 md:flex block justify-between items-center">
                    <div>
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Manage Customer</h2>
                    </div>

                    <div class="flex flex-wrap gap-2 items-center">
                        <div class="flex items-center gap-2 relative">
                            <x-columns :columns="[
                                'cs_no' => 'S.No',
                                'c_name' => 'Name',
                                'c_email' => 'Email',
                                'c_phone' => 'Phone',
                                'c_address' => 'Address',
                                'c_due_balance' => 'Due Balance',
                            ]" name="account_vendor_columns" />

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
                                    class="hidden absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-20">
                                    <ul class="py-1 text-sm">
                                        <li>
                                            <button onclick="startExport('pdf','customer')"
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
                                            <button onclick="startExport('xlsx','customer')"
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

                            <button onclick="openCustomerModal('create')"
                                class="px-5 py-1.5 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-all duration-200 shadow-sm flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Add New
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Bulk Actions & Per Page -->
                <div class="flex justify-between items-center mb-4">
                    <x-bulk-delete url="account.sale.customers.bulk-delete" />
                </div>

                <!-- Filters & Table -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <!-- Filters -->
                    <div id="filter-form" class="p-4 mt-4">
                        <div class="flex justify-end items-center flex-wrap gap-3">
                            <div class="relative">
                                <label class="block text-xs text-gray-500 mb-1">Search</label>
                                <input type="search" name="search" value="{{ request('search') }}" data-filter
                                    placeholder="Search name, email, phone..."
                                    class="w-80 border border-gray-300 rounded-lg pr-4 py-2 text-sm focus:border-[#0078C8] focus:ring-1 focus:ring-[#0078C8]/20 focus:outline-none transition-all duration-200">
                            </div>

                            <div class="relative">
                                <x-default-page />
                            </div>

                            <div class="flex gap-2 mt-2">
                                <button type="button" onclick="ReportFilter.filter()"
                                    class="px-5 py-2 border border-[#0078C8] text-[#0078C8] rounded-lg hover:bg-[#0078C8] hover:text-white transition-all duration-200 text-sm font-medium">
                                    Search
                                </button>
                                <a href="{{ route('account.sale.customers.index') }}"
                                    class="px-5 py-2 bg-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] transition-all duration-200 text-sm font-medium">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Table Content -->
                    <div id="customer-table">
                        @include('pages.account.sale.customer.partials.table')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Customer Modal -->
    <x-acccount.customer.create-customer />

    <x-delete-confirmation />

@endsection

@push('scripts')
    <script src="{{ asset('assets/js/report-filter.js') }}"></script>
    <script>
        // Initialize global report filter for AJAX
        ReportFilter.init({
            url: '{{ route('account.sale.customers.index') }}',
            tableSelector: '#customer-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            extraFilterContainers: [document.getElementById('perPageForm')],
            debounceDelay: 300
        });

        // Dropdown handlers
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.dropdown-btn');
            if (btn) {
                e.stopPropagation();
                const menu = btn.nextElementSibling;
                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(m => {
                    if (m !== menu) m.classList.add('hidden');
                });
                // Toggle current dropdown
                menu.classList.toggle('hidden');
            }
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            dropdown.classList.toggle('hidden');
        }

        window.addEventListener('click', function(e) {
            if (!e.target.closest('button') && !e.target.closest('[id$="Dropdown"]')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(el => el.classList.add('hidden'));
            }
        });
    </script>
@endpush
