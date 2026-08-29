@extends('layouts.auth')

@section('title', 'Progress Sheet')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">
                        <h2 class="text-stone-950 text-xl font-normal">Progress Sheet</h2>
                        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end">

                            @if(auth()->user()->hasPermission('warehouse.progress-sheet.bulk-delete'))
                            <!-- Bulk Delete Button -->
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
                                'date'     => 'Date',
                                'name'     => 'Name',
                                'working'  => 'Working',
                                'quantity' => 'Quantity',
                                'remarks'  => 'Remarks',
                            ]" name="progress_sheet_columns" />

                            @if(auth()->user()->hasPermission('warehouse.progress-sheet.export-pdf') || auth()->user()->hasPermission('warehouse.progress-sheet.export-csv'))
                            <!-- Export Dropdown -->
                            <div class="relative">
                                <button onclick="toggleDropdown('exportDropdown')" class="dropdown-btn px-4 py-1.5 border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-semibold tracking-wide transition-colors">
                                    EXPORT ▼
                                </button>
                                <div id="exportDropdown" class="dropdown-menu hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded shadow-md z-10">
                                    <ul class="py-1 text-sm">
                                        <li>
                                            <button onclick="startExport('pdf','progress-sheet')"
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
                                            <button onclick="startExport('xlsx','progress-sheet')"
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
                            @endif

                            @if(auth()->user()->hasPermission('warehouse.progress-sheet.create'))
                            <!-- Add New Button -->
                            <a href="{{ route('warehouse.progress-sheet.create') }}"
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

                    @php $hasActiveFilters = request()->hasAny(['search', 'name', 'date']); @endphp
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

                        <!-- Filter Section -->
                        <div id="filtersSection" class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200 {{ $hasActiveFilters ? '' : 'hidden' }}">
                            <form method="GET" id="filter-form" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                                <div class="">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                                    <input type="text" name="search" placeholder="Search by name, working, remarks..."
                                        value="{{ request('search') }}"
                                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                </div>
                                <div class="">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Name</label>
                                    <select name="name"
                                        class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                        <option value="">All Employees</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->full_name }}" {{ request('name') == $emp->full_name ? 'selected' : '' }}>
                                                {{ $emp->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="">
                                    <label class="block text-xs font-medium text-gray-600 mb-2">Date</label>
                                    <input type="date" name="date" value="{{ request('date') }}"
                                        class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
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
                        <div id="ps-table">
                            @include('pages.products.warehouse.progress-sheet.partials.table')
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
            <div class="fixed inset-0 bg-black/50" id="modalBackdrop"></div>
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
                            <h3 id="deleteModalTitle" class="text-lg font-medium text-gray-900">Delete Entry</h3>
                            <p id="deleteModalMessage" class="mt-2 text-sm text-gray-500">
                                Are you sure you want to delete this entry? This action cannot be undone.
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

<script src="{{ asset('assets/js/report-filter.js') }}"></script>
@push('scripts')
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
    // Toast
    // =====================
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const content = document.getElementById('toast-content');
        const colors = { success: 'text-green-600', error: 'text-red-600', info: 'text-blue-600', warning: 'text-yellow-600' };
        const icons = {
            success: '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/>',
            error:   '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>',
            info:    '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>',
            warning: '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>',
        };
        content.innerHTML = `
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 mt-0.5 flex-shrink-0 ${colors[type]||colors.info}" fill="currentColor" viewBox="0 0 20 20">${icons[type]||icons.info}</svg>
                <div class="flex-1 text-sm">${message}</div>
                <button class="text-gray-400 hover:text-gray-600 ml-2 flex-shrink-0"
                    onclick="document.getElementById('toast').classList.add('hidden')">✕</button>
            </div>`;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 4000);
    }
    window.showToast = showToast;

    // =====================
    // Three-dots Action Dropdown
    // =====================
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.dropdown-btn');
        if (btn) {
            e.stopPropagation();
            const menu = btn.nextElementSibling;
            document.querySelectorAll('.dropdown-menu').forEach(m => { if (m !== menu) m.classList.add('hidden'); });
            menu.classList.toggle('hidden');
            return;
        }
        if (e.target.closest('.dropdown-menu')) return;
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
    });

    // =====================
    // Checkboxes & Bulk Delete Button
    // =====================
    function updateBulkDeleteBtn() {
        const selected = document.querySelectorAll('.rowCheckPS:checked').length;
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

    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'checkAllPS') {
            document.querySelectorAll('.rowCheckPS').forEach(c => c.checked = e.target.checked);
            updateBulkDeleteBtn();
            return;
        }
        if (e.target && e.target.classList.contains('rowCheckPS')) {
            const all = document.querySelectorAll('.rowCheckPS');
            const checked = document.querySelectorAll('.rowCheckPS:checked');
            const checkAll = document.getElementById('checkAllPS');
            if (checkAll) {
                checkAll.checked = all.length > 0 && all.length === checked.length;
                checkAll.indeterminate = checked.length > 0 && checked.length < all.length;
            }
            updateBulkDeleteBtn();
        }
    });

    // =====================
    // Delete State
    // =====================
    let _deleteMode = null;
    let _deleteUrl  = null;
    let _deleteId   = null;

    function showDeleteModal() {
        document.getElementById('deleteConfirmationModal').classList.remove('hidden');
    }

    function hideDeleteModal() {
        document.getElementById('deleteConfirmationModal').classList.add('hidden');
        const btn = document.getElementById('confirmDeleteBtn');
        btn.disabled = false;
        btn.textContent = 'Delete';
        _deleteMode = null; _deleteUrl = null; _deleteId = null;
    }
    window.hideDeleteModal = hideDeleteModal;

    document.getElementById('modalBackdrop').addEventListener('click', hideDeleteModal);
    document.getElementById('cancelDeleteBtn').addEventListener('click', hideDeleteModal);

    // =====================
    // Single Delete
    // =====================
    function deletePS(id, url) {
        _deleteMode = 'single';
        _deleteUrl  = url;
        _deleteId   = id;
        document.getElementById('deleteModalTitle').textContent   = 'Delete Entry';
        document.getElementById('deleteModalMessage').textContent = 'Are you sure you want to delete this entry? This action cannot be undone.';
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        showDeleteModal();
    }
    window.deletePS = deletePS;

    // =====================
    // Bulk Delete
    // =====================
    function confirmBulkDelete() {
        const selected = document.querySelectorAll('.rowCheckPS:checked');
        if (selected.length === 0) {
            showToast('Please select at least one record to delete.', 'warning');
            return;
        }
        _deleteMode = 'bulk';
        document.getElementById('deleteModalTitle').textContent   = `Delete ${selected.length} Entry(s)`;
        document.getElementById('deleteModalMessage').textContent = `Are you sure you want to delete ${selected.length} selected entry(s)? This action cannot be undone.`;
        showDeleteModal();
    }
    window.confirmBulkDelete = confirmBulkDelete;

    // =====================
    // Confirm Button
    // =====================
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (_deleteMode === 'single') executeSingleDelete(this);
        else if (_deleteMode === 'bulk') executeBulkDelete(this);
    });

    function executeSingleDelete(btn) {
        if (!_deleteUrl) return;
        btn.disabled = true;
        btn.textContent = 'Deleting...';

        fetch(_deleteUrl, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) {
                const row = document.querySelector(`tr[data-ps-id="${_deleteId}"]`);
                if (row) row.remove();
                updateBulkDeleteBtn();
            }
            hideDeleteModal();
        })
        .catch(() => { showToast('Network error. Please try again.', 'error'); hideDeleteModal(); });
    }

    function executeBulkDelete(btn) {
        const selectedCheckboxes = [...document.querySelectorAll('.rowCheckPS:checked')];
        const ids = selectedCheckboxes.map(c => c.value);
        if (ids.length === 0) { hideDeleteModal(); return; }

        btn.disabled = true;
        btn.textContent = 'Deleting...';

        fetch('{{ route('warehouse.progress-sheet.bulk-delete') }}', {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: ids })
        })
        .then(r => r.json())
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) {
                selectedCheckboxes.forEach(cb => { const row = cb.closest('tr'); if (row) row.remove(); });
                const checkAll = document.getElementById('checkAllPS');
                if (checkAll) { checkAll.checked = false; checkAll.indeterminate = false; }
                updateBulkDeleteBtn();
            }
            hideDeleteModal();
        })
        .catch(() => { showToast('Network error. Please try again.', 'error'); hideDeleteModal(); });
    }

    // =====================
    // Session Flash Messages
    // =====================
    @if(session('success'))
        showToast(@json(session('success')), 'success');
    @endif
    @if(session('error'))
        showToast(@json(session('error')), 'error');
    @endif
    @if(session('info'))
        showToast(@json(session('info')), 'info');
    @endif

    ReportFilter.init({
        url: '{{ route('warehouse.progress-sheet.index') }}',
        tableSelector: '#ps-table',
        paginationSelector: '#pagination-wrapper',
        filterContainer: document.getElementById('filter-form'),
        debounceDelay: 250
    });


</script>
@endpush
