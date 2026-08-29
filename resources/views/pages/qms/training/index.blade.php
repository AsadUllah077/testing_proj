@extends('layouts.auth')
@section('title', 'Training Management')
@section('content')
<x-acccount.loader />
<div class="mt-4 px-3">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-[#6378ff] mb-1">Quality Management Protocol</p>
            <div class="page-title text-3xl font-bold">Training Management</div>
            <p class="page-sub">Employee training programs linked to SOPs and policies.</p>
        </div>
        <div class="page-actions">
            <button onclick="document.getElementById('filterPanel').classList.toggle('hidden')" class="qms-btn qms-btn-secondary">
                🔍 Filters
            </button>
            <a href="{{ route('qms.training.create') }}" class="qms-btn qms-btn-primary">
                ➕ Create Training
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="qms-kpi-grid">
        <div class="qms-kpi-card">
            <div class="kpi-icon">📚</div>
            <div class="qms-kpi-label">Total Programs</div>
            <div class="qms-kpi-value">{{ $stats['total'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Assignments</div>
        </div>
        <div class="qms-kpi-card">
            <div class="kpi-icon">✅</div>
            <div class="qms-kpi-label">Completed</div>
            <div class="qms-kpi-value text-green-600">{{ $stats['completed'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Success rate</div>
        </div>
        <div class="qms-kpi-card">
            <div class="kpi-icon">⏳</div>
            <div class="qms-kpi-label">In Progress</div>
            <div class="qms-kpi-value text-amber-500">{{ $stats['in_progress'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Active learning</div>
        </div>
        <div class="qms-kpi-card">
            <div class="kpi-icon">📝</div>
            <div class="qms-kpi-label">Pending</div>
            <div class="qms-kpi-value text-gray-400">{{ $stats['pending'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Awaiting start</div>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div id="filterPanel" class="@if(!request()->hasAny(['q','status'])) hidden @endif bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-4">
        <form method="GET" id="filter-form" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="form-label">Search</label>
                <div class="filter-search">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search trainings..." class="form-control">
                </div>
            </div>
            <div class="flex-1 min-w-[130px]">
                <label class="form-label">Status</label>
                <select name="status" class="filter-select w-full">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option>
                    <option value="in_progress" {{ request('status')=='in_progress'?'selected':'' }}>In Progress</option>
                    <option value="completed" {{ request('status')=='completed'?'selected':'' }}>Completed</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="qms-btn qms-btn-primary">Apply</button>
                <a href="{{ route('qms.training.index') }}" class="qms-btn qms-btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    {{-- Table Section --}}
    <div class="qms-card" style="padding: 0; overflow: hidden;">
        <div id="training-table">
            @include('pages.qms.training.partials.table')
        </div>
    </div>
</div>

<div id="toast" class="fixed top-4 right-4 z-99999 hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
    <div id="toast-content" class="text-sm text-gray-800"></div>
</div>
<x-delete-confirmation />
@endsection

@push('scripts')
<script>
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
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    document.getElementById('toast-content').innerHTML = `<div class="flex gap-2 items-start"><span class="text-sm flex-1">${msg}</span><button onclick="this.closest('#toast').classList.add('hidden')" class="text-gray-400">✕</button></div>`;
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 4000);
}
window.showToast = showToast;
let deleteTarget = { url: null, rowSel: null };
const deleteModal = document.getElementById('deleteModal');
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
function confirmDelete(el) { deleteTarget = { url: el.dataset.url, rowSel: el.dataset.row }; deleteModal?.classList.remove('hidden'); }
window.confirmDelete = confirmDelete;
document.getElementById('cancelDelete')?.addEventListener('click', () => deleteModal?.classList.add('hidden'));
document.getElementById('confirmDelete')?.addEventListener('click', async () => {
    if (!deleteTarget.url) return;
    const res = await fetch(deleteTarget.url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
    const data = await res.json();
    if (res.ok && data?.success) { document.querySelector(deleteTarget.rowSel)?.remove(); showToast(data.message, 'success'); }
    else showToast(data?.message || 'Failed.', 'error');
    deleteModal?.classList.add('hidden'); deleteTarget = { url: null, rowSel: null };
});
@if(session('success')) showToast('{{ session("success") }}', 'success'); @endif
@if(session('error'))   showToast('{{ session("error") }}', 'error'); @endif
</script>
@endpush
