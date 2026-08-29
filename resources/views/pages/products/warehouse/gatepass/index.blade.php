{{-- resources/views/pages/products/warehouse/gate-pass/index.blade.php --}}
@extends('layouts.auth')

@section('title', 'Gate Pass Management')

@section('content')
    <x-acccount.loader />
    <div class="min-h-screen bg-gray-100 py-6">
        <div class="max-w-12xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header Bar -->
            <div class="mb-4 bg-white rounded-md border border-gray-200 p-5 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">
                <div class="flex flex-wrap items-center gap-4">
                    <h2 class="text-gray-800 text-base font-semibold tracking-wide">Gate Pass Management</h2>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2.5 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">
                            {{ $stats['total'] }} Total
                        </span>
                        <span class="px-2.5 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">
                            {{ $stats['pending_qc'] }} QC Pending
                        </span>
                        <span class="px-2.5 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-medium">
                            {{ $stats['pending_security'] }} Security Pending
                        </span>
                        <span class="px-2.5 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                            {{ $stats['approved'] }} Approved
                        </span>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                    @if(auth()->user()->hasPermission('warehouse.gate-pass.export-pdf') || auth()->user()->hasPermission('warehouse.gate-pass.export-excel'))
                    <!-- Export Dropdown -->
                    <div class="relative">
                        <button onclick="toggleDropdown('exportDropdown')"
                            class="px-4 py-1.5 border border-[#0078C8] text-[#0078C8] rounded text-xs font-semibold hover:bg-[#0078C8] hover:text-white transition-colors tracking-wide">
                            EXPORT ▼
                        </button>
                        <div id="exportDropdown"
                            class="hidden absolute right-0 mt-1 w-36 bg-white border border-gray-200 rounded shadow-md z-10">
                            <ul class="py-1 text-sm text-gray-700">
                                <li>
                                    <a href="#" onclick="exportData('pdf')"
                                        class="block px-4 py-2 text-xs hover:bg-[#0078C8] hover:text-white">PDF</a>
                                </li>
                                <li>
                                    <a href="#" onclick="exportData('excel')"
                                        class="block px-4 py-2 text-xs hover:bg-[#0078C8] hover:text-white">Excel</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    @endif
                    @if(auth()->user()->hasPermission('warehouse.gate-pass.create'))
                    <!-- Add New -->
                    <a href="{{ route('warehouse.gate-pass.create') }}"
                        class="px-4 py-1.5 bg-[#0078C8] text-white text-xs font-semibold rounded hover:bg-[#0066a0] transition-colors tracking-wide inline-flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        ADD NEW
                    </a>
                    @endif
                </div>
            </div>


            @php $hasActiveFilters = request()->hasAny(['status', 'search']); @endphp
            <!-- Filters + Table Card -->
            <div class="bg-white rounded-md border border-gray-200  overflow-hidden">
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

                <!-- Filters Row -->
                <div id="filtersSection" class="{{ $hasActiveFilters ? '' : 'hidden' }}">
                <form id="filter-form" class="px-5 py-4 border-b border-gray-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">

                    <!-- Status Filter -->
                    <div class="">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                        <select id="statusFilter" name="status"
                            class="select2 w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none bg-white">
                            <option value="All" {{ request('status', 'All') == 'All' ? 'selected' : '' }}>All Status
                            </option>
                            <option value="QC_APPROVAL_PENDING"
                                {{ request('status') == 'QC_APPROVAL_PENDING' ? 'selected' : '' }}>QC Approval Pending
                            </option>
                            <option value="SECURITY_APPROVAL_PENDING"
                                {{ request('status') == 'SECURITY_APPROVAL_PENDING' ? 'selected' : '' }}>Security Approval
                                Pending</option>
                            <option value="QC_AND_SECURITY_APPROVED"
                                {{ request('status') == 'QC_AND_SECURITY_APPROVED' ? 'selected' : '' }}>QC & Security
                                Approved</option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                        <div class="flex gap-2">
                            <input type="text" id="searchInput" name="search" placeholder="Search by ID, customer, invoice, location..."
                                value="{{ request('search') }}"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none">
                        </div>
                    </div>

                        <div class="w-32">
                            <x-default-page />
                        </div>
                        <x-filter-button />
                        @if(auth()->user()->hasPermission('warehouse.gate-pass.bulk-delete'))
                        <!-- Bulk Delete -->
                        <button type="button" id="bulkDeleteBtn"
                            class="px-3 py-2 bg-red-50 text-red-600 border border-red-200 rounded text-xs font-medium hover:bg-red-100 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                            disabled>
                            Delete Selected
                        </button>
                        @endif
                </form>
                </div>

                <!-- Selected count bar -->
                <div id="selectedBar"
                    class="hidden px-5 py-2.5 bg-blue-50 border-b border-blue-100 flex items-center justify-between">
                    <span class="text-xs font-medium text-blue-700">
                        <span id="selectedCount">0</span> gate passes selected
                    </span>
                    <button type="button" id="clearSelection"
                        class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                        Clear selection
                    </button>
                </div>

                <!-- Table -->
                <div id="gate-pass-table" class="overflow-x-auto">
                    @include('pages.products.warehouse.gatepass.partials.table')
                </div>
            </div>

        </div>
    </div>
    <x-delete-confirmation />
@endsection

@push('scripts')
<script src="{{ asset('assets/js/report-filter.js') }}"></script>
<script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // ---- Filter Toggle ----
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

        // ---- Checkboxes ----
        function initializeCheckboxes() {
            const checkAll = document.getElementById('checkAll');
            const rowChecks = document.querySelectorAll('.rowCheck');

            if (checkAll) {
                checkAll.addEventListener('change', function() {
                    rowChecks.forEach(c => c.checked = this.checked);
                    updateSelectedCount();
                });
            }
            rowChecks.forEach(c => c.addEventListener('change', function() {
                updateSelectAllCheckbox();
                updateSelectedCount();
            }));
        }

        function updateSelectAllCheckbox() {
            const checkAll = document.getElementById('checkAll');
            const checkboxes = document.querySelectorAll('.rowCheck');
            const allChecked = checkboxes.length > 0 && [...checkboxes].every(c => c.checked);
            const someChecked = [...checkboxes].some(c => c.checked);
            if (checkAll) {
                checkAll.checked = allChecked;
                checkAll.indeterminate = someChecked && !allChecked;
            }
        }



        function updateSelectedCount() {
            const n = document.querySelectorAll('.rowCheck:checked').length;
            document.getElementById('selectedCount').textContent = n;
            document.getElementById('selectedBar').classList.toggle('hidden', n === 0);
            const btn = document.getElementById('bulkDeleteBtn');
            if (btn) {
                n > 0 ? btn.removeAttribute('disabled') : btn.setAttribute('disabled', 'disabled');
            }
        }

        function getSelectedIds() {
            return [...document.querySelectorAll('.rowCheck:checked')].map(c => c.value).filter(Boolean);
        }

        document.getElementById('clearSelection')?.addEventListener('click', function() {
            document.querySelectorAll('.rowCheck, #checkAll').forEach(c => {
                c.checked = false;
                c.indeterminate = false;
            });
            updateSelectedCount();
        });

        // ---- Dropdown ----
        window.toggleDropdown = function(id) {
            const el = document.getElementById(id);
            document.querySelectorAll('[id$="Dropdown"]').forEach(d => {
                if (d.id !== id) d.classList.add('hidden');
            });
            el.classList.toggle('hidden');
        };
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[onclick*="toggleDropdown"]') && !e.target.closest('[id$="Dropdown"]')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(d => d.classList.add('hidden'));
            }
        });
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.dropdown-btn');
            if (btn) {
                e.stopPropagation();
                const menu = btn.nextElementSibling;
                document.querySelectorAll('.dropdown-menu').forEach(m => {
                    if (m !== menu) m.classList.add('hidden');
                });
                menu.classList.toggle('hidden');
            }
        });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            }
        });

        // ---- Expandable Gate Pass Details ----
        document.addEventListener('click', function(e) {
            const button = e.target.closest('.gate-pass-expand');
            if (!button) return;

            const detailsRow = document.getElementById(button.dataset.target);
            if (!detailsRow) return;

            const willOpen = detailsRow.classList.contains('hidden');
            detailsRow.classList.toggle('hidden', !willOpen);
            button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            button.title = willOpen ? 'Hide gate pass details' : 'Show gate pass details';
            button.querySelector('.gate-pass-expand-icon')?.classList.toggle('rotate-90', willOpen);
        });
        document.querySelector('select[name="per_page"]')?.addEventListener('change', () => ReportFilter.debouncedFilter());
        document.getElementById('statusFilter')?.addEventListener('change', () => ReportFilter.debouncedFilter());
        document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); ReportFilter.debouncedFilter(); }
        });

        // ---- ReportFilter ----
        ReportFilter.init({
            url: '{{ route('warehouse.gate-pass.index') }}',
            tableSelector: '#gate-pass-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250,
        });

        // ---- Bulk Delete ----
        document.getElementById('bulkDeleteBtn')?.addEventListener('click', function() {
            const ids = getSelectedIds();
            if (!ids.length) {
                Notification.success('Please select gate passes to delete', 'error');
                return;
            }
            if (!confirm(`Are you sure you want to delete ${ids.length} gate pass(es)?`)) return;

            fetch('{{ route('warehouse.gate-pass.bulk-delete') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        ids
                    })
                })
                .then(r => r.json())
                .then(data => {
                    Notification.success(data.message);
                    if (data.success) setTimeout(() => window.location.reload(), 1500);
                })
                .catch(() => Notification.error('Delete failed. Please try again.'));
        });

        // ---- Single Delete ----
        window.confirmDelete = function(button) {
            const url = button.dataset.url;
            const row = button.dataset.row;
            if (!confirm('Are you sure you want to delete this gate pass?')) return;

            fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    Notification.success(data.message);
                    if (data.success) {
                        const el = document.querySelector(row);
                        if (el) {
                            document.querySelector(`[data-parent-gate-pass-id="${el.dataset.gatePassId}"]`)?.remove();
                            el.remove();
                        }
                        updateSelectedCount();
                    }
                })
                .catch(() => Notification.error('Network error. Please try again.'));
        };

        // ---- Export ----
        window.exportData = function(format) {
            const params = new URLSearchParams(window.location.search);
            const url = format === 'pdf' ? '{{ route('warehouse.gate-pass.export.pdf') }}' :
                '{{ route('warehouse.gate-pass.export.excel') }}';
            window.open(`${url}?${params.toString()}`, '_blank');
            Notification.success(`Exporting as ${format.toUpperCase()}...`, 'info');
        };
        document.addEventListener('DOMContentLoaded', function() {
            initializeCheckboxes();
            updateSelectedCount();
        });
    </script>
@endpush
