@extends('layouts.auth')

@section('title', 'Document Management System')

@section('content')
    <div class="mt-4 px-3">

        {{-- Header --}}
        <div class="page-header">
            <div>
                <p class="page-sub uppercase tracking-widest text-[#6378ff] font-bold !mb-1">Quality Management Protocol</p>
                <h1 class="page-title text-3xl font-extrabold text-gray-900 tracking-tight">Document Management</h1>
            </div>
            <div id="hdr-btn-register" class="page-actions flex items-center gap-3">
                <a href="{{ route('qms.documentmanagement.create') }}" class="qms-btn qms-btn-primary shadow-lg shadow-indigo-100">
                    <span class="mr-1.5">➕</span> New Controlled Document
                </a>
            </div>
            <div id="hdr-btn-templates" class="page-actions hidden">
                <button onclick="openModal('modal-template')" class="qms-btn qms-btn-primary shadow-lg shadow-indigo-100">
                    <span class="mr-1.5">➕</span> Upload Template
                </button>
            </div>
            <div id="hdr-btn-external" class="page-actions hidden">
                <button onclick="openModal('modal-external')" class="qms-btn qms-btn-primary shadow-lg shadow-indigo-100">
                    <span class="mr-1.5">➕</span> Add External Document
                </button>
            </div>
            <div id="hdr-btn-requests" class="page-actions hidden">
                <button onclick="openModal('modal-request')" class="qms-btn qms-btn-primary shadow-lg shadow-indigo-100">
                    <span class="mr-1.5">➕</span> New Request
                </button>
            </div>
        </div>

        {{-- Flash Message --}}
        @if(session('success'))
        <div class="mt-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold rounded-xl flex items-center gap-2">
            <span>✓</span> {{ session('success') }}
        </div>
        @endif

        {{-- KPI Summary Cards --}}
        <div class="qms-kpi-grid">
            <div class="qms-kpi-card group cursor-default hover:border-indigo-200 transition-colors">
                <div class="qms-kpi-label uppercase tracking-widest text-[10px] font-bold text-gray-500">Controlled Register</div>
                <div class="qms-kpi-value text-indigo-600 group-hover:scale-105 transition-transform">{{ $stats['controlled'] }}</div>
                <div class="flex items-center gap-1.5 mt-2">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    <span class="text-[10px] text-gray-400 font-bold uppercase">Active Records</span>
                </div>
            </div>
            <div class="qms-kpi-card group cursor-default hover:border-amber-200 transition-colors">
                <div class="qms-kpi-label uppercase tracking-widest text-[10px] font-bold text-gray-500">Pending Review</div>
                <div class="qms-kpi-value text-amber-500 group-hover:scale-105 transition-transform">{{ $stats['pending'] }}</div>
                <div class="flex items-center gap-1.5 mt-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span class="text-[10px] text-gray-400 font-bold uppercase">Awaiting Approval</span>
                </div>
            </div>
            <div class="qms-kpi-card group cursor-default hover:border-emerald-200 transition-colors">
                <div class="qms-kpi-label uppercase tracking-widest text-[10px] font-bold text-gray-500">Approved Systems</div>
                <div class="qms-kpi-value text-emerald-500 group-hover:scale-105 transition-transform">{{ $stats['approved'] }}</div>
                <div class="flex items-center gap-1.5 mt-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-[10px] text-gray-400 font-bold uppercase">Compliance Ready</span>
                </div>
            </div>
            <div class="qms-kpi-card group cursor-default hover:border-gray-200 transition-colors">
                <div class="qms-kpi-label uppercase tracking-widest text-[10px] font-bold text-gray-500">Archived Items</div>
                <div class="qms-kpi-value text-gray-400 group-hover:scale-105 transition-transform">{{ $stats['archived'] }}</div>
                <div class="flex items-center gap-1.5 mt-2">
                    <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                    <span class="text-[10px] text-gray-400 font-bold uppercase">Historical Data</span>
                </div>
            </div>
        </div>

        {{-- Tab Bar --}}
        <div class="mt-8 space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-1" id="tab-nav">
                    <button data-tab="register" onclick="switchTab('register')"
                        class="tab-btn text-sm font-semibold px-4 py-2 rounded-xl transition-all">
                        Document Register
                    </button>
                    <button data-tab="templates" onclick="switchTab('templates')"
                        class="tab-btn text-sm font-semibold px-4 py-2 rounded-xl transition-all">
                        Templates Library
                    </button>
                    <button data-tab="external" onclick="switchTab('external')"
                        class="tab-btn text-sm font-semibold px-4 py-2 rounded-xl transition-all">
                        External Documents
                    </button>
                    <button data-tab="requests" onclick="switchTab('requests')"
                        class="tab-btn text-sm font-semibold px-4 py-2 rounded-xl transition-all">
                        Requests
                    </button>
                </div>
                <div id="search-register">
                    <form action="{{ route('qms.documentmanagement.index') }}" method="GET" class="flex items-center gap-2">
                        <input type="hidden" name="tab" value="register">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by ID or Title..."
                               class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none w-64 shadow-sm">
                        <button type="submit" class="qms-btn qms-btn-secondary !px-4 !py-2">Filter</button>
                    </form>
                </div>
            </div>

            {{-- ─── PANEL: Document Register ─── --}}
            <div id="panel-register">
                <div class="qms-card !p-0 overflow-hidden shadow-xl shadow-indigo-500/5">
                    <table class="qms-table">
                        <thead>
                            <tr>
                                <th class="w-12 text-center">ID</th>
                                <th>Document Detail</th>
                                <th>Owner</th>
                                <th>Type</th>
                                <th>Version</th>
                                <th>Status</th>
                                <th>Retention</th>
                                <th class="text-right px-6">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($documents as $doc)
                            <tr class="hover:bg-indigo-50/40 cursor-pointer transition-all group" onclick="window.location='{{ route('qms.documentmanagement.show', $doc->id) }}'">
                                <td class="text-center font-bold text-gray-400">#{{ $doc->id }}</td>
                                <td>
                                    <div>
                                        <div class="text-sm font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">{{ $doc->title }}</div>
                                        <div class="text-[10px] font-bold text-indigo-500 mt-0.5 uppercase tracking-wider">{{ $doc->doc_no }}</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-[10px] font-bold text-indigo-600">
                                            {{ substr($doc->owner->full_name ?? 'U', 0, 1) }}
                                        </div>
                                        <span class="text-xs font-semibold text-gray-700">{{ $doc->owner->full_name ?? 'Unassigned' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-gray-50 border border-gray-200 text-gray-500 uppercase tracking-widest">
                                        {{ $doc->type }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-bold text-gray-900">v{{ $doc->version }}</span>
                                        @if($doc->revision_cycle)
                                            <span class="text-[9px] text-gray-400 font-bold uppercase">{{ $doc->revision_cycle }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="qms-badge qms-badge-{{ $doc->status }}">
                                        {{ ucfirst($doc->status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-xs font-medium text-gray-500">{{ $doc->retention ?: 'Permanent' }}</span>
                                </td>
                                <td class="text-right px-6" onclick="event.stopPropagation()">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('qms.documentmanagement.show', $doc->id) }}"
                                           class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all" title="View">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('qms.documentmanagement.edit', $doc->id) }}"
                                           class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-all" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <form action="{{ route('qms.documentmanagement.destroy', $doc->id) }}" method="POST"
                                              onsubmit="return confirm('Delete \'{{ addslashes($doc->title) }}\'? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="py-24 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-20 h-20 rounded-full bg-gray-50 flex items-center justify-center text-4xl mb-4 grayscale opacity-30">📋</div>
                                        <p class="text-sm font-bold text-gray-500 uppercase tracking-widest">No Documents Found</p>
                                        <p class="text-xs text-gray-400 mt-2">Try adjusting your filters or add a new record.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between pb-10 pt-4">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        Displaying register page {{ $documents->currentPage() }} of {{ $documents->lastPage() }}
                    </div>
                    <div>{{ $documents->links() }}</div>
                </div>
            </div>

            {{-- ─── PANEL: Templates Library ─── --}}
            <div id="panel-templates" class="hidden pb-10">
                @if($templates->isEmpty())
                <div class="qms-card flex flex-col items-center py-24 text-center">
                    <div class="w-20 h-20 rounded-full bg-gray-50 flex items-center justify-center text-4xl mb-4 grayscale opacity-30">📄</div>
                    <p class="text-sm font-bold text-gray-500 uppercase tracking-widest">No Templates Yet</p>
                    <p class="text-xs text-gray-400 mt-2">Upload your first template using the button above.</p>
                </div>
                @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($templates as $category => $items)
                    <div class="qms-card">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <div class="qms-card-title">{{ $category }}</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 font-semibold uppercase tracking-wider">{{ $items->count() }} template{{ $items->count() !== 1 ? 's' : '' }}</div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            @foreach($items as $tpl)
                            <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-xl group hover:border-indigo-200 hover:bg-indigo-50/30 transition-all">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="text-base shrink-0">📄</span>
                                    <span class="text-sm font-medium text-gray-700 group-hover:text-indigo-700 truncate">{{ $tpl->name }}</span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0 ml-2">
                                    <a href="{{ route('qms.documentmanagement.templates.download', $tpl->id) }}"
                                       class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-white transition-all" title="Download">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('qms.documentmanagement.templates.destroy', $tpl->id) }}" method="POST"
                                          onsubmit="return confirm('Delete \'{{ addslashes($tpl->name) }}\'?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-white transition-all" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- ─── PANEL: External Documents ─── --}}
            <div id="panel-external" class="hidden pb-10">
                <div class="qms-card !p-0 overflow-hidden shadow-xl shadow-indigo-500/5">
                    <table class="qms-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Document</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Expiry / Review</th>
                                <th>Owner</th>
                                <th class="text-right px-6">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($externalDocs as $ext)
                            @php
                                $statusClass = match($ext->status) {
                                    'current'       => 'qms-badge-active',
                                    'expiring_soon' => 'qms-badge-under_review',
                                    'expired'       => 'qms-badge-archived',
                                    default         => 'qms-badge-draft',
                                };
                                $statusLabel = match($ext->status) {
                                    'current'       => 'Current',
                                    'expiring_soon' => 'Expiring Soon',
                                    'expired'       => 'Expired',
                                    default         => ucfirst($ext->status),
                                };
                            @endphp
                            <tr class="hover:bg-indigo-50/40 transition-all">
                                <td class="font-mono text-xs font-bold text-indigo-500">{{ $ext->ref_no }}</td>
                                <td>
                                    <div class="text-sm font-bold text-gray-900">{{ $ext->title }}</div>
                                </td>
                                <td>
                                    <span class="text-xs text-gray-500">{{ $ext->source ?? '—' }}</span>
                                </td>
                                <td>
                                    <span class="qms-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td>
                                    <span class="text-xs text-gray-500">
                                        {{ $ext->expiry_date ? $ext->expiry_date->format('M d, Y') : 'Annual Review' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-xs font-semibold text-gray-700">{{ $ext->owner->full_name ?? '—' }}</span>
                                </td>
                                <td class="text-right px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        @if($ext->file_path)
                                        <a href="{{ route('qms.documentmanagement.external.download', $ext->id) }}"
                                           class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all" title="Download">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                        </a>
                                        @endif
                                        <form action="{{ route('qms.documentmanagement.external.destroy', $ext->id) }}" method="POST"
                                              onsubmit="return confirm('Remove \'{{ addslashes($ext->title) }}\'?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-24 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-20 h-20 rounded-full bg-gray-50 flex items-center justify-center text-4xl mb-4 grayscale opacity-30">📎</div>
                                        <p class="text-sm font-bold text-gray-500 uppercase tracking-widest">No External Documents</p>
                                        <p class="text-xs text-gray-400 mt-2">Add ISO standards, certifications, or customer specs.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ─── PANEL: Requests ─── --}}
            <div id="panel-requests" class="hidden pb-10">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    {{-- Column: Requested --}}
                    <div>
                        <div class="flex items-center justify-between mb-3 px-1">
                            <span class="text-sm font-bold text-blue-600">● Requested</span>
                            <span class="text-xs font-bold bg-blue-100 text-blue-600 px-2.5 py-0.5 rounded-full">{{ ($requests['open'] ?? collect())->count() }}</span>
                        </div>
                        <div class="space-y-3">
                            @forelse($requests['open'] ?? [] as $req)
                            <div class="qms-card !p-4">
                                <div class="text-sm font-bold text-gray-900 mb-0.5">{{ $req->title }}</div>
                                <div class="text-[11px] text-gray-400 mb-3">Requested by {{ $req->requested_by }}</div>
                                @if($req->description)
                                <div class="text-xs text-gray-500 mb-3 line-clamp-2">{{ $req->description }}</div>
                                @endif
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <span class="qms-badge qms-badge-draft text-[10px]">Open</span>
                                    <div class="flex gap-1">
                                        <form action="{{ route('qms.documentmanagement.requests.status', $req->id) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="in_review">
                                            <button type="submit" class="text-[10px] px-2 py-1 bg-amber-50 text-amber-600 rounded-lg border border-amber-200 hover:bg-amber-100 font-semibold transition-all">
                                                → Review
                                            </button>
                                        </form>
                                        <form action="{{ route('qms.documentmanagement.requests.destroy', $req->id) }}" method="POST"
                                              onsubmit="return confirm('Delete this request?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-[10px] px-2 py-1 bg-red-50 text-red-400 rounded-lg border border-red-100 hover:bg-red-100 font-semibold transition-all">✕</button>
                                        </form>
                                    </div>
                                </div>
                                @if($req->due_date)
                                <div class="text-[10px] text-gray-400 mt-2 font-semibold">Due {{ $req->due_date->format('M d, Y') }}</div>
                                @endif
                                <div class="text-[10px] text-gray-300 mt-1">{{ $req->created_at->format('M d') }}</div>
                            </div>
                            @empty
                            <div class="qms-card !p-6 text-center">
                                <p class="text-xs text-gray-400">No open requests</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Column: In Review --}}
                    <div>
                        <div class="flex items-center justify-between mb-3 px-1">
                            <span class="text-sm font-bold text-amber-600">● In Review</span>
                            <span class="text-xs font-bold bg-amber-100 text-amber-600 px-2.5 py-0.5 rounded-full">{{ ($requests['in_review'] ?? collect())->count() }}</span>
                        </div>
                        <div class="space-y-3">
                            @forelse($requests['in_review'] ?? [] as $req)
                            <div class="qms-card !p-4">
                                <div class="text-sm font-bold text-gray-900 mb-0.5">{{ $req->title }}</div>
                                <div class="text-[11px] text-gray-400 mb-3">
                                    {{ $req->assignee ? 'Owner: ' . $req->assignee->full_name : 'Requested by ' . $req->requested_by }}
                                </div>
                                @if($req->description)
                                <div class="text-xs text-gray-500 mb-3 line-clamp-2">{{ $req->description }}</div>
                                @endif
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <span class="qms-badge qms-badge-under_review text-[10px]">In Review</span>
                                    <div class="flex gap-1">
                                        <form action="{{ route('qms.documentmanagement.requests.status', $req->id) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="text-[10px] px-2 py-1 bg-emerald-50 text-emerald-600 rounded-lg border border-emerald-200 hover:bg-emerald-100 font-semibold transition-all">
                                                ✓ Complete
                                            </button>
                                        </form>
                                        <form action="{{ route('qms.documentmanagement.requests.destroy', $req->id) }}" method="POST"
                                              onsubmit="return confirm('Delete this request?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-[10px] px-2 py-1 bg-red-50 text-red-400 rounded-lg border border-red-100 hover:bg-red-100 font-semibold transition-all">✕</button>
                                        </form>
                                    </div>
                                </div>
                                @if($req->due_date)
                                <div class="text-[10px] text-gray-400 mt-2 font-semibold">Due {{ $req->due_date->format('M d, Y') }}</div>
                                @endif
                            </div>
                            @empty
                            <div class="qms-card !p-6 text-center">
                                <p class="text-xs text-gray-400">Nothing in review</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Column: Completed --}}
                    <div>
                        <div class="flex items-center justify-between mb-3 px-1">
                            <span class="text-sm font-bold text-emerald-600">● Completed</span>
                            <span class="text-xs font-bold bg-emerald-100 text-emerald-600 px-2.5 py-0.5 rounded-full">{{ ($requests['completed'] ?? collect())->count() }}</span>
                        </div>
                        <div class="space-y-3">
                            @forelse($requests['completed'] ?? [] as $req)
                            <div class="qms-card !p-4 opacity-80">
                                <div class="text-sm font-bold text-gray-900 mb-0.5">{{ $req->title }}</div>
                                <div class="text-[11px] text-gray-400 mb-3">Completed {{ $req->updated_at->format('M d, Y') }}</div>
                                @if($req->description)
                                <div class="text-xs text-gray-500 mb-3 line-clamp-2">{{ $req->description }}</div>
                                @endif
                                <div class="flex items-center justify-between">
                                    <span class="qms-badge qms-badge-active text-[10px]">Completed</span>
                                    <form action="{{ route('qms.documentmanagement.requests.destroy', $req->id) }}" method="POST"
                                          onsubmit="return confirm('Delete this request?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-[10px] px-2 py-1 bg-red-50 text-red-400 rounded-lg border border-red-100 hover:bg-red-100 font-semibold transition-all">✕</button>
                                    </form>
                                </div>
                            </div>
                            @empty
                            <div class="qms-card !p-6 text-center">
                                <p class="text-xs text-gray-400">No completed requests</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ═══════════════ MODALS ═══════════════ --}}

        {{-- Modal: Upload Template --}}
        <div id="modal-template" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4" style="display:none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
                <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-900">Upload Template</h3>
                    <button onclick="closeModal('modal-template')" class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form action="{{ route('qms.documentmanagement.templates.store') }}" method="POST" enctype="multipart/form-data" class="px-6 py-5 space-y-4">
                    @csrf
                    <div>
                        <label class="form-label">Category <span class="text-red-500">*</span></label>
                        <select name="category" class="filter-select" required>
                            <option value="">Select category…</option>
                            <option value="CAPA Forms">CAPA Forms</option>
                            <option value="Inspection Templates">Inspection Templates</option>
                            <option value="Training Templates">Training Templates</option>
                            <option value="Audit Templates">Audit Templates</option>
                            <option value="Management Review">Management Review</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Template Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., CAPA Report Form" required>
                    </div>
                    <div>
                        <label class="form-label">File <span class="text-red-500">*</span></label>
                        <input type="file" name="file" class="form-control !py-2" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
                        <p class="text-[10px] text-gray-400 mt-1">PDF, DOC, DOCX, XLS, XLSX — max 20 MB</p>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeModal('modal-template')" class="qms-btn qms-btn-secondary">Cancel</button>
                        <button type="submit" class="qms-btn qms-btn-primary">Upload Template</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal: Add External Document --}}
        <div id="modal-external" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4" style="display:none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
                <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-900">Add External Document</h3>
                    <button onclick="closeModal('modal-external')" class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form action="{{ route('qms.documentmanagement.external.store') }}" method="POST" enctype="multipart/form-data" class="px-6 py-5 space-y-4">
                    @csrf
                    <div>
                        <label class="form-label">Document Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., ISO 9001:2015 Standard" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Source <span class="text-red-500">*</span></label>
                            <select name="source" class="filter-select" required>
                                <option value="">Select source…</option>
                                <option value="Standards Authority">Standards Authority</option>
                                <option value="Supplier">Supplier</option>
                                <option value="Customer">Customer</option>
                                <option value="Regulatory Body">Regulatory Body</option>
                                <option value="Internal">Internal</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Status</label>
                            <select name="status" class="filter-select">
                                <option value="current">Current</option>
                                <option value="expiring_soon">Expiring Soon</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Expiry / Review Date</label>
                            <input type="date" name="expiry_date" class="form-control date">
                        </div>
                        <div>
                            <label class="form-label">Owner</label>
                            <select name="owner_id" class="filter-select">
                                <option value="">Select owner…</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Attach File</label>
                        <input type="file" name="file" class="form-control !py-2" accept=".pdf,.doc,.docx">
                        <p class="text-[10px] text-gray-400 mt-1">Optional — PDF, DOC, DOCX — max 20 MB</p>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeModal('modal-external')" class="qms-btn qms-btn-secondary">Cancel</button>
                        <button type="submit" class="qms-btn qms-btn-primary">Add Document</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal: New Request --}}
        <div id="modal-request" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4" style="display:none">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
                <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-900">New Document Request</h3>
                    <button onclick="closeModal('modal-request')" class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form action="{{ route('qms.documentmanagement.requests.store') }}" method="POST" class="px-6 py-5 space-y-4">
                    @csrf
                    <div>
                        <label class="form-label">Request Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Create Grading Policy" required>
                    </div>
                    <div>
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control !h-auto py-3" placeholder="What document is needed and why…"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Requested By <span class="text-red-500">*</span></label>
                            <input type="text" name="requested_by" class="form-control" placeholder="e.g., QA Team" required>
                        </div>
                        <div>
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control date">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Assign To</label>
                        <select name="assigned_to" class="filter-select">
                            <option value="">Unassigned</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="closeModal('modal-request')" class="qms-btn qms-btn-secondary">Cancel</button>
                        <button type="submit" class="qms-btn qms-btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script>
    const TABS = ['register', 'templates', 'external', 'requests'];

    function switchTab(tab) {
        TABS.forEach(t => {
            document.getElementById('panel-' + t).classList.add('hidden');
            document.getElementById('hdr-btn-' + t).classList.add('hidden');
        });

        document.getElementById('panel-' + tab).classList.remove('hidden');
        document.getElementById('hdr-btn-' + tab).classList.remove('hidden');

        // Search bar only for register
        const searchBar = document.getElementById('search-register');
        if (searchBar) searchBar.classList.toggle('hidden', tab !== 'register');

        // Tab button styles
        document.querySelectorAll('.tab-btn').forEach(btn => {
            const isActive = btn.dataset.tab === tab;
            btn.classList.toggle('text-indigo-600', isActive);
            btn.classList.toggle('bg-indigo-50', isActive);
            btn.classList.toggle('font-bold', isActive);
            btn.classList.toggle('text-gray-500', !isActive);
            btn.classList.toggle('hover:text-gray-800', !isActive);
            btn.classList.toggle('hover:bg-gray-100', !isActive);
        });
    }

    function openModal(id) {
        const el = document.getElementById(id);
        el.style.display = 'flex';
        el.classList.remove('hidden');
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        el.style.display = 'none';
        el.classList.add('hidden');
    }

    // Close on backdrop click
    ['modal-template', 'modal-external', 'modal-request'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('click', function (e) {
                if (e.target === this) closeModal(id);
            });
        }
    });

    // Init tab from server (handles redirect-back-to-tab)
    switchTab('{{ $activeTab }}');
</script>
@endpush
