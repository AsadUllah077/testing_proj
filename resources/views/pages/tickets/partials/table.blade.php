<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                @if(auth()->user()->isSuperAdmin())
                <th class="px-5 py-3 text-left font-medium w-10">
                    <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]">
                </th>
                @endif
                <th class="px-5 py-3 text-left font-medium" data-col="ticket_number">Ticket #</th>
                <th class="px-5 py-3 text-left font-medium" data-col="title">Title</th>
                <th class="px-5 py-3 text-left font-medium" data-col="category">Category</th>
                <th class="px-5 py-3 text-left font-medium" data-col="severity">Severity</th>
                <th class="px-5 py-3 text-left font-medium" data-col="status">Status</th>
                <th class="px-5 py-3 text-left font-medium" data-col="assigned_to">Assigned To</th>
                <th class="px-5 py-3 text-left font-medium" data-col="reported_by">Reported By</th>
                <th class="px-5 py-3 text-left font-medium" data-col="created_at">Date</th>
                <th class="px-5 py-3 text-right font-medium">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($tickets as $ticket)
            <tr class="hover:bg-gray-50 transition-colors">
                @if(auth()->user()->isSuperAdmin())
                <td class="px-5 py-3.5 w-10">
                    @if($ticket->status !== 'completed')
                        <input type="checkbox" name="ticket_ids[]" value="{{ $ticket->id }}" class="ticket-checkbox rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]">
                    @else
                        <input type="checkbox" disabled class="rounded border-gray-100 bg-gray-50 text-gray-300 cursor-not-allowed">
                    @endif
                </td>
                @endif
                <td class="px-5 py-3.5 whitespace-nowrap" data-col="ticket_number">
                    <a href="{{ route('ticket.tickets.show', $ticket) }}" class="font-mono text-xs font-semibold text-[#0078C8] hover:underline">
                        {{ $ticket->ticket_number }}
                    </a>
                </td>
                <td class="px-5 py-3.5 max-w-[180px]" data-col="title">
                    <a href="{{ route('ticket.tickets.show', $ticket) }}" class="font-medium text-gray-800 hover:text-[#0078C8] line-clamp-1">
                        {{ $ticket->title }}
                    </a>
                    <div class="mt-1 sm:hidden">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $ticket->severity_color }}">
                            {{ ucfirst($ticket->severity) }}
                        </span>
                    </div>
                </td>
                <td class="px-5 py-3.5 text-gray-500 text-xs" data-col="category">{{ $ticket->category ?? '—' }}</td>
                <td class="px-5 py-3.5" data-col="severity">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->severity_color }}">
                        {{ ucfirst($ticket->severity) }}
                    </span>
                </td>
                <td class="px-5 py-3.5" data-col="status">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->status_color }}">
                        {{ ucfirst(str_replace('-', ' ', $ticket->status)) }}
                    </span>
                </td>
                <td class="px-5 py-3.5 text-gray-600 text-xs" data-col="assigned_to">
                    {{ $ticket->assignee?->full_name ?? '—' }}
                </td>
                <td class="px-5 py-3.5 text-gray-600 text-xs" data-col="reported_by">
                    {{ $ticket->reporter?->full_name ?? ($ticket->reporter?->name ?? '—') }}
                </td>
                <td class="px-5 py-3.5 text-gray-400 text-xs whitespace-nowrap" data-col="created_at">
                    {{ $ticket->created_at->format('d M Y') }}
                </td>
                <td class="px-5 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('ticket.tickets.show', $ticket) }}"
                            class="p-1.5 text-gray-400 hover:text-[#0078C8] hover:bg-blue-50 rounded transition-all"
                            title="View">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </a>
                        @if(auth()->user()->hasPermission('ticket.tickets.delete'))
                        <button onclick="deleteTicket({{ $ticket->id }})"
                            class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition-all"
                            title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="px-5 py-12 text-center text-gray-400 text-sm">
                    No tickets found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>


