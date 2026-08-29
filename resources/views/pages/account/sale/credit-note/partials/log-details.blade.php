<div class="space-y-4">
    <!-- Basic Information -->
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Action</label>
            <p class="mt-1 text-sm text-gray-900">
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                    @if($log->action == 'created') bg-green-100 text-green-800
                    @elseif($log->action == 'updated') bg-yellow-100 text-yellow-800
                    @elseif($log->action == 'deleted') bg-red-100 text-red-800
                    @else bg-gray-100 text-gray-800 @endif">
                    {{ ucfirst($log->action) }}
                </span>
            </p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Timestamp</label>
            <p class="mt-1 text-sm text-gray-900">{{ $log->created_at->format('M d, Y h:i A') }}</p>
        </div>
    </div>

    <!-- User Information -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Performed By</label>
        <div class="mt-1 flex items-center">
            <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold text-sm">
                {{ substr($log->user->name ?? 'S', 0, 1) }}
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">{{ $log->user->name ?? 'System' }}</p>
                <p class="text-sm text-gray-500">{{ $log->user->email ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <!-- Technical Details -->
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">IP Address</label>
            <p class="mt-1 text-sm text-gray-900">{{ $log->ip_address ?? 'N/A' }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">User Agent</label>
            <p class="mt-1 text-sm text-gray-900 truncate" title="{{ $log->user_agent ?? 'N/A' }}">
                {{ Str::limit($log->user_agent ?? 'N/A', 50) }}
            </p>
        </div>
    </div>

    <!-- Changes Section -->
    @if($log->action == 'updated' && $log->changes)
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Field Changes</label>
        <div class="border border-gray-200 rounded-md overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Field</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Old Value</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">New Value</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($log->changes as $field => $change)
                    <tr>
                        <td class="px-4 py-2 text-sm font-medium text-gray-900 capitalize">
                            {{ str_replace('_', ' ', $field) }}
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-500">
                            <span class="bg-red-50 text-red-700 px-2 py-1 rounded text-xs">
                                {{ is_array($change['from']) ? json_encode($change['from']) : ($change['from'] ?? 'Empty') }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-500">
                            <span class="bg-green-50 text-green-700 px-2 py-1 rounded text-xs">
                                {{ is_array($change['to']) ? json_encode($change['to']) : ($change['to'] ?? 'Empty') }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @elseif($log->action == 'created')
    <div>
        <label class="block text-sm font-medium text-gray-700">Initial Data</label>
        <div class="mt-1 bg-gray-50 p-3 rounded-md">
            <pre class="text-xs text-gray-600 overflow-auto max-h-40">{{ json_encode($log->new_data, JSON_PRETTY_PRINT) }}</pre>
        </div>
    </div>
    @elseif($log->action == 'deleted')
    <div>
        <label class="block text-sm font-medium text-gray-700">Deleted Data</label>
        <div class="mt-1 bg-red-50 p-3 rounded-md">
            <pre class="text-xs text-red-600 overflow-auto max-h-40">{{ json_encode($log->old_data, JSON_PRETTY_PRINT) }}</pre>
        </div>
    </div>
    @endif
</div>
