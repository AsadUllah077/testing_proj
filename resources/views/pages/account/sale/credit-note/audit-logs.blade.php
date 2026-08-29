@extends('layouts.auth')

@section('title', 'Credit Note Audit Logs')

@section('content')
    <div class="mb-8">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.sale') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="p-2">
            <div class="mx-auto">
                <div class="bg-white border border-gray-300 rounded-md shadow-sm">
                    <!-- Header -->
                    <div
                        class="p-5 border-b border-gray-200 flex rounded-md justify-between items-center bg-gradient-to-r from-gray-50 to-white">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800">Audit Logs</h2>
                            <p class="text-sm text-gray-600 mt-1">
                                Credit Note: <span class="font-semibold">{{ $creditNote->invoice_no }}</span> |
                                Customer: <span class="font-semibold">{{ $creditNote->customer->name ?? 'N/A' }}</span>
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('account.sale.credit-notes.show', $creditNote->id) }}"
                                class="px-4 py-2 border-2 border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#0078C8] hover:text-white transition-all">
                                VIEW Credit Note
                            </a>
                            <a href="{{ get_index_url('account.sale.credit-notes.index') }}"
                                class="px-5 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-all shadow-md hover:shadow-lg">
                                ← BACK TO LIST
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-4">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-6 bg-gray-50 border-b border-gray-200">
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                            <div class="flex items-center">
                                <div class="p-2 bg-blue-100 rounded-lg">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Total Actions</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ $logs->total() }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                            <div class="flex items-center">
                                <div class="p-2 bg-green-100 rounded-lg">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Created</p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ $logs->where('action', 'created')->count() }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                            <div class="flex items-center">
                                <div class="p-2 bg-yellow-100 rounded-lg">
                                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Updated</p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ $logs->where('action', 'updated')->count() }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                            <div class="flex items-center">
                                <div class="p-2 bg-red-100 rounded-lg">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Deleted</p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ $logs->where('action', 'deleted')->count() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Logs Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Action</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        User</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Changes</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        IP Address</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Timestamp</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Details</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($logs as $log)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                            @if ($log->action == 'created') bg-green-100 text-green-800
                                            @elseif($log->action == 'updated') bg-yellow-100 text-yellow-800
                                            @elseif($log->action == 'deleted') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst($log->action) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div
                                                        class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold">
                                                        {{ substr($log->user->name ?? 'S', 0, 1) }}
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $log->user->name ?? 'System' }}</div>
                                                    <div class="text-sm text-gray-500">{{ $log->user->email ?? 'N/A' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                @if ($log->action == 'created')
                                                    <span class="text-green-600">Record created</span>
                                                @elseif($log->action == 'updated' && $log->changes)
                                                    {{ count($log->changes) }} field(s) changed
                                                @elseif($log->action == 'deleted')
                                                    <span class="text-red-600">Record deleted</span>
                                                @else
                                                    No changes recorded
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $log->ip_address ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div>{{ $log->created_at->format('M d, Y') }}</div>
                                            <div class="text-gray-400">{{ $log->created_at->format('h:i A') }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button type="button" onclick="showLogDetails({{ $log->id }})"
                                                class="text-[#0078C8] hover:text-[#006bb0] font-medium">
                                                View Details
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="h-16 w-16 text-gray-300 mb-4" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                    </path>
                                                </svg>
                                                <h3 class="text-lg font-medium text-gray-900 mb-2">No Audit Logs Found</h3>
                                                <p class="text-gray-500 mb-4">No activity has been recorded for this sale
                                                    invoice yet.</p>
                                                <div class="flex gap-2">
                                                    <a href="{{ route('account.sale-invoice.show', $creditNote->id) }}"
                                                        class="inline-flex items-center px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0] transition-colors">
                                                        View Credit Note Details
                                                    </a>
                                                    <a href="{{ get_index_url('account.sale-invoice.index') }}"
                                                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition-colors">
                                                        Back to List
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($logs->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $logs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Log Details Modal -->
    <div id="logDetailsModal" class="fixed inset-0 bg-gray-600/50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Audit Log Details</h3>
                    <button onclick="closeLogDetails()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="mt-4" id="logDetailsContent">
                    <!-- Content will be loaded via JavaScript -->
                </div>

                <div class="mt-6 flex justify-end">
                    <button onclick="closeLogDetails()"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function showLogDetails(logId) {
            // Show loading
            document.getElementById('logDetailsContent').innerHTML = `
            <div class="flex justify-center items-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#0078C8]"></div>
                <span class="ml-2 text-sm text-gray-600">Loading details...</span>
            </div>
        `;

            document.getElementById('logDetailsModal').classList.remove('hidden');

            // Fetch log details
            fetch(`/account/credit-notes/log-details/${logId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById('logDetailsContent').innerHTML = data.html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('logDetailsContent').innerHTML = `
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Network Error</h3>
                        <p class="mt-1 text-sm text-gray-500">Unable to load log details. Please check your connection and try again.</p>
                        <button onclick="closeLogDetails()" class="mt-4 px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors text-sm">
                            Close
                        </button>
                    </div>
                `;
                });
        }

        function closeLogDetails() {
            document.getElementById('logDetailsModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('logDetailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLogDetails();
            }
        });
    </script>
@endpush
