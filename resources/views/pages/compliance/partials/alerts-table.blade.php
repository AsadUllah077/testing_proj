@if($alerts->count() > 0)
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach($alerts as $alert)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                        {{ ucfirst(str_replace('_', ' ', $alert->alert_type)) }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ $alert->title }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    {{ Str::limit($alert->message, 100) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @php
                        $severityClass = match($alert->severity) {
                            'info' => 'bg-blue-100 text-blue-800',
                            'warning' => 'bg-yellow-100 text-yellow-800',
                            'error' => 'bg-red-100 text-red-800',
                            'critical' => 'bg-gray-100 text-gray-800',
                            default => 'bg-gray-100 text-gray-800'
                        };
                    @endphp
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $severityClass }}">
                        {{ ucfirst($alert->severity) }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @php
                        $statusClass = match($alert->status) {
                            'active' => 'bg-green-100 text-green-800',
                            'acknowledged' => 'bg-blue-100 text-blue-800',
                            'resolved' => 'bg-gray-100 text-gray-800',
                            default => 'bg-gray-100 text-gray-800'
                        };
                    @endphp
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusClass }}">
                        {{ ucfirst($alert->status) }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $alert->created_at->format('M j, Y H:i') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    @if($alert->status === 'active')
                    <button class="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-3 rounded text-xs acknowledge-alert"
                            data-alert-id="{{ $alert->id }}">
                        <i class="fas fa-check mr-1"></i> Acknowledge
                    </button>
                    @else
                        @if($alert->acknowledgedBy)
                        <small class="text-gray-500">
                            Ack'd by {{ $alert->acknowledgedBy->name ?? 'Unknown' }}
                            @if($alert->acknowledged_at)
                            on {{ $alert->acknowledged_at->format('M j, Y') }}
                            @endif
                        </small>
                        @endif
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($alerts->hasPages())
<div class="flex justify-center mt-4">
    {{ $alerts->withQueryString()->links() }}
</div>
@endif
@else
<div class="text-center py-8">
    <i class="fas fa-bell-slash text-6xl text-gray-400 mb-4"></i>
    <h5 class="text-xl font-medium text-gray-500 mb-2">No alerts found</h5>
    <p class="text-gray-500">All compliance requirements are up to date.</p>
</div>
@endif