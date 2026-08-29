@extends('layouts.auth')
@section('title', $training->title)
@section('content')
<div class="mt-4 px-3">
    {{-- Header --}}
    <div class="page-header" style="align-items: center;">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="{{ route('qms.training.index') }}" class="icon-btn" title="Back to list">
                ←
            </a>
            <div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <h1 class="page-title text-2xl" style="margin-bottom: 0;">{{ $training->title }}</h1>
                    @php
                        $statusMap = [
                            'pending'     => ['Scheduled',   'qms-badge-draft'],
                            'in_progress' => ['In Progress', 'qms-badge-review'],
                            'completed'   => ['Awarded',     'qms-badge-approved'],
                        ];
                        [$label, $cls] = $statusMap[$training->status] ?? [ucfirst($training->status), 'qms-badge-draft'];
                    @endphp
                    <span class="qms-badge {{ $cls }}">
                        {{ $label }}
                    </span>
                    @if($training->certification_enabled)
                    <span class="qms-badge qms-badge-approved" style="background: rgba(167,139,250,0.12); color: #a78bfa; border: none;">
                        🎓 Certification
                    </span>
                    @endif
                </div>
                <p class="page-sub">
                    @if($training->sop)
                        Linked SOP: {{ $training->sop->title }}
                    @elseif($training->policy)
                        Linked Policy: {{ $training->policy->title }}
                    @else
                        General Training
                    @endif
                </p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('qms.training.edit', $training->id) }}" class="qms-btn qms-btn-secondary">
                ✏️ Edit Session
            </a>
            <button class="qms-btn qms-btn-primary" onclick="showToast('Exporting results...', 'info')">
                📥 Export Results
            </button>
        </div>
    </div>

    {{-- Content Layout --}}
    <div class="detail-layout">
        
        {{-- Main Column --}}
        <div>
            <div class="qms-card mb-4" style="padding: 24px;">
                <div class="section-title text-sm font-bold uppercase tracking-wider text-gray-400 mb-4" style="font-family: 'Sora', sans-serif;">Training Overview</div>
                <div class="prose max-w-none text-gray-800 leading-relaxed mb-6">
                    {!! nl2br(e($training->description)) ?: '<p class="text-gray-400 italic">No description provided for this session.</p>' !!}
                </div>

                <div class="section-title text-sm font-bold uppercase tracking-wider text-gray-400 mb-4" style="font-family: 'Sora', sans-serif;">Assigned Employees ({{ $training->employees->count() }})</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse($training->employees as $te)
                    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 12px; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">
                            {{ strtoupper(substr($te->employee->name ?? 'U', 0, 1)) }}
                        </div>
                        <div style="flex: 1;">
                            <p style="font-size: 13px; font-weight: 600; color: var(--text);">{{ $te->employee->name ?? 'Unknown Employee' }}</p>
                            <p style="font-size: 11px; color: var(--text3);">{{ $te->employee->emp_id ?? $te->id }}</p>
                        </div>
                        <span class="qms-badge {{ $te->status == 'completed' ? 'qms-badge-approved' : 'qms-badge-draft' }}">
                            {{ ucfirst($te->status ?? 'pending') }}
                        </span>
                    </div>
                    @empty
                    <div class="col-span-2 text-center py-6">
                        <p class="text-gray-400 italic">No employees assigned to this training.</p>
                    </div>
                    @endforelse
                </div>
            </div>

            @if($training->has_assessment)
            <div class="qms-card">
                <div class="section-title text-sm font-bold uppercase tracking-wider text-gray-400 mb-3" style="font-family: 'Sora', sans-serif;">Assessment Criteria</div>
                <div style="background: var(--surface2); border-radius: 12px; padding: 16px; border: 1px dashed var(--border);">
                    <p style="font-size: 13px; color: var(--text2); line-height: 1.6;">{{ $training->certification_criteria ?: 'Assessors will verify competence against the linked SOP/Policy steps.' }}</p>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar Column --}}
        <div class="detail-sidebar">
            <div class="detail-sidebar-card">
                <div class="card-title" style="margin-bottom:16px;">Session Info</div>
                
                <div class="meta-row">
                    <span class="meta-label">Type</span>
                    <span class="meta-value">{{ $training->sop ? 'SOP Based' : ($training->policy ? 'Policy Based' : 'General') }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Assessment</span>
                    <span class="meta-value">{{ $training->has_assessment ? 'Yes' : 'No' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Total Assigned</span>
                    <span class="meta-value">{{ $training->employees->count() }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Created By</span>
                    <span class="meta-value">{{ $training->creator->name ?? 'System' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Date</span>
                    <span class="meta-value">{{ $training->created_at->format('M d, Y') }}</span>
                </div>
            </div>

            <div class="qms-card" style="padding: 16px;">
                <div class="card-title" style="margin-bottom:12px;">Certificates</div>
                <p style="font-size: 12px; color: var(--text3); margin-bottom: 12px;">Issue certificates to all employees who completed the training and assessment.</p>
                <button class="qms-btn qms-btn-primary w-full justify-center {{ $training->status == 'completed' ? '' : 'opacity-50 cursor-not-allowed' }}" {{ $training->status == 'completed' ? '' : 'disabled' }}>
                    🏅 Award Certificates
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
