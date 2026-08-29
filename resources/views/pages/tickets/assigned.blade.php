@extends('layouts.auth')
@section('title', 'My Assigned Tickets')

@section('content')
    <x-acccount.loader />
    <div class="mt-4 mx-auto px-3 max-w-12xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold text-gray-900">My Assigned Tickets</h1>
                <p class="text-sm text-gray-500 mt-0.5">Manage and resolve your assigned issues</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                        <th class="px-5 py-3 text-left font-medium">Ticket #</th>
                        <th class="px-5 py-3 text-left font-medium">Title</th>
                        <th class="px-5 py-3 text-left font-medium hidden sm:table-cell">Severity</th>
                        <th class="px-5 py-3 text-left font-medium">Status</th>
                        <th class="px-5 py-3 text-left font-medium hidden md:table-cell">Due Date</th>
                        <th class="px-5 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($tickets as $ticket)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3.5 font-mono text-xs font-semibold text-[#0078C8] whitespace-nowrap">
                            {{ $ticket->ticket_number }}
                        </td>
                        <td class="px-5 py-3.5 max-w-[180px]">
                            <div class="font-medium text-gray-800 line-clamp-1">{{ $ticket->title }}</div>
                            <div class="text-xs text-gray-400 mt-0.5 line-clamp-1 italic hidden sm:block">{{ $ticket->description }}</div>
                            <div class="mt-1 sm:hidden">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $ticket->severity_color }}">
                                    {{ ucfirst($ticket->severity) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 hidden sm:table-cell">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->severity_color }}">
                                {{ ucfirst($ticket->severity) }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->status_color }}">
                                {{ ucfirst(str_replace('-', ' ', $ticket->status)) }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-gray-500 text-xs whitespace-nowrap hidden md:table-cell">
                            {{ $ticket->due_date ? $ticket->due_date->format('d M Y, h:i A') : '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($ticket->status === 'assigned')
                                <button onclick="startTicket({{ $ticket->id }})"
                                    class="px-3 py-1.5 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] transition-colors whitespace-nowrap">
                                    Start Task
                                </button>
                                @elseif($ticket->status === 'in-progress')
                                <button onclick="openCompleteModal({{ $ticket->id }})"
                                    class="px-3 py-1.5 bg-[#0078C8] text-white text-xs font-medium rounded-lg hover:bg-[#006bb0] transition-colors">
                                    Complete
                                </button>
                                @endif
                                <a href="{{ route('ticket.tickets.show', $ticket) }}" class="p-1.5 text-gray-400 hover:text-[#0078C8]" title="View Details">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-gray-400">
                            No tickets assigned to you yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $tickets->links() }}
            </div>
        </div>
    </div>

    <!-- Complete Modal -->
    <div id="completeModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="font-semibold text-gray-900 mb-4 text-lg">Complete Ticket</h3>
            <form id="completeForm" class="space-y-4">
                <input type="hidden" id="completeTicketId" name="ticket_id">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Resolution Feedback</label>
                    <textarea name="feedback" rows="4" required
                        placeholder="Explain how the issue was resolved..."
                        class="w-full py-2.5 px-4 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] outline-none bg-gray-50"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="document.getElementById('completeModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-lg hover:bg-[#006bb0] transition-colors">Submit Completion</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    async function startTicket(id) {
        if (!confirm('Start working on this ticket?')) return;
        try {
            const res = await fetch(`/tickets/${id}/start`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                Notification.success(data.message);
                setTimeout(() => window.location.reload(), 800);
            } else {
                Notification.error(data.message);
            }
        } catch (err) { Notification.error('Network error'); }
    }

    function openCompleteModal(id) {
        document.getElementById('completeTicketId').value = id;
        document.getElementById('completeModal').classList.remove('hidden');
    }

    document.getElementById('completeForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('completeTicketId').value;
        const formData = new FormData(e.target);

        try {
            const res = await fetch(`/tickets/${id}/complete`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                Notification.success(data.message);
                document.getElementById('completeModal').classList.add('hidden');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                Notification.error(data.message);
            }
        } catch (err) { Notification.error('Network error'); }
    });
</script>
@endpush
