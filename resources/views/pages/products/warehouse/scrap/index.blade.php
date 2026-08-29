{{-- resources/views/pages/products/warehouse/scrap/index.blade.php --}}
@extends('layouts.auth')

@section('title', 'Scrap Material')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="mb-4 p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">
                        <h2 class="text-stone-950 text-xl font-normal">Scrap Material</h2>
                        <div class="flex flex-wrap gap-2 items-center justify-end w-full sm:w-auto">

                            @if(auth()->user()->hasPermission('warehouse.scrap.bulk-delete'))
                            <!-- Bulk Delete Button (hidden until rows selected) -->
                            <button id="bulkDeleteBtn" onclick="confirmBulkDelete()"
                                class="hidden px-4 py-2 bg-red-600 text-white text-xs font-normal rounded hover:bg-red-700 items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4 inline-block">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                                <span id="bulkDeleteBtnText">DELETE SELECTED</span>
                            </button>
                            @endif

                            <!-- Column Chooser -->
                            <x-columns :columns="[
                                'scrap_id' => 'Scrap ID',
                                'type_of_material' => 'Type Of Material',
                                'source' => 'Source',
                                'weight' => 'Weight',
                                'created_at' => 'Created At',
                                'updated_at' => 'Updated At',
                            ]" name="scrap_columns" />

                            @if(auth()->user()->hasPermission('warehouse.scrap.export-pdf') || auth()->user()->hasPermission('warehouse.scrap.export-excel') || auth()->user()->hasPermission('warehouse.scrap.export-csv'))
                            <!-- Export Dropdown -->
                            <div class="relative">
                                <button onclick="toggleDropdown('exportScrapDropdown')"
                                    class="px-4 py-2 border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal">
                                    EXPORT ▼
                                </button>
                                <div id="exportScrapDropdown"
                                    class="hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-md z-30">
                                    <ul class="py-1 text-sm text-gray-700">
                                        <li>
                                            <a href="{{ route('warehouse.scrap.export.pdf') }}"
                                                class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white">PDF</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('warehouse.scrap.export.excel') }}"
                                                class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white">Excel</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('warehouse.scrap.export.csv') }}"
                                                class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white">CSV</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            @endif

                            @if(auth()->user()->hasPermission('warehouse.scrap.create'))
                            <!-- Add New Button -->
                            <a href="{{ route('warehouse.scrap.create') }}"
                                class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                ADD
                            </a>
                            @endif
                        </div>
                    </div>



                    @php $hasActiveFilters = request()->hasAny(['search', 'source']); @endphp
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

                        <!-- Filter Section -->
                        <div id="filtersSection" class="p-6 border-b border-gray-200 {{ $hasActiveFilters ? '' : 'hidden' }}">
                            <form id="filter-form" class="flex flex-wrap items-end gap-4">
                                <div class="flex-1 min-w-[160px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                                    <input type="text" name="search" placeholder="Search by ID, description, source..."
                                        value="{{ request('search') }}"
                                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>
                                <div class="flex-1 min-w-[160px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Source</label>
                                    <select name="source"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        <option value="">All Sources</option>
                                        <option value="failed_unit"
                                            {{ request('source') == 'failed_unit' ? 'selected' : '' }}>Failed Unit
                                        </option>
                                        <option value="extraction_remains"
                                            {{ request('source') == 'extraction_remains' ? 'selected' : '' }}>Extraction
                                            Remains</option>
                                        <option value="production_waste"
                                            {{ request('source') == 'production_waste' ? 'selected' : '' }}>Production
                                            Waste</option>
                                        <option value="damaged_goods"
                                            {{ request('source') == 'damaged_goods' ? 'selected' : '' }}>Damaged
                                            Goods</option>
                                        <option value="rejected_material"
                                            {{ request('source') == 'rejected_material' ? 'selected' : '' }}>Rejected
                                            Material</option>
                                    </select>
                                </div>
                                <div class="flex-1 min-w-[110px]">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Per Page</label>
                                    <select name="per_page"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        @foreach ([10, 20, 30, 50, 100] as $size)
                                            <option value="{{ $size }}"
                                                {{ (int) request('per_page', 20) === $size ? 'selected' : '' }}>
                                                {{ $size }} / page
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex justify-end gap-3">
                                    <button type="button" id="reset-btn"
                                        class="px-6 py-2.5 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors">
                                        RESET
                                    </button>
                                    <button type="button" id="search-btn"
                                        class="px-6 py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors inline-flex items-center gap-2">
                                        <span id="search-text">SEARCH</span>
                                        <span id="search-loader" class="hidden">
                                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 22 6.477 22 12h-4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Table -->
                        <div id="scrap-table">
                            @include('pages.products.warehouse.scrap.partials.table')
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast"
        class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
        <div id="toast-content" class="text-sm text-gray-800"></div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div id="deleteConfirmationModal" class="fixed inset-0 z-[9999] hidden">
        <div class="flex items-center justify-center min-h-screen px-4">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/50" id="modalBackdrop"></div>
            {{-- Modal Panel --}}
            <div class="relative bg-white rounded-lg text-left shadow-xl w-full max-w-lg z-10">
                <div class="bg-white px-6 pt-6 pb-4 rounded-t-lg">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 id="deleteModalTitle" class="text-lg font-medium text-gray-900">
                                Delete Scrap Material
                            </h3>
                            <p id="deleteModalMessage" class="mt-2 text-sm text-gray-500">
                                Are you sure you want to delete this scrap material? This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3 rounded-b-lg">
                    <button type="button" id="confirmDeleteBtn"
                        class="inline-flex justify-center rounded-md px-4 py-2 bg-red-600 text-sm font-medium text-white hover:bg-red-700 focus:outline-none disabled:opacity-60 disabled:cursor-not-allowed transition-colors">
                        Delete
                    </button>
                    <button type="button" id="cancelDeleteBtn"
                        class="inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="{{ asset('assets/js/report-filter.js') }}"></script>
<script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // =====================
        // Filter Toggle
        // =====================
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

        // =====================
        // Export Dropdown ONLY
        // (kept completely separate from action dropdowns)
        // =====================
        function toggleDropdown(id) {
            const el = document.getElementById(id);
            const isHidden = el.classList.contains('hidden');
            // Close all named export dropdowns first
            document.querySelectorAll('[id$="Dropdown"]').forEach(d => d.classList.add('hidden'));
            // Then open the clicked one if it was closed
            if (isHidden) el.classList.remove('hidden');
        }
        window.toggleDropdown = toggleDropdown;

        // Close export dropdown on outside click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[onclick*="toggleDropdown"]') && !e.target.closest('[id$="Dropdown"]')) {
                document.querySelectorAll('[id$="Dropdown"]').forEach(d => d.classList.add('hidden'));
            }
        });

        // =====================
        // Three-dots Action Dropdown
        // Single consolidated handler — no conflicts
        // =====================
        document.addEventListener('click', function(e) {

            // 1. Clicked a three-dots button
            const btn = e.target.closest('.dropdown-btn');
            if (btn) {
                e.stopPropagation(); // prevent the outside-click handler from firing
                const menu = btn.nextElementSibling;

                // Close every other open action menu
                document.querySelectorAll('.dropdown-menu').forEach(m => {
                    if (m !== menu) m.classList.add('hidden');
                });

                // Toggle this menu
                menu.classList.toggle('hidden');
                return; // stop — don't fall through to the close-all logic below
            }

            // 2. Clicked inside an open action menu (e.g. a link/button inside it) — leave it open
            if (e.target.closest('.dropdown-menu')) {
                return;
            }

            // 3. Clicked anywhere else — close all action menus
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        });

        // =====================
        // Checkboxes & Bulk Delete Button
        // =====================
        function updateBulkDeleteBtn() {
            const selected = document.querySelectorAll('.rowCheckScrap:checked').length;
            const btn = document.getElementById('bulkDeleteBtn');
            const btnText = document.getElementById('bulkDeleteBtnText');
            if (!btn) return;
            if (selected > 0) {
                btn.classList.remove('hidden');
                btn.classList.add('inline-flex');
                btnText.textContent = `DELETE SELECTED (${selected})`;
            } else {
                btn.classList.add('hidden');
                btn.classList.remove('inline-flex');
            }
        }
        window.updateBulkDeleteBtn = updateBulkDeleteBtn;

        // Event delegation for checkboxes (works after any DOM update)
        document.addEventListener('change', function(e) {
            // "Check All" checkbox
            if (e.target && e.target.id === 'checkAllScrap') {
                document.querySelectorAll('.rowCheckScrap').forEach(c => c.checked = e.target.checked);
                updateBulkDeleteBtn();
                return;
            }
            // Individual row checkbox
            if (e.target && e.target.classList.contains('rowCheckScrap')) {
                const all = document.querySelectorAll('.rowCheckScrap');
                const checked = document.querySelectorAll('.rowCheckScrap:checked');
                const checkAll = document.getElementById('checkAllScrap');
                if (checkAll) {
                    checkAll.checked = all.length > 0 && all.length === checked.length;
                    checkAll.indeterminate = checked.length > 0 && checked.length < all.length;
                }
                updateBulkDeleteBtn();
            }
        });



        // =====================
        // ReportFilter
        // =====================
        ReportFilter.init({
            url: '{{ route('warehouse.scrap.index') }}',
            tableSelector: '#scrap-table',
            paginationSelector: '#pagination-wrapper',
            filterContainer: document.getElementById('filter-form'),
            debounceDelay: 250,
        });

        // =====================
        // Delete State
        // =====================
        let _deleteMode = null; // 'single' | 'bulk'
        let _deleteUrl = null;
        let _deleteId = null;

        function showDeleteModal() {
            document.getElementById('deleteConfirmationModal').classList.remove('hidden');
        }

        function hideDeleteModal() {
            document.getElementById('deleteConfirmationModal').classList.add('hidden');
            // Reset button state
            const btn = document.getElementById('confirmDeleteBtn');
            btn.disabled = false;
            btn.textContent = 'Delete';
            // Clear state
            _deleteMode = null;
            _deleteUrl = null;
            _deleteId = null;
        }
        window.hideDeleteModal = hideDeleteModal;

        // Close modal on backdrop click
        document.getElementById('modalBackdrop').addEventListener('click', hideDeleteModal);
        // Close modal on cancel button
        document.getElementById('cancelDeleteBtn').addEventListener('click', hideDeleteModal);

        // =====================
        // Single Delete
        // =====================
        function deleteScrap(id, url) {
            _deleteMode = 'single';
            _deleteUrl = url;
            _deleteId = id;
            document.getElementById('deleteModalTitle').textContent = 'Delete Scrap Material';
            document.getElementById('deleteModalMessage').textContent =
                'Are you sure you want to delete this scrap material? This action cannot be undone.';
            // Close any open action dropdown first
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
            showDeleteModal();
        }
        window.deleteScrap = deleteScrap;

        // =====================
        // Bulk Delete
        // =====================
        function confirmBulkDelete() {
            const selected = document.querySelectorAll('.rowCheckScrap:checked');
            if (selected.length === 0) {
                Notification.error('Please select at least one record to delete.');
                return;
            }
            _deleteMode = 'bulk';
            document.getElementById('deleteModalTitle').textContent = `Delete ${selected.length} Scrap Material(s)`;
            document.getElementById('deleteModalMessage').textContent =
                `Are you sure you want to delete ${selected.length} selected scrap material(s)? This action cannot be undone.`;
            showDeleteModal();
        }
        window.confirmBulkDelete = confirmBulkDelete;

        // =====================
        // Confirm Button — routes to correct handler
        // =====================
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (_deleteMode === 'single') {
                executeSingleDelete(this);
            } else if (_deleteMode === 'bulk') {
                executeBulkDelete(this);
            }
        });

        // =====================
        // Execute Single Delete
        // =====================
        function executeSingleDelete(btn) {
            if (!_deleteUrl) return;
            btn.disabled = true;
            btn.textContent = 'Deleting...';

            fetch(_deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Notification.success(data.message);
                        const row = document.querySelector(`tr[data-scrap-id="${_deleteId}"]`);
                        if (row) row.remove();
                        updateBulkDeleteBtn();
                    } else {
                        Notification.error(data.message);
                    }
                    hideDeleteModal();
                })
                .catch(() => {
                    Notification.error('Network error. Please try again.');
                    hideDeleteModal();
                });
        }

        // =====================
        // Execute Bulk Delete
        // =====================
        function executeBulkDelete(btn) {
            const selectedCheckboxes = [...document.querySelectorAll('.rowCheckScrap:checked')];
            const ids = selectedCheckboxes.map(c => c.value);

            if (ids.length === 0) {
                hideDeleteModal();
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Deleting...';

            fetch('{{ route('warehouse.scrap.bulk-delete') }}', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        ids: ids
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        selectedCheckboxes.forEach(checkbox => {
                            const row = checkbox.closest('tr');
                            if (row) row.remove();
                        });
                        const checkAll = document.getElementById('checkAllScrap');
                        if (checkAll) {
                            checkAll.checked = false;
                            checkAll.indeterminate = false;
                        }
                        updateBulkDeleteBtn();
                        Notification.success(data.message);
                    } else {
                        Notification.error(data.message);
                    }
                    hideDeleteModal();
                })
                .catch(() => {
                    Notification.error('Network error. Please try again.');
                    hideDeleteModal();
                });
        }
    </script>
@endpush
