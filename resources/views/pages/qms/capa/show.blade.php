@extends('layouts.auth')
@section('title', 'CAPA Details: ' . $capa->capa_number)
@section('content')
<div class="mt-4 px-3">
    {{-- Header --}}
    <div class="page-header" style="align-items: center;">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="{{ route('qms.capa.index') }}" class="icon-btn" title="Back to list">
                ←
            </a>
            <div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <h1 class="page-title text-2xl" style="margin-bottom: 0;">{{ $capa->capa_number }}</h1>
                    @php
                        $statusMap = [
                            'open'        => ['Open',        'qms-badge-draft'],
                            'in_progress' => ['In Progress', 'qms-badge-review'],
                            'closed'      => ['Closed',      'qms-badge-approved'],
                        ];
                        [$label, $cls] = $statusMap[$capa->status] ?? [ucfirst($capa->status), 'qms-badge-draft'];
                    @endphp
                    <span class="qms-badge {{ $cls }}">
                        {{ $label }}
                    </span>
                    <span class="inline-badge" style="background: var(--surface2); border: 1px solid var(--border); color: var(--text3);">
                        Priority: {{ $capa->priority ?? 'Medium' }}
                    </span>
                </div>
                <p class="page-sub">{{ $capa->title }} · Opened on {{ $capa->created_at->format('M d, Y') }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('qms.capa.edit', $capa->id) }}" class="qms-btn qms-btn-secondary">
                ✏️ Edit Action
            </a>
            @if($capa->status !== 'closed')
            <button class="qms-btn qms-btn-primary" onclick="updateStatus('closed')">
                🔒 Close CAPA
            </button>
            @endif
        </div>
    </div>

    {{-- Content Layout --}}
    <div class="detail-layout">
        
        {{-- Main Column --}}
        <div>
            <div class="qms-card mb-4" style="padding: 24px;">
                <div class="section-title text-sm font-bold uppercase tracking-wider text-gray-400 mb-4" style="font-family: 'Sora', sans-serif;">Issue Description</div>
                <div class="prose max-w-none text-gray-800 leading-relaxed mb-6">
                    {!! nl2br(e($capa->description)) ?: '<p class="text-gray-400 italic">No description provided.</p>' !!}
                </div>

                <div class="divider"></div>

                <div class="section-title text-sm font-bold uppercase tracking-wider text-gray-400 mb-4" style="font-family: 'Sora', sans-serif;">Root Cause Analysis</div>
                <div style="background: var(--surface2); border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                    <p style="font-size: 14px; color: var(--text2); line-height: 1.7;">
                        {{ $capa->root_cause ?: 'Root cause analysis has not been documented for this issue yet.' }}
                    </p>
                </div>

                @if($capa->resolution_notes)
                <div class="section-title text-sm font-bold uppercase tracking-wider text-gray-400 mb-4" style="font-family: 'Sora', sans-serif;">Resolution & Corrective Actions</div>
                <div style="background: var(--approved-bg); border: 1px solid rgba(5,150,105,0.2); border-radius: 12px; padding: 16px;">
                    <p style="font-size: 14px; color: var(--text); line-height: 1.7;">
                        {{ $capa->resolution_notes }}
                    </p>
                    @if($capa->closed_at)
                    <div style="margin-top: 12px; font-size: 12px; color: #059669; font-weight: 600;">
                        ✓ Closed on {{ \Carbon\Carbon::parse($capa->closed_at)->format('M d, Y') }}
                    </div>
                    @endif
                </div>
                @endif
            </div>

            <div class="qms-card">
                <div class="section-title text-sm font-bold uppercase tracking-wider text-gray-400 mb-3" style="font-family: 'Sora', sans-serif;">Timeline & History</div>
                <div class="timeline" style="margin-top: 20px;">
                    @if($capa->closed_at)
                    <div class="timeline-item done">
                        <div class="tl-time">{{ \Carbon\Carbon::parse($capa->closed_at)->format('M d, Y') }}</div>
                        <div class="tl-title">CAPA Closed</div>
                        <div class="tl-sub">The issue was verified and the corrective action was completed.</div>
                    </div>
                    @endif
                    <div class="timeline-item done">
                        <div class="tl-time">{{ $capa->updated_at->format('M d, Y') }}</div>
                        <div class="tl-title">Last Activity</div>
                        <div class="tl-sub">Status updated to {{ ucfirst(str_replace('_', ' ', $capa->status)) }}.</div>
                    </div>
                    <div class="timeline-item done">
                        <div class="tl-time">{{ $capa->created_at->format('M d, Y') }}</div>
                        <div class="tl-title">CAPA Initiated</div>
                        <div class="tl-sub">Issue reported by {{ $capa->creator->name ?? 'System' }}.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar Column --}}
        <div class="detail-sidebar">
            <div class="detail-sidebar-card">
                <div class="card-title" style="margin-bottom:16px;">Management Information</div>
                
                <div class="meta-row">
                    <span class="meta-label">Assignee</span>
                    <span class="meta-value">
                        <div style="display:flex; align-items:center; gap:8px; justify-content:flex-end;">
                            <div style="width:24px; height:24px; border-radius:50%; background:var(--accent-light); color:var(--accent); display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700;">
                                {{ strtoupper(substr($capa->assignee->name ?? 'U', 0, 1)) }}
                            </div>
                            {{ $capa->assignee->name ?? 'Unassigned' }}
                        </div>
                    </span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Linked SOP</span>
                    <span class="meta-value">{{ $capa->sop->title ?? 'None' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Product/Lot</span>
                    <span class="meta-value">{{ $capa->linked_product ?? 'N/A' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Deadline</span>
                    <span class="meta-value {{ $capa->deadline && \Carbon\Carbon::parse($capa->deadline)->isPast() ? 'text-red-600' : 'text-blue-600' }}">
                        {{ $capa->deadline ? \Carbon\Carbon::parse($capa->deadline)->format('M d, Y') : 'No deadline' }}
                    </span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Effectiveness</span>
                    <span class="meta-value">Pending Verification</span>
                </div>
            </div>

            <div class="qms-card" style="padding: 16px;">
                <div class="card-title" style="margin-bottom:12px;">Quick Actions</div>
                <button class="qms-btn qms-btn-secondary w-full justify-center mb-2" onclick="window.print()">
                    🖨 Print CAPA Report
                </button>
                <button class="qms-btn qms-btn-secondary w-full justify-center">
                    📧 Email Stakeholders
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    async function updateStatus(newStatus) {
        if(!confirm(`Close this CAPA and mark as resolved?`)) return;
        
        try {
            const res = await fetch('{{ route("qms.capa.update", $capa->id) }}', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    title: '{{ $capa->title }}',
                    status: newStatus
                })
            });
            const data = await res.json();
            if(data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Error updating status');
            }
        } catch (e) {
            alert('Network error');
        }
    }
</script>
@endpush
@endsection
