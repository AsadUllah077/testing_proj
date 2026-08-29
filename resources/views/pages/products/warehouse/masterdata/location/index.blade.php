@extends('layouts.auth')

@section('title', 'Warehouse Locations List')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.masterData') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['route'] . '*') }}" />
                @endforeach
            </div>
        </div>
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">
                        <h2 class="text-stone-950 text-xl font-normal">Warehouse Locations </h2>
                        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">

                            @if(auth()->user()->hasPermission('warehouse.master-data.locations.create'))
                            <!-- Add New Button -->
                            <a href="{{ route('warehouse.master-data.locations.create') }}"
                                class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                ADD NEW
                            </a>
                            @endif

                        </div>
                    </div>
                    @php $hasActiveFilters = request()->hasAny(['q', 'from_date', 'to_date', 'company_id']); @endphp
                    <!-- Main Content Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-5">
                        <!-- Filter Toggle Button -->
                        <div class="flex justify-end px-4 pt-3">
                            <button type="button" id="filtersToggleBtn" onclick="toggleFilters()"
                                title="Toggle Filters"
                                class="p-2 rounded-lg transition-colors {{ $hasActiveFilters ? 'bg-[#0078C8] text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                                </svg>
                            </button>
                        </div>
                        <!-- Filters Section -->
                        <div id="filtersSection" class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 {{ $hasActiveFilters ? '' : 'hidden' }}">
                            <form method="GET" id="filter-form" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                                <div class="">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">From Date</label>
                                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                                        class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>
                                <div class="">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">To Date</label>
                                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                                        class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>
                                @if (!Auth::user()->company_id)
                                    <div class="">
                                        <label class="block text-xs font-medium text-gray-600 mb-2">Company</label>
                                        <select name="company_id"
                                            class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                            <option value="">All Companies</option>
                                            @foreach ($companies as $company)
                                                <option value="{{ $company->id }}"
                                                    {{ request('company_id') == $company->id ? 'selected' : '' }}>
                                                    {{ $company->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @else
                                    <div class="hidden">
                                        <input type="hidden" name="company_id" value="{{ Auth::user()->company_id }}">
                                    </div>
                                @endif
                                <div class="">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                                    <input type="text" name="q" placeholder="Search scrap category..."
                                        value="{{ request('q') }}"
                                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>
                                <div class="">
                                    <x-default-page />
                                </div>
                                <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                                    <x-filter-button />
                                </div>
                            </form>
                        </div>

                        <!-- Table -->
                        <div id="location-table">
                            @include('pages.products.warehouse.masterdata.location.partials.table')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete confirmation modal component --}}
    <x-delete-confirmation />
@endsection

<script src="{{ asset('assets/js/report-filter.js') }}"></script>
@push('scripts')
    <script>
        // ------ Bulk check ------
        const checkAll = document.getElementById('checkAll');
        const rowChecks = document.querySelectorAll('.rowCheck');
        checkAll?.addEventListener('change', function() {
            rowChecks.forEach(chk => chk.checked = this.checked);
        });

        // ------ Dropdown toggle for each row ------
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

        // ------ Export dropdown ------
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            dropdown.classList.toggle('hidden');
        }
        window.toggleDropdown = toggleDropdown;

        function toggleFilters() {
            const section = document.getElementById('filtersSection');
            const btn = document.getElementById('filtersToggleBtn');
            const isHidden = section.classList.toggle('hidden');
            if (isHidden) {
                btn.classList.remove('bg-[#0078C8]', 'text-white');
                btn.classList.add('bg-gray-100', 'text-gray-500', 'hover:bg-gray-200');
            } else {
                btn.classList.remove('bg-gray-100', 'text-gray-500', 'hover:bg-gray-200');
                btn.classList.add('bg-[#0078C8]', 'text-white');
            }
        }
        window.toggleFilters = toggleFilters;

        // ------ Delete via modal + AJAX ------
        let deleteTarget = {
            url: null,
            rowSel: null
        };
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        function confirmDelete(btnEl) {
            deleteTarget = {
                url: btnEl.dataset.url,
                rowSel: btnEl.dataset.row
            };
            deleteModal?.classList.remove('hidden');
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        }
        window.confirmDelete = confirmDelete;

        cancelDelete?.addEventListener('click', () => {
            deleteModal?.classList.add('hidden');
            deleteTarget = {
                url: null,
                rowSel: null
            };
        });

        // AJAX DELETE
        confirmDeleteBtn?.addEventListener('click', async () => {
            if (!deleteTarget.url) return;

            try {
                const res = await fetch(deleteTarget.url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await res.json();

                if (res.ok && data?.success) {
                    // remove row
                    if (deleteTarget.rowSel) {
                        const row = document.querySelector(deleteTarget.rowSel);
                        row?.parentNode?.removeChild(row);
                    }
                    Notification.success(data.message || 'Warehouse location deleted successfully!');
                } else {
                    const msg = data?.message || 'Failed to delete warehouse location.';
                    Notification.error(msg);
                }
            } catch (e) {
                Notification.error('Network error. Please try again.');
            } finally {
                deleteModal?.classList.add('hidden');
                deleteTarget = {
                    url: null,
                    rowSel: null
                };
            }
        });

        // Close modal when clicking outside
        deleteModal?.addEventListener('click', (e) => {
            if (e.target === deleteModal) {
                deleteModal.classList.add('hidden');
                deleteTarget = {
                    url: null,
                    rowSel: null
                };
            }
        });


        ReportFilter.init({
            url: '{{ route('warehouse.master-data.locations.index') }}',
            tableSelector: '#location-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
