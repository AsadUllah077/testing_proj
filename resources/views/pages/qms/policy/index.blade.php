@extends('layouts.auth')
@section('title', 'Policies Management')
@section('content')
<x-acccount.loader />
<div class="mt-4 px-3">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-[#6378ff] mb-1">Quality Management Protocol</p>
            <div class="page-title text-3xl font-bold">Policies Management</div>
            <p class="page-sub">Centralized directory of organizational standards and regulatory compliance requirements.</p>
        </div>
        <div class="page-actions">
            <button onclick="toggleFilter()" class="qms-btn qms-btn-secondary">
                🔍 Filters
            </button>
            <a href="{{ route('qms.policy.create') }}" class="qms-btn qms-btn-primary">
                ➕ Create New Policy
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="qms-kpi-grid">
        <div class="qms-kpi-card">
            <div class="kpi-icon">📋</div>
            <div class="qms-kpi-label">Total Active</div>
            <div class="qms-kpi-value">{{ $stats['active'] }}</div>
            <div class="text-xs text-gray-400 mt-1">All policies</div>
        </div>
        <div class="qms-kpi-card">
            <div class="kpi-icon">📝</div>
            <div class="qms-kpi-label">Under Review</div>
            <div class="qms-kpi-value text-orange-500">{{ $stats['under_review'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Awaiting board</div>
        </div>
        <div class="qms-kpi-card">
            <div class="kpi-icon">✍️</div>
            <div class="qms-kpi-label">Draft</div>
            <div class="qms-kpi-value text-gray-400">{{ $stats['draft'] }}</div>
            <div class="text-xs text-gray-400 mt-1">In progress</div>
        </div>
        <div class="qms-kpi-card">
            <div class="kpi-icon">📦</div>
            <div class="qms-kpi-label">Archived</div>
            <div class="qms-kpi-value text-red-500">{{ $stats['archived'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Historical</div>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div id="filterPanel" class="@if(!request()->hasAny(['q','status','category','priority','from_date','to_date'])) hidden @endif bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-4">
        <form method="GET" id="filter-form" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="form-label">Search</label>
                <div class="filter-search">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search policies..." class="form-control">
                </div>
            </div>
            <div class="flex-1 min-w-[130px]">
                <label class="form-label">Category</label>
                <select name="category" class="filter-select w-full">
                    <option value="">All Categories</option>
                    @foreach(['QA','Sales','Customer Service','Supplier','HR','Finance','IT','Legal'] as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[130px]">
                <label class="form-label">Status</label>
                <select name="status" class="filter-select w-full">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status')=='draft'?'selected':'' }}>Draft</option>
                    <option value="active" {{ request('status')=='active'?'selected':'' }}>Approved</option>
                    <option value="under_review" {{ request('status')=='under_review'?'selected':'' }}>Review</option>
                    <option value="archived" {{ request('status')=='archived'?'selected':'' }}>Archived</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="qms-btn qms-btn-primary">Apply</button>
                <a href="{{ route('qms.policy.index') }}" class="qms-btn qms-btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    {{-- Table Section --}}
    <div class="qms-card" style="padding: 0; overflow: hidden;">
        <div id="policy-table">
            @include('pages.qms.policy.partials.table')
        </div>
    </div>
</div>
</div>

<div id="toast" class="fixed top-4 right-4 z-99999 hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
    <div id="toast-content" class="text-sm text-gray-800"></div>
</div>
<x-delete-confirmation />
@endsection

<script src="{{ asset('assets/js/report-filter.js') }}"></script>
@push('scripts')
<script>
function toggleFilter() {
    document.getElementById('filterPanel').classList.toggle('hidden');
}

// Bulk selection
let selectedIds = new Set();
function updateBulkBar() {
    const count = selectedIds.size;
    document.getElementById('selectedCount').textContent = count;
    const bar = document.getElementById('bulkBar');
    bar.classList.toggle('hidden', count === 0);
    bar.classList.toggle('flex', count > 0);
    document.getElementById('bulkToggleBtn').classList.toggle('hidden', count === 0);
}

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('row-check')) {
        const id = e.target.dataset.id;
        e.target.checked ? selectedIds.add(id) : selectedIds.delete(id);
        updateBulkBar();
    }
    if (e.target.id === 'checkAll') {
        document.querySelectorAll('.row-check').forEach(c => {
            c.checked = e.target.checked;
            e.target.checked ? selectedIds.add(c.dataset.id) : selectedIds.delete(c.dataset.id);
        });
        updateBulkBar();
    }
});

function clearSelection() {
    selectedIds.clear();
    document.querySelectorAll('.row-check, #checkAll').forEach(c => c.checked = false);
    updateBulkBar();
}
window.clearSelection = clearSelection;

async function bulkArchive() {
    if (selectedIds.size === 0) return;
    if (!confirm(`Archive ${selectedIds.size} selected policies?`)) return;
    // Placeholder - implement bulk archive endpoint as needed
    showToast('Bulk archive is not yet connected to an endpoint.', 'info');
}
window.bulkArchive = bulkArchive;

// Dropdown
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.dropdown-btn');
    if (btn) {
        e.stopPropagation();
        const menu = btn.nextElementSibling;
        document.querySelectorAll('.dropdown-menu').forEach(m => { if (m !== menu) m.classList.add('hidden'); });
        menu.classList.toggle('hidden');
    }
});
document.addEventListener('click', e => {
    if (!e.target.closest('.dropdown-btn') && !e.target.closest('.dropdown-menu'))
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
});

function showToast(message, type = 'success') {
    const icons = {
        success: '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15l-4.121-4.121a1 1 0 111.414-1.414L8.414 12.172l7.879-7.879a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>',
        error:   '<svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>',
        info:    '<svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>',
    };
    const toast = document.getElementById('toast');
    document.getElementById('toast-content').innerHTML = `<div class="flex items-start gap-3">${icons[type]||icons.info}<div class="flex-1">${message}</div><button onclick="this.closest('#toast').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button></div>`;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 4000);
}
window.showToast = showToast;

// Delete
let deleteTarget = { url: null, rowSel: null };
const deleteModal = document.getElementById('deleteModal');
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

function confirmDelete(btnEl) {
    deleteTarget = { url: btnEl.dataset.url, rowSel: btnEl.dataset.row };
    deleteModal?.classList.remove('hidden');
    document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.add('hidden'));
}
window.confirmDelete = confirmDelete;

document.getElementById('cancelDelete')?.addEventListener('click', () => { deleteModal?.classList.add('hidden'); });

document.getElementById('confirmDelete')?.addEventListener('click', async () => {
    if (!deleteTarget.url) return;
    try {
        const res = await fetch(deleteTarget.url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
        const data = await res.json();
        if (res.ok && data?.success) {
            document.querySelector(deleteTarget.rowSel)?.remove();
            showToast(data.message || 'Deleted successfully!', 'success');
        } else { showToast(data?.message || 'Failed to delete.', 'error'); }
    } catch { showToast('Network error.', 'error'); }
    finally { deleteModal?.classList.add('hidden'); deleteTarget = { url: null, rowSel: null }; }
});

@if(session('success')) showToast('{{ session("success") }}', 'success'); @endif
@if(session('error'))   showToast('{{ session("error") }}', 'error'); @endif

ReportFilter.init({
    url: '{{ route("qms.policy.index") }}',
    tableSelector: '#policy-table',
    paginationSelector: '#pagination-wrapper',
    filterContainer: document.getElementById('filter-form'),
    debounceDelay: 300
});
</script>
@endpush
