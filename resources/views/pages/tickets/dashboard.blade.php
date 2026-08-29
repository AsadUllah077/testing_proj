@extends('layouts.auth')
@section('title', 'Tickets Dashboard')

@section('content')
    <div class="mt-4 mx-auto px-3 max-w-12xl">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Tickets Overview</h1>
                <p class="text-sm text-gray-500 mt-0.5">Real-time status of all reported issues</p>
            </div>
            @if(auth()->user()->hasPermission('ticket.tickets.create'))
            <a href="{{ route('ticket.tickets.create') }}"
                class="w-full sm:w-auto px-4 py-2 bg-[#0078C8] text-white text-sm rounded-lg hover:bg-[#006bb0] flex items-center justify-center gap-2 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Log New Issue
            </a>
            @endif
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <div class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Total</div>
            </div>
            <div class="bg-white rounded-xl border border-blue-100 p-4 text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $stats['open'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Open</div>
            </div>
            <div class="bg-white rounded-xl border border-yellow-100 p-4 text-center">
                <div class="text-2xl font-bold text-yellow-600">{{ $stats['in_progress'] }}</div>
                <div class="text-xs text-gray-500 mt-1">In Progress</div>
            </div>
            <div class="bg-white rounded-xl border border-green-100 p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ $stats['resolved'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Resolved</div>
            </div>
            <div class="bg-white rounded-xl border border-orange-100 p-4 text-center">
                <div class="text-2xl font-bold text-orange-500">{{ $stats['high'] }}</div>
                <div class="text-xs text-gray-500 mt-1">High Severity</div>
            </div>
            <div class="bg-white rounded-xl border border-red-100 p-4 text-center">
                <div class="text-2xl font-bold text-red-600">{{ $stats['critical'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Critical</div>
            </div>
        </div>

        <!-- Recent Tickets -->
        @if(auth()->user()->hasPermission('ticket.tickets.view'))
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">Recent Tickets</h3>
                <a href="{{ route('ticket.tickets.index') }}" class="text-xs text-[#0078C8] hover:underline">View all →</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentTickets as $ticket)
                <div class="px-4 py-3.5 flex items-start sm:items-center gap-3 hover:bg-gray-50 transition-colors">
                    <div class="flex-shrink-0 hidden xs:block">
                        <span class="font-mono text-xs font-semibold text-gray-400">{{ $ticket->ticket_number }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5 xs:hidden">
                            <span class="font-mono text-xs font-semibold text-gray-400">{{ $ticket->ticket_number }}</span>
                        </div>
                        <a href="{{ route('ticket.tickets.show', $ticket) }}"
                            class="text-sm font-medium text-gray-800 hover:text-[#0078C8] line-clamp-1">
                            {{ $ticket->title }}
                        </a>
                        <div class="text-xs text-gray-400 mt-0.5">
                            {{ $ticket->category }} · {{ $ticket->created_at->diffForHumans() }}
                        </div>
                        <div class="flex items-center gap-1.5 mt-1 sm:hidden">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $ticket->severity_color }}">{{ ucfirst($ticket->severity) }}</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $ticket->status_color }}">{{ ucfirst(str_replace('-', ' ', $ticket->status)) }}</span>
                        </div>
                    </div>
                    <div class="hidden sm:flex items-center gap-2 flex-shrink-0">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $ticket->severity_color }}">{{ ucfirst($ticket->severity) }}</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $ticket->status_color }}">{{ ucfirst(str_replace('-', ' ', $ticket->status)) }}</span>
                    </div>
                    <div class="text-xs text-gray-400 flex-shrink-0 hidden md:block">
                        {{ $ticket->assignee?->full_name ?? 'Unassigned' }}
                    </div>
                </div>
                @empty
                <div class="px-5 py-10 text-center text-gray-400 text-sm">
                    No tickets yet.
                    @if(auth()->user()->hasPermission('ticket.tickets.create'))
                        <a href="{{ route('ticket.tickets.create') }}" class="text-[#0078C8] hover:underline ml-1">Log the first issue</a>
                    @endif
                </div>
                @endforelse
            </div>
        </div>
        @endif

    </div>
@endsection
