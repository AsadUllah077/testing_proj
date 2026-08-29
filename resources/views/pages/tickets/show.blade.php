@extends('layouts.auth')
@section('title', $ticket->ticket_number . ' — ' . $ticket->title)

@section('content')
    <div class="mt-4 mx-auto px-3 max-w-12xl">
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="{{ route('ticket.tickets.index') }}" class="hover:text-[#0078C8]">Tickets</a>
            <span>/</span>
            <span class="text-gray-800">{{ $ticket->ticket_number }}</span>
        </div>

        <!-- Ticket Header -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="font-mono text-xs font-bold text-gray-400 bg-gray-100 px-2 py-0.5 rounded">{{ $ticket->ticket_number }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $ticket->severity_color }}">
                            {{ strtoupper($ticket->severity) }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $ticket->status_color }}">
                            {{ strtoupper(str_replace('-', ' ', $ticket->status)) }}
                        </span>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900">{{ $ticket->title }}</h1>
                    <div class="flex flex-wrap items-center gap-4 mt-1.5 text-xs text-gray-400">
                        <span>{{ $ticket->category }}</span>
                        <span>{{ $ticket->created_at->format('d M Y, H:i') }}</span>
                        @if($ticket->due_date)
                            <span class="{{ $ticket->due_date->isPast() && !in_array($ticket->status, ['resolved','closed']) ? 'text-red-500 font-semibold' : '' }}">
                                Due {{ $ticket->due_date->format('d M Y') }}
                                @if($ticket->due_date->isPast() && !in_array($ticket->status, ['resolved','closed']))
                                    — Overdue
                                @endif
                            </span>
                        @endif
                    </div>
                </div>
                @if(auth()->user()->hasPermission('ticket.tickets.delete'))
                <button onclick="confirmDelete()"
                    class="px-4 py-2 bg-[#0078C8] text-white text-sm rounded-lg hover:bg-[#006bb0] transition-colors flex-shrink-0 shadow-sm font-medium">
                    Delete Ticket
                </button>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            <!-- Left: Description + Activity Feed -->
            <div class="lg:col-span-2 space-y-5">

                <!-- Issue Description -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-6 h-6 bg-blue-100 rounded flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 text-blue-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-800">Issue Description</h3>
                    </div>
                    <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ $ticket->description }}</p>

                    @if($ticket->attachment)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-400 uppercase mb-2">Attachment</p>
                        <a href="{{ Storage::url($ticket->attachment) }}" target="_blank"
                            class="inline-flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-lg text-sm text-[#0078C8] hover:bg-blue-50 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                            </svg>
                            View Attachment
                        </a>
                    </div>
                    @endif

                    @if($ticket->feedback)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-semibold text-[#0078C8] uppercase mb-2 tracking-wider">Employee Completion Feedback</p>
                        <div class="bg-blue-50 rounded-lg p-3 border-l-4 border-[#0078C8]">
                            <p class="text-sm text-gray-700 italic">"{{ $ticket->feedback }}"</p>
                            <div class="mt-2 flex items-center gap-2 text-xs text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Completed on {{ $ticket->completed_at?->format('d M Y') }}</span>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Activity Feed -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">Activity Feed</h3>
                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ $ticket->comments->count() }} {{ Str::plural('update', $ticket->comments->count()) }}</span>
                    </div>

                    <!-- Comments list -->
                    <div class="divide-y divide-gray-50" id="commentsList">
                        @forelse($ticket->comments as $comment)
                        <div class="px-5 py-4 flex gap-3" id="comment-{{ $comment->id }}">
                            <div class="w-8 h-8 bg-gradient-to-br from-[#0078C8] to-[#005a96] rounded-full flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                                {{ strtoupper(mb_substr($comment->user->full_name ?? $comment->user->name ?? 'U', 0, 2)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-sm font-semibold text-gray-800">{{ $comment->user->full_name ?? $comment->user->name }}</span>
                                    @if($comment->is_private)
                                    <span class="px-1.5 py-0.5 bg-yellow-100 text-yellow-700 text-xs rounded">Private</span>
                                    @endif
                                    <span class="text-xs text-gray-400">{{ $comment->created_at->format('d M Y, H:i') }}</span>
                                </div>
                                <p class="text-sm text-gray-600 leading-relaxed">{{ $comment->body }}</p>
                            </div>
                            @if($comment->user_id === Auth::id())
                            <button onclick="deleteComment({{ $ticket->id }}, {{ $comment->id }})"
                                class="flex-shrink-0 p-1 text-gray-300 hover:text-red-400 transition-colors mt-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                            @endif
                        </div>
                        @empty
                        <div id="noCommentsMsg" class="px-5 py-8 text-center text-gray-400 text-sm">
                            No activity yet. Add the first update below.
                        </div>
                        @endforelse
                    </div>

                    <!-- Add Comment -->
                    @if(auth()->user()->hasPermission('ticket.tickets.comments.create'))
                    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50">
                        <div class="flex gap-3">
                            <div class="w-8 h-8 bg-gradient-to-br from-[#0078C8] to-[#005a96] rounded-full flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                                {{ strtoupper(mb_substr(Auth::user()->full_name ?? Auth::user()->name ?? 'U', 0, 2)) }}
                            </div>
                            <div class="flex-1">
                                <textarea id="commentBody" rows="2"
                                    placeholder="Add a private comment..."
                                    class="w-full py-2.5 px-4 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all resize-none bg-white"></textarea>
                                <div class="flex flex-wrap items-center justify-between gap-2 mt-2">
                                    <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer">
                                        <input type="checkbox" id="isPrivate" class="rounded border-gray-300">
                                        Private note
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400 cursor-pointer hover:text-gray-600">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                        </svg>
                                        <button id="postCommentBtn"
                                            class="px-4 py-1.5 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] transition-colors">
                                            Post Update
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Right: Control Center + Metadata -->
            <div class="space-y-5">

                <!-- Control Center -->
                @if(auth()->user()->hasPermission('ticket.tickets.edit'))
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="font-semibold text-gray-800 mb-4">Control Center</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Assign to Employee</label>
                            <select id="ctrl_assigned_to" {{ $ticket->status === 'completed' || !auth()->user()->isSuperAdmin() ? 'disabled' : '' }}
                                class="select2 w-full py-2 px-3 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none bg-gray-50 focus:bg-white transition-all">
                                <option value="">Unassigned</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ $ticket->assigned_to == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Workflow Status</label>
                            <select id="ctrl_status" {{ $ticket->status === 'completed' ? 'disabled' : '' }}
                                class="select2 w-full py-2 px-3 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none bg-gray-50 focus:bg-white transition-all">
                                @foreach(['open' => 'Open', 'in-progress' => 'In Progress', 'review' => 'Under Review', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $val => $label)
                                    <option value="{{ $val }}" {{ $ticket->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Severity</label>
                            <select id="ctrl_severity" {{ $ticket->status === 'completed' ? 'disabled' : '' }}
                                class="select2 w-full py-2 px-3 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none bg-gray-50 focus:bg-white transition-all">
                                @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $val => $label)
                                    <option value="{{ $val }}" {{ $ticket->severity === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Target Resolution</label>
                            <input class="date" type="date" id="ctrl_due_date" {{ $ticket->status === 'completed' ? 'disabled' : '' }}
                                value="{{ $ticket->due_date?->format('Y-m-d') }}"
                                class="date w-full py-2 px-3 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none bg-gray-50 focus:bg-white transition-all">
                        </div>

                        @if($ticket->status !== 'completed')
                        <button id="saveChangesBtn"
                            class="w-full py-2.5 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors">
                            Save Changes
                        </button>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Metadata -->
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <h3 class="font-semibold text-gray-800 mb-4">Metadata</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-400 text-xs">Reported by</dt>
                            <dd class="text-gray-700 font-medium text-xs text-right">{{ $ticket->reporter?->full_name ?? $ticket->reporter?->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-400 text-xs">Category</dt>
                            <dd class="text-gray-700 font-medium text-xs text-right">{{ $ticket->category ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-400 text-xs">Assigned To</dt>
                            <dd id="assigneeLabel" class="text-gray-700 font-medium text-xs text-right">{{ $ticket->assignee?->full_name ?? 'Unassigned' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-400 text-xs">Created</dt>
                            <dd class="text-gray-700 font-medium text-xs text-right">{{ $ticket->created_at->format('d M Y') }}</dd>
                        </div>
                        @if($ticket->due_date)
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-400 text-xs">Due Date</dt>
                            <dd class="font-medium text-xs text-right {{ $ticket->due_date->isPast() && !in_array($ticket->status, ['resolved','closed']) ? 'text-red-500' : 'text-gray-700' }}">
                                {{ $ticket->due_date->format('d M Y') }}
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>

                <!-- Share / Flag quick actions -->
                <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col xs:flex-row items-stretch xs:items-center gap-3">
                    <button class="flex-1 py-2 bg-[#0078C8] text-white rounded-lg text-xs hover:bg-[#006bb0] transition-colors flex items-center justify-center gap-2 font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
                        </svg>
                        Share
                    </button>
                    <button class="flex-1 py-2 bg-[#0078C8] text-white rounded-lg text-xs hover:bg-[#006bb0] transition-colors flex items-center justify-center gap-2 font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" />
                        </svg>
                        Escalate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-2">Delete Ticket</h3>
            <p class="text-sm text-gray-500 mb-5">Delete <strong>{{ $ticket->ticket_number }}</strong>? This cannot be undone.</p>
            <div class="flex justify-end gap-3">
                <button onclick="document.getElementById('deleteModal').classList.add('hidden')"
                    class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">Cancel</button>
                <button id="confirmDeleteBtn"
                    class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">Delete</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const csrf      = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const ticketId  = {{ $ticket->id }};
    const updateUrl = '/tickets/' + ticketId;
    const commentUrl = '/tickets/' + ticketId + '/comments';
    const currentUserName = '{{ Auth::user()->full_name ?? Auth::user()->name }}';

    // ---- CONTROL CENTER: SAVE ----
    document.getElementById('saveChangesBtn')?.addEventListener('click', async () => {
        const btn = document.getElementById('saveChangesBtn');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        const body = {
            assigned_to: document.getElementById('ctrl_assigned_to')?.value || null,
            status:      document.getElementById('ctrl_status').value,
            severity:    document.getElementById('ctrl_severity').value,
            due_date:    document.getElementById('ctrl_due_date').value || null,
            _method:     'PUT',
        };

        try {
            const res  = await fetch(updateUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });
            const data = await res.json();
            if (data.success) {
                Notification.success(data.message);
                // Update assignee label
                const sel = document.getElementById('ctrl_assigned_to');
                const label = sel.options[sel.selectedIndex]?.text || 'Unassigned';
                document.getElementById('assigneeLabel').textContent = label === '' ? 'Unassigned' : label;
            } else {
                Notification.error(data.message);
            }
        } catch {
            Notification.error('Network error.');
        }

        btn.disabled = false;
        btn.textContent = 'Save Changes';
    });

    // ---- POST COMMENT ----
    document.getElementById('postCommentBtn')?.addEventListener('click', async () => {
        const body = document.getElementById('commentBody').value.trim();
        if (!body) return;

        const btn = document.getElementById('postCommentBtn');
        btn.disabled = true;

        try {
            const res  = await fetch(commentUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    body,
                    is_private: document.getElementById('isPrivate').checked,
                }),
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('commentBody').value = '';
                document.getElementById('isPrivate').checked = false;

                const c = data.comment;
                const noMsg = document.getElementById('noCommentsMsg');
                if (noMsg) noMsg.remove();

                const privateBadge = c.is_private
                    ? `<span class="px-1.5 py-0.5 bg-yellow-100 text-yellow-700 text-xs rounded">Private</span>`
                    : '';

                const html = `
                <div class="px-5 py-4 flex gap-3" id="comment-${c.id}">
                    <div class="w-8 h-8 bg-gradient-to-br from-[#0078C8] to-[#005a96] rounded-full flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                        ${c.initials}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-sm font-semibold text-gray-800">${c.user_name}</span>
                            ${privateBadge}
                            <span class="text-xs text-gray-400">${c.created_at}</span>
                        </div>
                        <p class="text-sm text-gray-600 leading-relaxed">${c.body}</p>
                    </div>
                    <button onclick="deleteComment(${ticketId}, ${c.id})"
                        class="flex-shrink-0 p-1 text-gray-300 hover:text-red-400 transition-colors mt-0.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>`;

                document.getElementById('commentsList').insertAdjacentHTML('afterbegin', html);
                Notification.success('Comment posted.');
            } else {
                Notification.error(data.message);
            }
        } catch {
            Notification.error('Network error.');
        }

        btn.disabled = false;
    });

    // ---- DELETE COMMENT ----
    async function deleteComment(tId, cId) {
        if (!confirm('Delete this comment?')) return;
        try {
            const res  = await fetch(`/tickets/${tId}/comments/${cId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById(`comment-${cId}`)?.remove();
                Notification.success(data.message);
            } else {
                Notification.error(data.message);
            }
        } catch {
            Notification.error('Network error.');
        }
    }
    window.deleteComment = deleteComment;

    // ---- DELETE TICKET ----
    const deleteModal = document.getElementById('deleteModal');
    function confirmDelete() { deleteModal.classList.remove('hidden'); }
    window.confirmDelete = confirmDelete;

    deleteModal?.addEventListener('click', e => { if (e.target === deleteModal) deleteModal.classList.add('hidden'); });

    document.getElementById('confirmDeleteBtn')?.addEventListener('click', async () => {
        try {
            const res  = await fetch(updateUrl, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) {
                Notification.success(data.message);
                setTimeout(() => window.location.href = '/tickets', 800);
            } else {
                Notification.error(data.message);
            }
        } catch {
            Notification.error('Network error.');
        }
    });
</script>
@endpush
