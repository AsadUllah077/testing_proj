@extends('layouts.auth')

@section('title', 'Document: ' . $document->title)

@section('content')
<div class="mt-4 px-3">
    {{-- Document Header --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('qms.documentmanagement.index') }}" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $document->title }}</h1>
                    <span class="qms-badge qms-badge-{{ $document->status }}">
                        {{ ucfirst($document->status) }}
                    </span>
                </div>
                <p class="text-sm text-gray-500">
                    {{ $document->doc_no }} · v{{ $document->version }} · Owned by {{ $document->owner->full_name ?? 'Unassigned' }}
                </p>
            </div>
        </div>
        <div class="flex gap-2">
            <button class="qms-btn qms-btn-secondary">✏️ Edit Details</button>
            @if($document->status !== 'active')
                <button class="qms-btn qms-btn-primary">✅ Approve Document</button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="qms-card">
                <div class="flex border-b border-gray-100 mb-6">
                    <button class="px-6 py-4 text-sm font-semibold border-b-2 border-indigo-500 text-indigo-600 transition-all">Overview</button>
                    <button class="px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 transition-all">Version History</button>
                    <button class="px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 transition-all">Distribution</button>
                </div>

                <div class="space-y-6">
                    <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Summary & Purpose</h3>
                        <p class="text-gray-700 leading-relaxed">{{ $document->summary ?: 'No summary provided.' }}</p>
                        <div class="mt-4 p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <span class="text-xs font-semibold text-gray-500 block mb-1">Primary Purpose</span>
                            <p class="text-sm text-gray-700 font-medium">{{ $document->purpose ?: 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Current Attachments</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($document->attachments as $attachment)
                            <div class="flex items-center gap-4 p-4 rounded-xl border border-gray-100 hover:border-indigo-200 hover:bg-indigo-50/30 transition-all group">
                                <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center text-xl">📄</div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $attachment->file_name }}</p>
                                    <p class="text-[11px] text-gray-500">{{ round($attachment->file_size / 1024, 1) }} KB · {{ $attachment->created_at->format('M d, Y') }}</p>
                                </div>
                                <a href="{{ Storage::url($attachment->file_path) }}" target="_blank" class="p-2 text-gray-400 hover:text-indigo-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- History Timeline --}}
            <div class="qms-card">
                <h3 class="qms-card-title mb-6">Activity & Review Audit</h3>
                <div class="space-y-0 relative before:absolute before:left-[17px] before:top-2 before:bottom-2 before:w-0.5 before:bg-gray-100">
                    @forelse($logs as $log)
                    <div class="flex gap-6 pb-8 relative group last:pb-0">
                        <div class="w-9 h-9 rounded-full bg-white border-2 border-indigo-500 shadow-sm flex items-center justify-center z-10 transition-transform group-hover:scale-110">
                            <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-bold text-gray-900">{{ ucfirst($log->action) }} document</p>
                                <span class="text-[11px] font-medium text-gray-400">{{ $log->created_at->format('M d, Y · h:i A') }}</span>
                            </div>
                            <p class="text-xs text-gray-500">Performed by <span class="text-indigo-600 font-semibold">{{ $log->user->full_name ?? 'System' }}</span></p>
                            @if($log->changes)
                            <div class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-100 text-[11px] text-gray-600 font-mono">
                                @foreach($log->changes['new'] ?? [] as $field => $val)
                                    @if($field !== 'updated_at' && $field !== 'summary')
                                        <div class="mb-1"><span class="font-bold">{{ ucwords(str_replace('_', ' ', $field)) }}:</span> {{ is_array($val) ? json_encode($val) : $val }}</div>
                                    @endif
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 italic py-4">No activity logs recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="qms-card">
                <h3 class="qms-card-title mb-4 uppercase tracking-widest text-[11px]">Control Matrix</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Type</span>
                        <span class="font-semibold text-gray-900">{{ $document->type }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Cycle</span>
                        <span class="font-semibold text-gray-900">{{ $document->revision_cycle ?: 'Annual' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Retention</span>
                        <span class="font-semibold text-gray-900">{{ $document->retention ?: 'Permanent' }}</span>
                    </div>
                    <div class="divider !my-2"></div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Confidential</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $document->is_confidential ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-600' }}">
                            {{ $document->is_confidential ? 'YES' : 'NO' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="qms-card">
                <h3 class="qms-card-title mb-4 uppercase tracking-widest text-[11px]">Distribution Access</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($document->controlled_distribution ?: [] as $dept)
                        <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-md text-xs font-medium">{{ $dept }}</span>
                    @empty
                        <span class="text-xs text-gray-400 italic">No restricted distribution set.</span>
                    @endforelse
                </div>
            </div>

            <div class="bg-indigo-600 rounded-2xl p-6 text-white shadow-lg shadow-indigo-200">
                <h4 class="text-sm font-bold opacity-80 uppercase tracking-widest mb-4">Quick Export</h4>
                <div class="space-y-3">
                    <button class="w-full flex items-center justify-between p-3 bg-indigo-500 hover:bg-indigo-400 rounded-xl transition-all group">
                        <span class="text-sm font-semibold">Generate PDF</span>
                        <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </button>
                    <button class="w-full flex items-center justify-between p-3 bg-indigo-500 hover:bg-indigo-400 rounded-xl transition-all group">
                        <span class="text-sm font-semibold">Print Labels</span>
                        <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </button>
                    <button class="w-full flex items-center justify-between p-3 bg-indigo-500 hover:bg-indigo-400 rounded-xl transition-all group">
                        <span class="text-sm font-semibold">Share for Signature</span>
                        <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
