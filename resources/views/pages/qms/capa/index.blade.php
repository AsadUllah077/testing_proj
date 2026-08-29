@extends('layouts.auth')
@section('title', 'CAPA System')
@section('content')
<x-acccount.loader />
<div class="mt-4 px-3">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-[#6378ff] mb-1">Quality Management Protocol</p>
            <div class="page-title text-3xl font-bold">CAPA System</div>
            <p class="page-sub">Corrective & Preventive Action tracking for issue resolution.</p>
        </div>
        <div class="page-actions">
            {{-- View toggle --}}
            <div style="display: flex; background: var(--surface2); border-radius: 12px; padding: 4px; gap: 4px; border: 1px solid var(--border);">
                <button onclick="setView('table')" id="btn-table"
                    class="qms-btn qms-btn-secondary" style="padding: 6px 16px; border: none; font-size: 12px; background: white; color: var(--text);">
                    Table
                </button>
                <button onclick="setView('kanban')" id="btn-kanban"
                     class="qms-btn qms-btn-secondary" style="padding: 6px 16px; border: none; font-size: 12px; background: transparent; color: var(--text3);">
                    Kanban
                </button>
            </div>

            <button onclick="document.getElementById('filterPanel').classList.toggle('hidden')" class="qms-btn qms-btn-secondary">
                🔍 Filters
            </button>
            <a href="{{ route('qms.capa.create') }}" class="qms-btn qms-btn-primary">
                ➕ Create CAPA
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="qms-kpi-grid">
        <div class="qms-kpi-card">
            <div class="kpi-icon">📝</div>
            <div class="qms-kpi-label">Open Issues</div>
            <div class="qms-kpi-value text-red-500">{{ $stats['open'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Requiring action</div>
        </div>
        <div class="qms-kpi-card">
            <div class="kpi-icon">⚡</div>
            <div class="qms-kpi-label">In Progress</div>
            <div class="qms-kpi-value text-blue-500">{{ $stats['in_progress'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Under resolution</div>
        </div>
        <div class="qms-kpi-card">
            <div class="kpi-icon">✅</div>
            <div class="qms-kpi-label">Closed</div>
            <div class="qms-kpi-value text-green-600">{{ $stats['closed'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Resolved cases</div>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div id="filterPanel" class="@if(!request()->hasAny(['q','status'])) hidden @endif bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-4">
        <form method="GET" id="filter-form" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="form-label">Search</label>
                <div class="filter-search">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search CAPAs..." class="form-control">
                </div>
            </div>
            <div class="flex-1 min-w-[130px]">
                <label class="form-label">Status</label>
                <select name="status" class="filter-select w-full">
                    <option value="">All Statuses</option>
                    <option value="open" {{ request('status')=='open'?'selected':'' }}>Open</option>
                    <option value="in_progress" {{ request('status')=='in_progress'?'selected':'' }}>In Progress</option>
                    <option value="closed" {{ request('status')=='closed'?'selected':'' }}>Closed</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="qms-btn qms-btn-primary">Apply</button>
                <a href="{{ route('qms.capa.index') }}" class="qms-btn qms-btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    {{-- Table View --}}
    <div id="view-table" class="qms-card" style="padding: 0; overflow: hidden;">
        <div id="capa-table">
            @include('pages.qms.capa.partials.table')
        </div>
    </div>

    {{-- Kanban View --}}
    <div id="view-kanban" class="hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach(['open'=>['Open','var(--open)','var(--open-bg)'],'in_progress'=>['In Progress','var(--inprogress)','var(--inprogress-bg)'],'closed'=>['Closed','var(--closed)','var(--closed-bg)']] as $status => [$label,$color,$bg])
            <div class="qms-card" style="padding: 0; background: var(--surface2); border: none;">
                <div style="padding: 16px; border-bottom: 2px solid {{ $color }}; display: flex; align-items: center; justify-content: space-between; background: white; border-radius: 16px 16px 0 0;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-weight: 700; color: var(--text); font-size: 14px;">{{ $label }}</span>
                        <span style="background: {{ $bg }}; color: {{ $color }}; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 20px;">
                            {{ $stats[$status] }}
                        </span>
                    </div>
                </div>
                <div style="padding: 12px; display: flex; flex-direction: column; gap: 12px; min-height: 400px;">
                    @foreach($capas->where('status', $status) as $capa)
                    <div style="background: white; border-radius: 12px; border: 1px solid var(--border); padding: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: all 0.2s;" class="kanban-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <p style="font-weight: 600; color: var(--text); font-size: 14px; line-height: 1.4;">{{ $capa->title }}</p>
                            <a href="{{ route('qms.capa.edit', $capa->id) }}" style="color: var(--text3); hover: color: var(--accent);">
                                ✏️
                            </a>
                        </div>
                        <p style="font-size: 11px; color: var(--text3); margin-bottom: 10px; font-family: monospace;">{{ $capa->capa_number }}</p>
                        
                        @if($capa->linked_product)
                            <div style="margin-bottom: 10px;">
                                <span style="font-size: 11px; background: var(--surface2); color: var(--text2); padding: 2px 8px; border-radius: 4px; border: 1px solid var(--border);">
                                    📦 {{ $capa->linked_product }}
                                </span>
                            </div>
                        @endif

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--border);">
                            @if($capa->assignee)
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <div style="width: 20px; height: 20px; border-radius: 50%; background: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 700;">
                                        {{ strtoupper(substr($capa->assignee->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <span style="font-size: 11px; color: var(--text2); font-weight: 500;">{{ Str::limit($capa->assignee->name, 10) }}</span>
                                </div>
                            @endif

                            @if($capa->deadline)
                                <div style="font-size: 11px; {{ $capa->deadline->isPast() && $capa->status !== 'closed' ? 'color: #dc2626; font-weight: 600;' : 'color: var(--text3);' }}">
                                    📅 {{ $capa->deadline->format('M d') }}
                                </div>
                            @endif
                        </div>

                        {{-- Quick status change --}}
                        <div style="display: flex; gap: 4px; margin-top: 12px;">
                            @if($status !== 'in_progress')
                            <button onclick="quickStatus({{ $capa->id }}, 'in_progress')"
                                style="flex: 1; font-size: 10px; padding: 6px; border-radius: 6px; border: 1px solid var(--inprogress-bg); background: var(--inprogress-bg); color: var(--inprogress); font-weight: 600; cursor: pointer;">
                                In Progress
                            </button>
                            @endif
                            @if($status !== 'closed')
                            <button onclick="quickStatus({{ $capa->id }}, 'closed')"
                                style="flex: 1; font-size: 10px; padding: 6px; border-radius: 6px; border: 1px solid var(--closed-bg); background: var(--closed-bg); color: var(--closed); font-weight: 600; cursor: pointer;">
                                Close Action
                            </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
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
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// View toggle
function setView(v) {
    document.getElementById('view-table').classList.toggle('hidden', v !== 'table');
    document.getElementById('view-kanban').classList.toggle('hidden', v !== 'kanban');
    document.getElementById('btn-table').className = v === 'table'
        ? 'px-3 py-1.5 rounded-md text-xs font-medium transition-all bg-white text-gray-700 shadow-sm'
        : 'px-3 py-1.5 rounded-md text-xs font-medium transition-all text-gray-500';
    document.getElementById('btn-kanban').className = v === 'kanban'
        ? 'px-3 py-1.5 rounded-md text-xs font-medium transition-all bg-white text-gray-700 shadow-sm'
        : 'px-3 py-1.5 rounded-md text-xs font-medium transition-all text-gray-500';
}
window.setView = setView;

// Quick status update
async function quickStatus(id, status) {
    try {
        const res = await fetch(`/qms/capa/${id}/status`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ status })
        });
        const data = await res.json();
        if (data.success) { showToast(data.message, 'success'); setTimeout(() => location.reload(), 1000); }
        else showToast(data.message, 'error');
    } catch { showToast('Network error.', 'error'); }
}
window.quickStatus = quickStatus;

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

function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    document.getElementById('toast-content').innerHTML = `<div class="flex gap-2 items-start"><span class="text-sm flex-1">${msg}</span><button onclick="this.closest('#toast').classList.add('hidden')" class="text-gray-400">✕</button></div>`;
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 4000);
}
window.showToast = showToast;

let deleteTarget = { url: null, rowSel: null };
const deleteModal = document.getElementById('deleteModal');
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
