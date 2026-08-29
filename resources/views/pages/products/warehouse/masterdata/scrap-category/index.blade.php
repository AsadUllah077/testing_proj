{{-- resources/views/pages/products/warehouse/masterdata/scrap-category/index.blade.php --}}
@extends('layouts.auth')

@section('title', 'Scrap Categories List')

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
                    <div
                        class="p-5 mb-4 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                        <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Scrap Categories Management</h2>
                        <div class="flex items-center gap-2">
                            @if(auth()->user()->hasPermission('warehouse.master-data.scrap-category.bulk-delete'))
                            <div id="bulk-actions" class="hidden">
                                <button onclick="confirmBulkDelete()"
                                    class="px-4 py-2 bg-red-600 text-white text-xs font-normal rounded hover:bg-red-700 flex items-center justify-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                    BULK DELETE (<span id="selected-count">0</span>)
                                </button>
                            </div>
                            @endif

                            @if(auth()->user()->hasPermission('warehouse.master-data.scrap-category.create'))
                            <a href="{{ route('warehouse.master-data.scrap-category.create') }}"
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
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
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
                            <form method="GET" id="filter-form" class="flex flex-wrap items-end gap-4">
                                <div class="flex-1 min-w-[150px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">From Date</label>
                                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                                        class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>
                                <div class="flex-1 min-w-[150px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">To Date</label>
                                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                                        class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>
                                @if (!Auth::user()->company_id)
                                    <div class="flex-1 min-w-[150px]">
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
                                    <div class="flex-1 min-w-[150px] hidden">
                                        <input type="hidden" name="company_id" value="{{ Auth::user()->company_id }}">
                                    </div>
                                @endif
                                <div class="flex-1 min-w-[150px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                                    <input type="text" name="q" placeholder="Search scrap category..."
                                        value="{{ request('q') }}"
                                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>
                                <div class="flex flex-col">
                                    <x-default-page />
                                </div>
                                <x-filter-button />
                            </form>
                        </div>

                        <!-- Table -->
                        <div id="scrap-category-table">
                            @include('pages.products.warehouse.masterdata.scrap-category.partials.table')
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
        const bulkActions = document.getElementById('bulk-actions');
        const selectedCount = document.getElementById('selected-count');

        function updateBulkActions() {
            const checked = document.querySelectorAll('.rowCheck:checked');
            if (checked.length > 0) {
                bulkActions?.classList.remove('hidden');
                if (selectedCount) selectedCount.innerText = checked.length;
            } else {
                bulkActions?.classList.add('hidden');
            }
        }

        checkAll?.addEventListener('change', function() {
            const rowChecks = document.querySelectorAll('.rowCheck');
            rowChecks.forEach(chk => chk.checked = this.checked);
            updateBulkActions();
        });

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('rowCheck')) {
                updateBulkActions();
            }
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
            rowSel: null,
            ids: [],
            isBulk: false
        };
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        function confirmDelete(btnEl) {
            deleteTarget = {
                url: btnEl.dataset.url,
                rowSel: btnEl.dataset.row,
                isBulk: false
            };
            deleteModal?.classList.remove('hidden');
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        }
        window.confirmDelete = confirmDelete;

        function confirmBulkDelete() {
            const checked = document.querySelectorAll('.rowCheck:checked');
            const ids = Array.from(checked).map(chk => chk.value);

            deleteTarget = {
                url: '{{ route('warehouse.master-data.scrap-category.bulk-delete') }}',
                ids: ids,
                isBulk: true
            };
            deleteModal?.classList.remove('hidden');
        }
        window.confirmBulkDelete = confirmBulkDelete;

        cancelDelete?.addEventListener('click', () => {
            deleteModal?.classList.add('hidden');
            deleteTarget = {
                url: null,
                rowSel: null,
                ids: [],
                isBulk: false
            };
        });

        // AJAX DELETE
        confirmDeleteBtn?.addEventListener('click', async () => {
            if (!deleteTarget.url) return;

            const originalBtnContent = confirmDeleteBtn.innerHTML;
            confirmDeleteBtn.disabled = true;
            confirmDeleteBtn.innerHTML = `
                <div class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Deleting...</span>
                </div>
            `;

            try {
                const body = deleteTarget.isBulk ? {
                    ids: deleteTarget.ids
                } : {};

                const res = await fetch(deleteTarget.url, {
                    method: deleteTarget.isBulk ? 'POST' : 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: deleteTarget.isBulk ? JSON.stringify(body) : null
                });
                const data = await res.json();

                if (res.ok && data?.success) {
                    if (deleteTarget.isBulk) {
                        deleteTarget.ids.forEach(id => {
                            const row = document.querySelector(`tr[data-row="${id}"]`);
                            row?.parentNode?.removeChild(row);
                        });
                        bulkActions?.classList.add('hidden');
                        if (checkAll) checkAll.checked = false;
                    } else if (deleteTarget.rowSel) {
                        const row = document.querySelector(deleteTarget.rowSel);
                        row?.parentNode?.removeChild(row);
                    }
                    Notification.success(data.message || 'Action completed successfully!');
                } else {
                    const msg = data?.message || 'Action failed.';
                    Notification.error(msg);
                }
            } catch (e) {
                console.error(e);
                Notification.error('Network error. Please try again.');
            } finally {
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.innerHTML = originalBtnContent;
                deleteModal?.classList.add('hidden');
                deleteTarget = {
                    url: null,
                    rowSel: null,
                    ids: [],
                    isBulk: false
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
            url: '{{ route('warehouse.master-data.scrap-category.index') }}',
            tableSelector: '#scrap-category-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250
        });
    </script>
@endpush
