@extends('layouts.auth')
@section('title', $sop->title)
@section('content')
<div class="mt-4 px-3">

    {{-- Header --}}
    <div class="page-header" style="align-items: center;">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="{{ route('qms.sop.index') }}" class="icon-btn" title="Back to list">
                ←
            </a>
            <div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <h1 class="page-title text-2xl" style="margin-bottom: 0;">{{ $sop->title }}</h1>
                    @php
                        $statusMap = [
                            'draft'    => ['Draft',     'qms-badge-draft'],
                            'review'   => ['Review',    'qms-badge-review'],
                            'approved' => ['Active',    'qms-badge-approved'],
                            'archived' => ['Archived',  'qms-badge-archived'],
                        ];
                        [$label, $cls] = $statusMap[$sop->status] ?? [ucfirst($sop->status), 'qms-badge-draft'];
                    @endphp
                    <span class="qms-badge {{ $cls }}">
                        {{ $label }}
                    </span>
                    <span class="inline-badge" style="background: var(--surface2); border: 1px solid var(--border); color: var(--text3);">
                        v{{ $sop->version }}
                    </span>
                </div>
                <p class="page-sub">{{ $sop->department ?? 'General' }} · {{ $sop->sop_number }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('qms.sop.edit', $sop->id) }}" class="qms-btn qms-btn-secondary">
                ✏️ Edit SOP
            </a>
            <button class="qms-btn qms-btn-primary" onclick="showToast('Execution mode started', 'info')">
                ▶️ Execute SOP
            </button>
        </div>
    </div>

    {{-- Content Layout --}}
    <div class="detail-layout">
        
        {{-- Main Column --}}
        <div>
            @php $totalSteps = $sop->steps->count(); @endphp
            
            <div class="qms-card mb-4" style="padding: 24px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px">
                    <div class="section-title text-sm font-bold uppercase tracking-wider text-gray-400" style="font-family: 'Sora', sans-serif;">Procedure Execution</div>
                    <span id="progressText" style="font-size:12px; font-weight:700; color:var(--accent)">0 / {{ $totalSteps }} Steps Completed</span>
                </div>
                
                <div class="progress-bar mb-6">
                    <div id="progressBar" class="progress-fill" style="width:0%"></div>
                </div>

                <div class="space-y-4">
                    @forelse($sop->steps->sortBy('order') as $step)
                    <div class="sop-step" id="step-item-{{ $step->id }}">
                        <div class="sop-step-header" onclick="this.classList.toggle('open'); this.nextElementSibling.classList.toggle('open')">
                            <div class="sop-step-num">{{ $step->order }}</div>
                            <div class="sop-step-title">{{ $step->title }}</div>
                            <div style="margin-left: auto; display: flex; align-items: center; gap: 12px;">
                                @if($step->has_checklist)
                                <div onclick="event.stopPropagation()">
                                    <input type="checkbox" class="step-check" 
                                           style="width: 18px; height: 18px; cursor: pointer; border-radius: 4px;"
                                           data-total="{{ $totalSteps }}" onchange="updateProgress(this)">
                                </div>
                                @endif
                                <span style="font-size:12px; color:var(--text3)">▼</span>
                            </div>
                        </div>
                        <div class="sop-step-body" style="padding-left: 54px;">
                            @if($step->description)
                                <p style="font-size:13px; color:var(--text2); line-height:1.7;">{{ $step->description }}</p>
                            @else
                                <p class="text-gray-400 italic">No instructions provided for this step.</p>
                            @endif
                            
                            @if($step->has_checklist)
                            <div style="margin-top:12px; display:flex; gap:8px">
                                <span class="inline-badge">📋 Checklist Item</span>
                                <span class="inline-badge" style="background:var(--surface2); color:var(--text3)">⏱ Manual Verification</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-10">
                        <p class="text-gray-400 italic">No procedure steps defined.</p>
                    </div>
                    @endforelse
                </div>
            </div>

            @if($sop->description)
            <div class="qms-card">
                <div class="section-title text-sm font-bold uppercase tracking-wider text-gray-400 mb-3" style="font-family: 'Sora', sans-serif;">Extended Description</div>
                <p style="font-size:14px; color:var(--text2); line-height:1.8;">{{ $sop->description }}</p>
            </div>
            @endif
        </div>

        {{-- Sidebar Column --}}
        <div class="detail-sidebar">
            <div class="detail-sidebar-card">
                <div class="card-title" style="margin-bottom:16px;">SOP Metadata</div>
                
                <div class="meta-row">
                    <span class="meta-label">SOP Number</span>
                    <span class="meta-value font-mono">{{ $sop->sop_number }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Department</span>
                    <span class="meta-value">{{ $sop->department ?? 'General' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Linked Product</span>
                    <span class="meta-value">{{ $sop->linked_product ?? 'All Products' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Version</span>
                    <span class="meta-value">v{{ $sop->version }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Created At</span>
                    <span class="meta-value">{{ $sop->created_at->format('M d, Y') }}</span>
                </div>
                @if($sop->approved_at)
                <div class="meta-row">
                    <span class="meta-label">Approved At</span>
                    <span class="meta-value text-green-600">{{ $sop->approved_at->format('M d, Y') }}</span>
                </div>
                @endif
            </div>

            <div class="qms-card" style="padding: 16px;">
                <div class="card-title" style="margin-bottom:12px;">Documents</div>
                <div class="space-y-2">
                    <button class="qms-btn qms-btn-secondary w-full justify-center" onclick="window.print()">
                        🖨 Print Procedure
                    </button>
                    <button class="qms-btn qms-btn-secondary w-full justify-center">
                        📥 Export CSV
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let completedCount = 0;
function updateProgress(chk) {
    const total = parseInt(chk.dataset.total);
    chk.checked ? completedCount++ : completedCount--;
    const pct = total > 0 ? Math.round((completedCount / total) * 100) : 0;
    document.getElementById('progressBar').style.width = pct + '%';
    document.getElementById('progressText').textContent = `${completedCount} / ${total} completed`;
    chk.closest('.border').classList.toggle('bg-green-50', chk.checked);
    chk.closest('.border').classList.toggle('border-green-200', chk.checked);
}
window.updateProgress = updateProgress;
</script>
@endpush
