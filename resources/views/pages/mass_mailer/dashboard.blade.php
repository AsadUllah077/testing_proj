@extends('layouts.auth')

@section('title', 'Mass Mailer Dashboard')

@section('content')
<div class="mt-4">
    <div class="mx-auto px-3">
        {{-- Page Header --}}
        <div class="flex items-center justify-between mb-4 bg-white rounded-lg border border-black/10 px-5 py-4">
            <h1 class="text-gray-900 text-xl font-normal">Mass Mailer Dashboard</h1>
            <div class="flex gap-2">
                <a href="{{ route('mass-mailer.sender.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Send Mass Email
                </a>
            </div>
        </div>

        {{-- Stats Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-6 rounded-lg border border-black/10 shadow-sm">
                <p class="text-sm text-gray-500 mb-1">Total Contacts</p>
                <h3 class="text-2xl font-semibold text-gray-900">{{ $stats['total_contacts'] }}</h3>
            </div>
            <div class="bg-white p-6 rounded-lg border border-black/10 shadow-sm">
                <p class="text-sm text-gray-500 mb-1">Total Tags</p>
                <h3 class="text-2xl font-semibold text-gray-900">{{ $stats['total_tags'] }}</h3>
            </div>
            <div class="bg-white p-6 rounded-lg border border-black/10 shadow-sm">
                <p class="text-sm text-gray-500 mb-1">Total Campaigns</p>
                <h3 class="text-2xl font-semibold text-gray-900">{{ $stats['total_campaigns'] }}</h3>
            </div>
            <div class="bg-white p-6 rounded-lg border border-black/10 shadow-sm">
                <p class="text-sm text-gray-500 mb-1">Active Campaigns</p>
                <h3 class="text-2xl font-semibold text-gray-900">{{ $stats['active_campaigns'] }}</h3>
            </div>
        </div>

        {{-- Recent Campaigns Table --}}
        <div class="bg-white rounded-lg border border-black/10 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-black/10 flex justify-between items-center">
                <h2 class="text-lg font-medium text-gray-900">Recent Campaigns</h2>
                <a href="{{ route('mass-mailer.sender.create') }}" class="text-blue-600 hover:underline text-sm font-medium">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-black/10">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Campaign ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Progress</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Created At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-black/10">
                        @forelse($recent_campaigns as $campaign)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">CMR-{{ str_pad($campaign->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">{{ $campaign->subject }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($campaign->status === 'completed') bg-green-100 text-green-800 
                                    @elseif($campaign->status === 'sending') bg-blue-100 text-blue-800 
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($campaign->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-600" style="width: {{ $campaign->total_contacts > 0 ? ($campaign->sent_contacts / $campaign->total_contacts) * 100 : 0 }}%"></div>
                                    </div>
                                    <span class="text-xs text-slate-500">{{ $campaign->sent_contacts }}/{{ $campaign->total_contacts }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $campaign->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-400 italic">No recent campaigns found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
