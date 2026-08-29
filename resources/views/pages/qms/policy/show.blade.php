@extends('layouts.auth')
@section('title', $policy->title)
@section('content')
<div class="mt-4 px-3">
    {{-- Header --}}
    <div class="page-header" style="align-items: center;">
        <div style="display:flex; align-items:center; gap:16px;">
            <a href="{{ route('qms.policy.index') }}" class="icon-btn" title="Back to list">
                ←
            </a>
            <div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <h1 class="page-title text-2xl font-bold tracking-tight" style="margin-bottom: 0;">{{ $policy->title }}</h1>
                    <span class="qms-badge qms-badge-{{ $policy->status }}">
                        {{ ucfirst($policy->status) }}
                    </span>
                    <span class="text-[11px] font-bold text-gray-400 bg-gray-50 border border-gray-100 px-2 py-0.5 rounded uppercase tracking-wider">
                        v{{ $policy->version ?: '1.0' }}
                    </span>
                </div>
                <p class="page-sub text-xs mt-1">
                    {{ $policy->policy_number }} · Department: {{ $policy->department ?: 'General' }} · Last updated {{ $policy->updated_at->format('M d, Y') }}
                </p>
            </div>
        </div>
        <div class="page-actions flex items-center gap-3">
            @if($policy->status === 'draft')
            <a href="{{ route('qms.policy.edit', $policy->id) }}" class="qms-btn qms-btn-secondary !shadow-none hover:bg-gray-50">
                ✏️ Edit Policy
            </a>
            <button class="qms-btn qms-btn-primary shadow-lg shadow-indigo-100" onclick="workflowAction('submit-review')">
                Submit for Review →
            </button>
            @elseif($policy->status === 'under_review')
            {{-- Only v1.0 reaches under_review --}}
            <button class="qms-btn qms-btn-secondary !shadow-none hover:bg-red-50 !text-red-600 !border-red-200" onclick="openRejectModal('qa')">
                ✗ Reject
            </button>
            <button class="qms-btn qms-btn-primary shadow-lg shadow-indigo-100" onclick="workflowAction('qa-approve')">
                ✓ QA Approve
            </button>
            @elseif($policy->status === 'pending_approval')
            <button class="qms-btn qms-btn-secondary !shadow-none hover:bg-red-50 !text-red-600 !border-red-200" onclick="openRejectModal('final')">
                ✗ Reject
            </button>
            <button class="qms-btn qms-btn-primary shadow-lg shadow-indigo-100" onclick="workflowAction('final-approve')">
                ✓ Approve v{{ $policy->version }}
            </button>
            @elseif($policy->status === 'active')
            <a href="{{ route('qms.policy.edit', $policy->id) }}" class="qms-btn qms-btn-secondary !shadow-none hover:bg-gray-50">
                ✏️ Edit Policy
            </a>
            @endif
        </div>
    </div>

    {{-- Content Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        
        {{-- Main Column --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="qms-card">
                <div class="flex border-b border-gray-100 mb-6">
                    <button class="qms-tab active" onclick="switchTab(this, 'overview')">Overview</button>
                    <button class="qms-tab" onclick="switchTab(this, 'history')">Version History</button>
                    <button class="qms-tab" onclick="switchTab(this, 'workflow')">Approval Workflow</button>
                    <button class="qms-tab" onclick="switchTab(this, 'attachments')">Attachments ({{ $policy->attachments->count() }}) </button>
                </div>

                {{-- Overview Tab --}}
                <div id="tab-overview" class="tab-content transition-all duration-300">
                    <div class="prose max-w-none text-gray-700 leading-relaxed font-medium">
                        {!! $policy->content ?: '<p class="text-gray-400 italic">No content provided for this policy.</p>' !!}
                    </div>
                </div>

                {{-- History Tab (Styled like Image) --}}
                <div id="tab-history" class="tab-content hidden space-y-8 py-2">
                    <div class="relative before:absolute before:left-3 before:top-2 before:bottom-2 before:w-0.5 before:bg-indigo-50">
                        @forelse($logs as $log)
                            @php
                                $isCreation = $log->action === 'created';
                                $isApproval = isset($log->changes['new']['status']) && $log->changes['new']['status'] === 'active';
                                $hasContentChange = isset($log->changes['new']['content']) || $isCreation;
                                $version = $log->changes['new']['version'] ?? ($isCreation ? '1.0' : null);
                            @endphp
                            @if($hasContentChange)
                            <div class="flex gap-6 pb-10 relative">
                                <div class="w-6 h-6 rounded-full bg-white border-4 border-indigo-400 z-10 shadow-sm transition-transform hover:scale-110"></div>
                                <div class="flex-1 -mt-1">
                                    <div class="text-[11px] font-bold text-gray-400 mb-1">{{ $log->created_at->format('M d, Y · h:i A') }}</div>
                                    <div class="text-base font-bold text-gray-900 mb-1">
                                        @if($version) v{{ $version }} — @endif
                                        Major Revision
                                    </div>
                                    <div class="text-sm text-gray-500 leading-snug">
                                        {{ $isCreation ? 'Initial policy creation and classification.' : 'Policy content updated.' }}
                                        Performed by <span class="text-indigo-600 font-bold">{{ $log->user->full_name ?: 'System' }}</span>.
                                    </div>
                                </div>
                            </div>
                            @endif
                        @empty
                            <p class="text-center py-10 text-gray-400 italic">No history logs recorded.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Workflow Tab --}}
                <div id="tab-workflow" class="tab-content hidden space-y-4 py-2">

                    @php
                        $wfOrder  = ['draft', 'under_review', 'pending_approval', 'active'];
                        $wfCurrent = $policy->status === 'archived' ? 'active' : $policy->status;
                        $wfStep   = array_search($wfCurrent, $wfOrder);
                    @endphp

                    {{-- ── REVISION MODE (v2.0+): show approval history + 2-step flow ── --}}
                    @if($isRevision)

                        {{-- Approval History --}}
                        @if($approvalHistory->count())
                        <div class="mb-2">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Approval History</p>
                            <div class="space-y-2">
                                @foreach($approvalHistory as $record)
                                <div class="flex items-center gap-3 p-3 bg-indigo-50 border border-indigo-100 rounded-xl">
                                    <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-bold text-gray-900">Final Approved — v{{ $record->version }}</p>
                                        <p class="text-xs text-indigo-600/70 font-medium">
                                            {{ $record->approver->full_name ?? 'System' }} · {{ $record->approved_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <span class="text-[10px] font-bold text-indigo-600 bg-white border border-indigo-200 px-2.5 py-1 rounded-full uppercase">
                                        v{{ $record->version }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="border-l-2 border-dashed border-gray-200 ml-4 pl-4 pb-1">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Current cycle — v{{ $policy->version }}</p>
                        </div>
                        @endif

                        {{-- 2-step flow: Author → Final Approval --}}
                        @php
                            $revSteps = [
                                [
                                    'label'    => 'Document Author',
                                    'sublabel' => ($policy->creator->full_name ?? 'System') . ' · Revised content on ' . $policy->updated_at->format('M d, Y'),
                                    'done'     => true,
                                ],
                                [
                                    'label'    => $policy->status === 'active' ? 'Final Approved — v' . $policy->version : 'Final Approval — v' . $policy->version,
                                    'sublabel' => $policy->approved_by
                                        ? (($policy->approver->full_name ?? 'System') . ' · Approved ' . $policy->approved_at->format('M d, Y'))
                                        : 'Awaiting final sign-off',
                                    'done'     => $policy->status === 'active',
                                    'active'   => $policy->status === 'pending_approval',
                                ],
                            ];
                        @endphp
                        @foreach($revSteps as $step)
                        @php
                            $isDone     = $step['done'] ?? false;
                            $isActive   = $step['active'] ?? false;
                            $bg         = $isDone ? 'bg-emerald-50 border-emerald-100 shadow-sm' : ($isActive ? 'bg-blue-50 border-blue-100 shadow-sm' : 'bg-gray-50 border-gray-200');
                            $circle     = $isDone ? 'bg-emerald-500' : ($isActive ? 'bg-blue-500' : 'bg-gray-300');
                            $badgeBg    = $isDone ? 'border-emerald-200' : ($isActive ? 'border-blue-200' : 'border-gray-200');
                            $dotColor   = $isDone ? 'bg-emerald-500' : ($isActive ? 'bg-blue-500 animate-pulse' : 'bg-gray-400');
                            $badgeTxt   = $isDone ? 'text-emerald-600' : ($isActive ? 'text-blue-600' : 'text-gray-400');
                            $badgeLabel = $isDone ? 'Completed' : ($isActive ? 'In Progress' : 'Locked');
                            $subColor   = $isDone ? 'text-emerald-700/70' : ($isActive ? 'text-blue-600/70' : 'text-gray-400');
                        @endphp
                        <div class="flex items-center gap-4 p-5 {{ $bg }} rounded-2xl border transition-all">
                            <div class="w-10 h-10 rounded-full {{ $circle }} flex items-center justify-center text-white font-bold">{{ $loop->iteration }}</div>
                            <div class="flex-1">
                                <h4 class="text-sm font-bold text-gray-900">{{ $step['label'] }}</h4>
                                <p class="text-xs {{ $subColor }} font-medium mt-0.5">{{ $step['sublabel'] }}</p>
                            </div>
                            <div class="flex items-center gap-2 px-3 py-1 bg-white rounded-full border {{ $badgeBg }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                                <span class="text-[10px] font-bold {{ $badgeTxt }} uppercase">{{ $badgeLabel }}</span>
                            </div>
                        </div>
                        @endforeach

                    @else
                    {{-- ── FIRST VERSION (v1.0): full 3-step flow ── --}}
                    @php
                        $steps = [
                            [
                                'label'    => 'Document Author',
                                'sublabel' => ($policy->creator->full_name ?? 'System') . ' · Created ' . $policy->created_at->format('M d, Y'),
                                'done'     => true,
                            ],
                            [
                                'label'    => 'QA Review',
                                'sublabel' => $policy->reviewer_id
                                    ? (($policy->reviewer->full_name ?? 'System') . ' · Reviewed ' . $policy->reviewed_at->format('M d, Y'))
                                    : ($wfStep >= 1 ? 'In review...' : 'Awaiting submission'),
                                'done'     => $wfStep >= 2,
                                'active'   => $wfStep === 1,
                            ],
                            [
                                'label'    => $policy->status === 'active' ? 'Final Approved — v' . $policy->version : 'Final Approval',
                                'sublabel' => $policy->approved_by
                                    ? (($policy->approver->full_name ?? 'System') . ' · Approved ' . $policy->approved_at->format('M d, Y'))
                                    : ($wfStep >= 2 ? 'Awaiting final sign-off' : 'Locked'),
                                'done'     => $wfStep >= 3,
                                'active'   => $wfStep === 2,
                            ],
                        ];
                    @endphp
                    @foreach($steps as $step)
                    @php
                        $isDone     = $step['done'] ?? false;
                        $isActive   = $step['active'] ?? false;
                        $bg         = $isDone ? 'bg-emerald-50 border-emerald-100 shadow-sm' : ($isActive ? 'bg-blue-50 border-blue-100 shadow-sm' : 'bg-gray-50 border-gray-200');
                        $circle     = $isDone ? 'bg-emerald-500' : ($isActive ? 'bg-blue-500' : 'bg-gray-300');
                        $badgeBg    = $isDone ? 'border-emerald-200' : ($isActive ? 'border-blue-200' : 'border-gray-200');
                        $dotColor   = $isDone ? 'bg-emerald-500' : ($isActive ? 'bg-blue-500 animate-pulse' : 'bg-gray-400');
                        $badgeTxt   = $isDone ? 'text-emerald-600' : ($isActive ? 'text-blue-600' : 'text-gray-400');
                        $badgeLabel = $isDone ? 'Completed' : ($isActive ? 'In Progress' : 'Locked');
                        $subColor   = $isDone ? 'text-emerald-700/70' : ($isActive ? 'text-blue-600/70' : 'text-gray-400');
                    @endphp
                    <div class="flex items-center gap-4 p-5 {{ $bg }} rounded-2xl border transition-all">
                        <div class="w-10 h-10 rounded-full {{ $circle }} flex items-center justify-center text-white font-bold">{{ $loop->iteration }}</div>
                        <div class="flex-1">
                            <h4 class="text-sm font-bold text-gray-900">{{ $step['label'] }}</h4>
                            <p class="text-xs {{ $subColor }} font-medium mt-0.5">{{ $step['sublabel'] }}</p>
                        </div>
                        <div class="flex items-center gap-2 px-3 py-1 bg-white rounded-full border {{ $badgeBg }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                            <span class="text-[10px] font-bold {{ $badgeTxt }} uppercase">{{ $badgeLabel }}</span>
                        </div>
                    </div>
                    @endforeach
                    @endif

                    @if($policy->rejection_notes)
                    <div class="mt-4 p-4 bg-red-50 border border-red-100 rounded-2xl flex gap-3">
                        <span class="text-red-500 mt-0.5">⚠</span>
                        <div>
                            <p class="text-xs font-bold text-red-600 mb-1">Rejection Notes</p>
                            <p class="text-sm text-red-700">{{ $policy->rejection_notes }}</p>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Attachments Tab --}}
                <div id="tab-attachments" class="tab-content hidden space-y-4 py-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($policy->attachments as $file)
                        <div class="p-4 rounded-2xl border border-gray-100 bg-white flex items-center gap-4 hover:border-indigo-200 transition-all group">
                            <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-xl">📄</div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-bold text-gray-900 truncate">{{ $file->file_name }}</div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ round($file->file_size / 1024, 2) }} KB</div>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ Storage::url($file->file_path) }}" target="_blank" class="p-2 text-gray-400 hover:text-indigo-600 transition-colors">📥</a>
                                <button onclick="deleteAttachment({{ $file->id }})" class="p-2 text-gray-400 hover:text-red-500 transition-colors">🗑️</button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    @if($policy->attachments->isEmpty())
                        <div class="flex flex-col items-center justify-center py-12 grayscale opacity-40">
                            <span class="text-5xl mb-4">📂</span>
                            <p class="text-sm font-bold text-gray-400 uppercase tracking-widest">No Attachments Found</p>
                        </div>
                    @endif

                    <div class="bg-indigo-50/50 p-6 rounded-2xl border border-indigo-100/50 mt-6">
                        <form action="{{ route('qms.policy.update', $policy->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf @method('PUT')
                            <input type="hidden" name="title" value="{{ $policy->title }}">
                            <input type="hidden" name="status" value="{{ $policy->status }}">
                            <input type="hidden" name="priority" value="{{ $policy->priority }}">
                            <input type="hidden" name="content" value="{{ $policy->content }}">
                            
                            <h4 class="text-xs font-bold text-indigo-600 mb-4 uppercase tracking-widest">Upload Documentation</h4>
                            <div class="flex items-center gap-3">
                                <input type="file" name="attachments[]" multiple class="flex-1 bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm">
                                <button type="submit" class="qms-btn qms-btn-primary !px-6">Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="qms-card">
                <h3 class="qms-card-title mb-6">Governance Matrix</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest font-['Sora']">Classification</span>
                        <span class="text-sm font-bold text-gray-900 bg-gray-50 px-3 py-1 rounded-lg border border-gray-100">{{ $policy->classification ?: 'Internal' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest font-['Sora']">Risk Priority</span>
                        <span class="inline-badge font-bold px-3 py-1 rounded-lg text-xs {{ $policy->priority == 'high' ? 'bg-red-50 text-red-600 border border-red-100' : ($policy->priority == 'medium' ? 'bg-amber-50 text-amber-600 border border-amber-100' : 'bg-emerald-50 text-emerald-600 border border-emerald-100') }}">
                            {{ ucfirst($policy->priority) }} Level
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest font-['Sora']">Responsible Org</span>
                        <span class="text-sm font-bold text-gray-900">{{ $policy->department ?: 'Universal' }}</span>
                    </div>
                    
                    <div class="pt-4 border-t border-gray-50 mt-4 space-y-3">
                        <div class="flex items-start gap-4">
                            <div class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-xs">👤</div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase">Policy Owner</p>
                                <p class="text-xs font-bold text-gray-800">{{ $policy->creator->full_name ?: 'System Owner' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-indigo-600 p-6 rounded-2xl text-white shadow-xl shadow-indigo-100">
                <h4 class="text-sm font-bold uppercase tracking-widest mb-6 opacity-80">Export Protocol</h4>
                <div class="space-y-3">
                    <button onclick="window.print()" class="w-full flex items-center justify-between p-3 bg-indigo-500/50 hover:bg-indigo-400/50 rounded-xl transition-all group">
                        <span class="text-sm font-bold">Print Policy</span>
                        <span class="opacity-0 group-hover:opacity-100 transition-opacity">🖨️</span>
                    </button>
                    <button class="w-full flex items-center justify-between p-3 bg-indigo-500/50 hover:bg-indigo-400/50 rounded-xl transition-all group">
                        <span class="text-sm font-bold">Download PDF</span>
                        <span class="opacity-0 group-hover:opacity-100 transition-opacity">📥</span>
                    </button>
                    <button class="w-full flex items-center justify-between p-3 bg-indigo-500/50 hover:bg-indigo-400/50 rounded-xl transition-all group">
                        <span class="text-sm font-bold">Digital Sign</span>
                        <span class="opacity-0 group-hover:opacity-100 transition-opacity">✍️</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.qms-tab {
    padding: 1rem 1.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
    border-bottom: 2px solid transparent;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.qms-tab:hover { color: #4f46e5; }
.qms-tab.active {
    color: #4f46e5;
    border-bottom-color: #4f46e5;
}
</style>

{{-- Rejection Modal --}}
<div id="rejectModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
        <h3 class="text-base font-bold text-gray-900 mb-1">Reject & Return to Author</h3>
        <p class="text-xs text-gray-400 mb-4">Provide a reason so the author can make the necessary corrections.</p>
        <textarea id="rejectionNotes" rows="4" placeholder="Describe what needs to be corrected..."
            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-red-400 focus:ring focus:ring-red-50 resize-none"></textarea>
        <div class="flex justify-end gap-3 mt-4">
            <button onclick="closeRejectModal()" class="px-4 py-2 border border-gray-200 text-gray-600 rounded-xl text-sm hover:bg-gray-50">Cancel</button>
            <button onclick="submitRejection()" class="px-4 py-2 bg-red-500 text-white rounded-xl text-sm hover:bg-red-600">Reject & Return</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let _rejectType = null;

    function switchTab(el, tabId) {
        document.querySelectorAll('.qms-tab').forEach(t => t.classList.remove('active'));
        el.classList.add('active');
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        document.getElementById('tab-' + tabId).classList.remove('hidden');
    }

    async function workflowAction(action) {
        const labels = {
            'submit-review': 'Submit this policy for QA review?',
            'qa-approve':    'Approve QA review and move to final approval?',
            'final-approve': 'Finally approve this policy and make it active?',
        };
        if (!confirm(labels[action] || 'Confirm action?')) return;
        try {
            const res = await fetch(`/qms/policy/{{ $policy->id }}/${action}`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) { window.location.reload(); }
            else { alert(data.message || 'Error'); }
        } catch (e) { alert('Network error'); }
    }

    function openRejectModal(type) {
        _rejectType = type;
        document.getElementById('rejectionNotes').value = '';
        document.getElementById('rejectModal').classList.remove('hidden');
        document.getElementById('rejectModal').classList.add('flex');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
        document.getElementById('rejectModal').classList.remove('flex');
        _rejectType = null;
    }

    async function submitRejection() {
        const notes = document.getElementById('rejectionNotes').value.trim();
        if (!notes) { alert('Please provide rejection notes.'); return; }
        const action = _rejectType === 'qa' ? 'qa-reject' : 'final-reject';
        try {
            const res = await fetch(`/qms/policy/{{ $policy->id }}/${action}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ rejection_notes: notes })
            });
            const data = await res.json();
            if (data.success) { window.location.reload(); }
            else { alert(data.message || 'Error'); }
        } catch (e) { alert('Network error'); }
    }

    async function deleteAttachment(id) {
        if(!confirm('Are you sure you want to delete this attachment?')) return;
        try {
            const res = await fetch(`/qms/policy/attachment/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if(data.success) { window.location.reload(); }
            else { alert(data.message || 'Error deleting attachment'); }
        } catch (e) { alert('Network error'); }
    }
</script>
@endpush
@endsection
