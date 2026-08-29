{{-- resources/views/pages/products/warehouse/parts/list.blade.php --}}
@extends('layouts.auth')

@section('title', 'Parts List')

@section('content')
    <x-acccount.loader />
    <div class="mt-4">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="p-5 bg-white rounded-lg border border-black/10 flex flex-col sm:flex-row gap-3 justify-between items-start sm:items-center">
                        <h2 class="text-stone-950 text-xl font-normal">Parts Management</h2>
                        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end">

                            <!-- Column Chooser -->
                            <x-columns :columns="[
                                'part_number' => 'Part Number',
                                'name'        => 'Part Name',
                                'category'    => 'Category',
                                'location'    => 'Location',
                                'quantity'    => 'Quantity',
                                'status'      => 'Status',
                            ]" name="parts_columns" />

                            @if(auth()->user()->hasPermission('warehouse.parts.export-pdf') || auth()->user()->hasPermission('warehouse.parts.export-excel'))
                            <!-- Export Dropdown -->
                            <div class="relative">
                                <button onclick="toggleDropdown('exportPartsDropdown')"
                                    class="px-3 py-1.5 border border-[#0078C8] text-[#0078C8] rounded hover:bg-[#0078C8] hover:text-white text-xs font-normal whitespace-nowrap">
                                    EXPORT ▼
                                </button>
                                <div id="exportPartsDropdown"
                                    class="hidden absolute right-0 mt-1 w-36 bg-white border border-gray-200 rounded shadow-md z-10">
                                    <ul class="py-1 text-sm text-gray-700">
                                        <li>
                                            <a href="#" onclick="exportData('pdf')"
                                                class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white text-xs">PDF</a>
                                        </li>
                                        <li>
                                            <a href="#" onclick="exportData('excel')"
                                                class="block px-4 py-2 hover:bg-[#0078C8] hover:text-white text-xs">Excel</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            @endif

                            @if(auth()->user()->hasPermission('warehouse.parts.create'))
                            <!-- Add New Button -->
                            <a href="{{ route('warehouse.parts.create') }}"
                                class="px-3 py-1.5 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center gap-1.5 whitespace-nowrap">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-3.5 h-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                ADD NEW
                            </a>
                            @endif
                        </div>
                    </div>

                    @php $hasActiveFilters = request()->hasAny(['status', 'category', 'location', 'search']); @endphp
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


                        

                        <!-- Bulk Actions Bar with Stats -->
                        <div class="flex justify-between items-center px-6 py-2 border-b border-gray-100 bg-gray-50/50">
                            <div class="flex items-center gap-2">
                                @if(auth()->user()->hasPermission('warehouse.parts.bulk-delete'))
                                <button type="button" id="bulkDeleteBtn"
                                    class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-[11px] font-medium hover:bg-red-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled>
                                    DELETE SELECTED
                                </button>
                                @endif
                                <div class="h-3 w-px bg-gray-300"></div>
                                <!-- Compact Stats -->
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] text-gray-500 whitespace-nowrap">
                                        Total: <span class="font-semibold text-gray-700">{{ $stats['total_parts'] }}</span>
                                    </span>
                                    <span class="text-[11px] text-gray-500 whitespace-nowrap">
                                        Qty: <span class="font-semibold text-gray-700">{{ $stats['total_quantity'] }}</span>
                                    </span>
                                    <span class="text-[11px] text-yellow-600 whitespace-nowrap">
                                        Low: <span class="font-semibold">{{ $stats['low_stock_count'] }}</span>
                                    </span>
                                    <span class="text-[11px] text-red-600 whitespace-nowrap">
                                        Out: <span class="font-semibold">{{ $stats['out_of_stock_count'] }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Count Bar -->
                        <div class="px-6 py-2 bg-blue-50 border-b border-blue-200 hidden" id="selectedBar">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-blue-800">
                                    <span id="selectedCount">0</span> parts selected
                                </span>
                                <button type="button" id="clearSelection"
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                    Clear Selection
                                </button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div id="parts-table">
                            @include('pages.products.warehouse.parts.partials.table')
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

    {{-- Add to Product Modal --}}
    <div id="addToProductModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 transition-opacity">
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Add to Product</h3>
                            <p class="text-sm text-gray-500 mt-1" id="addToProductPartName"></p>
                        </div>
                        <form id="addToProductForm">
                            @csrf
                            <div class="space-y-4">
                                <input type="hidden" name="part_id" id="modalPartId">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Product *</label>
                                    <select name="product_id" id="selectProduct"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]">
                                        <option value="">Select product</option>
                                        @foreach(App\Models\Product::with('category')->limit(20)->get() as $product)
                                            <option value="{{ $product->id }}">
                                                {{ $product->unique_id }} - {{ $product->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Quantity *</label>
                                    <input type="number" name="quantity" id="productQuantity" value="1" min="1"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]">
                                    <p class="text-xs text-gray-500 mt-1" id="availableQuantity"></p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Notes</label>
                                    <textarea name="notes" rows="2"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#0078C8] focus:border-[#0078C8]"
                                        placeholder="Optional notes..."></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse">
                        <button type="button" onclick="addToProduct()"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#0078C8] text-sm font-medium text-white hover:bg-[#006bb0] sm:ml-3 sm:w-auto">
                            ADD TO PRODUCT
                        </button>
                        <button type="button" onclick="hideAddToProductModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto">
                            CANCEL
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete confirmation modal --}}
    <x-delete-confirmation />
@endsection

@push('styles')
<style>
    .status-badge        { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
    .status-in-stock     { background-color: #dcfce7; color: #166534; }
    .status-low-stock    { background-color: #fef9c3; color: #854d0e; }
    .status-out-of-stock { background-color: #fee2e2; color: #991b1b; }
    .status-requested    { background-color: #dbeafe; color: #1e40af; }
    .status-reserved     { background-color: #f3e8ff; color: #6b21a8; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/report-filter.js') }}"></script>
<script>
    // ========== CSRF TOKEN ==========
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // ========== BULK CHECKBOX ==========
    let checkAll        = document.getElementById('checkAllParts');
    let rowChecks       = document.querySelectorAll('.rowCheckPart');
    const selectedBar       = document.getElementById('selectedBar');
    const selectedCountSpan = document.getElementById('selectedCount');
    const clearSelectionBtn = document.getElementById('clearSelection');
    const bulkDeleteBtn     = document.getElementById('bulkDeleteBtn');

    function initializeCheckboxes() {
        checkAll  = document.getElementById('checkAllParts');
        rowChecks = document.querySelectorAll('.rowCheckPart');
        if (checkAll) {
            checkAll.removeEventListener('change', handleCheckAll);
            checkAll.addEventListener('change', handleCheckAll);
        }
        rowChecks.forEach(checkbox => {
            checkbox.removeEventListener('change', handleRowCheck);
            checkbox.addEventListener('change', handleRowCheck);
        });
    }

    function handleCheckAll(e) {
        document.querySelectorAll('.rowCheckPart').forEach(chk => chk.checked = e.target.checked);
        updateSelectedCount();
    }

    function handleRowCheck() {
        updateSelectAllCheckbox();
        updateSelectedCount();
    }

    function updateSelectAllCheckbox() {
        const checkboxes  = document.querySelectorAll('.rowCheckPart');
        const allChecked  = checkboxes.length > 0 && Array.from(checkboxes).every(c => c.checked);
        const someChecked = Array.from(checkboxes).some(c => c.checked);
        if (checkAll) {
            checkAll.checked       = allChecked;
            checkAll.indeterminate = someChecked && !allChecked;
        }
    }

    function updateSelectedCount() {
        const count = document.querySelectorAll('.rowCheckPart:checked').length;
        if (selectedCountSpan) selectedCountSpan.textContent = count;
        if (selectedBar)   selectedBar.classList.toggle('hidden', count === 0);
        if (bulkDeleteBtn) bulkDeleteBtn.toggleAttribute('disabled', count === 0);
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.rowCheckPart:checked'))
            .map(c => c.value).filter(Boolean);
    }

    clearSelectionBtn?.addEventListener('click', function () {
        document.querySelectorAll('.rowCheckPart').forEach(chk => chk.checked = false);
        if (checkAll) { checkAll.checked = false; checkAll.indeterminate = false; }
        updateSelectedCount();
    });

    // ========== TOAST ==========
    function showToast(message, type = 'success') {
        const toast   = document.getElementById('toast');
        const content = document.getElementById('toast-content');
        toast.classList.remove('hidden');
        const icon = type === 'success'
            ? '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>'
            : '<svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9l-3-3m0 0l3 3m-3-3v6m6-6v6m0 0l-3-3m3 3l-3-3" clip-rule="evenodd"/></svg>';
        content.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="mt-0.5">${icon}</div>
                <div class="flex-1 text-sm">${message}</div>
                <button class="text-gray-500 hover:text-gray-700" onclick="hideToast()">✕</button>
            </div>`;
        setTimeout(hideToast, 3000);
    }

    function hideToast() { document.getElementById('toast').classList.add('hidden'); }
    window.hideToast = hideToast;

    // ========== FILTER TOGGLE ==========
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

    // ========== DROPDOWN ==========
    function toggleDropdown(id) {
        const dropdown = document.getElementById(id);
        document.querySelectorAll('[id$="Dropdown"]').forEach(el => {
            if (el.id !== id) el.classList.add('hidden');
        });
        dropdown.classList.toggle('hidden');
    }
    window.toggleDropdown = toggleDropdown;

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.relative') && !e.target.closest('[id$="Dropdown"]')) {
            document.querySelectorAll('[id$="Dropdown"]').forEach(el => el.classList.add('hidden'));
        }
    });

    function positionAndToggleMenu(btn) {
        const menu = btn.nextElementSibling;
        const isOpen = !menu.classList.contains('hidden');
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        if (!isOpen) {
            const rect = btn.getBoundingClientRect();
            const menuW = 176; // w-44 = 11rem = 176px
            let left = rect.right - menuW;
            if (left < 8) left = rect.left;
            menu.style.top  = (rect.bottom + 4) + 'px';
            menu.style.left = left + 'px';
            menu.classList.remove('hidden');
        }
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.dropdown-btn');
        if (btn) {
            e.stopPropagation();
            positionAndToggleMenu(btn);
        }
    });

    window.addEventListener('scroll', function () {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
    }, true);

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu')) {
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
        }
    });

    // ========== DELETE ==========
    let deleteTarget       = { url: null, rowSel: null };
    const deleteModal      = document.getElementById('deleteModal');
    const cancelDelete     = document.getElementById('cancelDelete');
    const confirmDeleteBtn = document.getElementById('confirmDelete');

    function confirmDelete(btnEl) {
        deleteTarget = { url: btnEl.dataset.url, rowSel: btnEl.dataset.row };
        deleteModal?.classList.remove('hidden');
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
    }
    window.confirmDelete = confirmDelete;

    cancelDelete?.addEventListener('click', () => {
        deleteModal?.classList.add('hidden');
        deleteTarget = { url: null, rowSel: null };
    });

    confirmDeleteBtn?.addEventListener('click', async () => {
        if (!deleteTarget.url) return;
        try {
            const res  = await fetch(deleteTarget.url, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' }
            });
            const data = await res.json();
            if (res.ok && data?.success) {
                if (deleteTarget.rowSel) document.querySelector(deleteTarget.rowSel)?.remove();
                updateSelectedCount();
                showToast(data.message || 'Part deleted successfully!', 'success');
            } else {
                showToast(data?.message || 'Failed to delete part.', 'error');
            }
        } catch (e) {
            showToast('Network error. Please try again.', 'error');
        } finally {
            deleteModal?.classList.add('hidden');
            deleteTarget = { url: null, rowSel: null };
        }
    });

    deleteModal?.addEventListener('click', (e) => {
        if (e.target === deleteModal) {
            deleteModal.classList.add('hidden');
            deleteTarget = { url: null, rowSel: null };
        }
    });

    // ========== BULK DELETE ==========
    bulkDeleteBtn?.addEventListener('click', function () {
        const selectedIds = getSelectedIds();
        if (selectedIds.length === 0) { showToast('Please select parts to delete', 'error'); return; }
        if (!confirm(`Are you sure you want to delete ${selectedIds.length} parts?`)) return;

        fetch('{{ route('warehouse.parts.bulk-delete') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ ids: selectedIds })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                selectedIds.forEach(id => document.querySelector(`tr[data-part-id="${id}"]`)?.remove());
                updateSelectedCount();
            } else {
                showToast(data.message || 'Failed to delete parts', 'error');
            }
        })
        .catch(e => showToast('Delete failed: ' + e.message, 'error'));
    });

    // ========== ADD TO PRODUCT ==========
    function showAddToProductModal(partId, partName, availableQty) {
        document.getElementById('modalPartId').value                = partId;
        document.getElementById('addToProductPartName').textContent = `Add ${partName} to a product (Available: ${availableQty})`;
        document.getElementById('availableQuantity').textContent    = `Available: ${availableQty}`;
        document.getElementById('productQuantity').max              = availableQty;
        document.getElementById('productQuantity').value            = 1;
        document.getElementById('addToProductModal').classList.remove('hidden');
    }
    window.showAddToProductModal = showAddToProductModal;

    function hideAddToProductModal() {
        document.getElementById('addToProductModal').classList.add('hidden');
        document.getElementById('addToProductForm').reset();
    }
    window.hideAddToProductModal = hideAddToProductModal;

    function addToProduct() {
        const formData = new FormData(document.getElementById('addToProductForm'));
        fetch('{{ route('warehouse.parts.add-to-product') }}', {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                hideAddToProductModal();
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(data.message || 'Failed to add part to product', 'error');
            }
        })
        .catch(() => showToast('Network error. Please try again.', 'error'));
    }
    window.addToProduct = addToProduct;

    document.getElementById('addToProductModal')?.addEventListener('click', (e) => {
        if (e.target === document.getElementById('addToProductModal')) hideAddToProductModal();
    });

    // ========== EXPORT ==========
    window.exportData = function (format) {
        const params = new URLSearchParams(window.location.search);
        const url    = format === 'pdf'
            ? '{{ route('warehouse.parts.export.pdf') }}'
            : '{{ route('warehouse.parts.export.excel') }}';
        window.open(`${url}?${params.toString()}`, '_blank');
        showToast(`Exporting data as ${format.toUpperCase()}`, 'success');
    };

    // ========== SESSION MESSAGES ==========
    @if(session('success')) showToast('{{ session('success') }}', 'success'); @endif
    @if(session('error'))   showToast('{{ session('error') }}',   'error');   @endif
    @if(session('info'))    showToast('{{ session('info') }}',    'info');    @endif

    // ========== SELECT2 ==========
    $(document).ready(function () {
        $('.select2').select2({ width: '100%', allowClear: true });
    });

    // ========== INITIALIZE ==========
    document.addEventListener('DOMContentLoaded', function () {
        initializeCheckboxes();
        updateSelectedCount();
    });

    // ========== REPORT FILTER ==========
    ReportFilter.init({
        url: '{{ route('warehouse.parts.index') }}',
        tableSelector: '#parts-table',
        paginationSelector: '#pagination-wrapper',
        filterContainer: document.getElementById('filter-form'),
        debounceDelay: 250
    });
</script>
@endpush
