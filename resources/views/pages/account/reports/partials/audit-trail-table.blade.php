<table class="min-w-full text-sm">
    <thead>
        <tr class="bg-gray-50 border-b border-gray-200">
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Date & Time</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">User</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Source</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Type</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Action</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Details</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-100">
        @forelse ($logs as $log)
            @php
                // Determine if this is a unified object (merged) or direct Eloquent model
                $isReport = isset($log->source) ? $log->source === 'report' : $log instanceof \App\Models\ReportAuditTrail;
                $isTransaction = !$isReport;

                $logUser = $log->user ?? null;
                $logAction = $log->action ?? '';
                $logCreatedAt = $log->created_at;

                if ($isReport) {
                    $sourceLabel = 'Report';
                    $typeLabel = $log->report_name ?? ($log->type_label ?? '');
                    $logFilters = $log->filters ?? null;
                    $exportFormat = $log->export_format ?? null;
                    $changes = null;
                    $ipAddress = $log->ip_address ?? null;
                } else {
                    $sourceLabel = 'Transaction';
                    $typeLabel = $log->type_label ?? ($modelLabels[$log->loggable_type] ?? class_basename($log->loggable_type ?? ''));
                    $logFilters = null;
                    $exportFormat = null;
                    $changes = $log->changes ?? null;
                    $ipAddress = null;
                }
            @endphp
            <tr class="hover:bg-gray-50 transition-colors duration-150">
                {{-- Date & Time --}}
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                    <div class="flex flex-col">
                        <span class="font-medium">{{ \Carbon\Carbon::parse($logCreatedAt)->format('d M Y') }}</span>
                        <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($logCreatedAt)->format('h:i A') }}</span>
                    </div>
                </td>

                {{-- User --}}
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="text-blue-600 font-semibold text-xs">
                                {{ substr($logUser?->first_name ?? 'S', 0, 1) }}{{ substr($logUser?->last_name ?? 'Y', 0, 1) }}
                            </span>
                        </div>
                        <div class="ml-2">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $logUser?->first_name }} {{ $logUser?->last_name ?? 'System' }}
                            </div>
                        </div>
                    </div>
                </td>

                {{-- Source --}}
                <td class="px-4 py-3 whitespace-nowrap">
                    @if ($isReport)
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800">
                            Report
                        </span>
                    @else
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800">
                            Transaction
                        </span>
                    @endif
                </td>

                {{-- Type --}}
                <td class="px-4 py-3 whitespace-nowrap">
                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                        {{ $typeLabel }}
                    </span>
                </td>

                {{-- Action --}}
                <td class="px-4 py-3 whitespace-nowrap">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize
                        @if ($logAction === 'created') bg-green-100 text-green-800
                        @elseif($logAction === 'updated') bg-blue-100 text-blue-800
                        @elseif($logAction === 'deleted') bg-red-100 text-red-800
                        @elseif($logAction === 'view') bg-gray-100 text-gray-800
                        @elseif($logAction === 'filter') bg-yellow-100 text-yellow-800
                        @elseif($logAction === 'export') bg-teal-100 text-teal-800
                        @else bg-gray-100 text-gray-800 @endif">
                        @if ($logAction === 'view') Viewed
                        @elseif($logAction === 'filter') Filtered
                        @elseif($logAction === 'export') Exported{{ $exportFormat ? ' (' . strtoupper($exportFormat) . ')' : '' }}
                        @else {{ $logAction }}
                        @endif
                    </span>
                </td>

                {{-- Details --}}
                <td class="px-4 py-3">
                    @if ($isReport)
                        {{-- Report access details: show filters applied --}}
                        @if ($logFilters && count($logFilters) > 0)
                            <div class="bg-indigo-50 rounded-lg p-2 border border-indigo-100 max-w-sm overflow-x-auto">
                                <p class="text-xs font-semibold text-gray-700 mb-1">Filters Applied:</p>
                                <ul class="space-y-1">
                                    @foreach ($logFilters as $key => $value)
                                        <li class="text-xs text-gray-700">
                                            <span class="font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                            <span class="text-indigo-700">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @elseif($logAction === 'export')
                            <span class="text-xs text-gray-500">Exported as {{ strtoupper($exportFormat ?? 'unknown') }}</span>
                        @else
                            <span class="text-xs text-gray-500">No filters applied</span>
                        @endif
                        @if ($ipAddress)
                            <div class="text-xs text-gray-400 mt-1">IP: {{ $ipAddress }}</div>
                        @endif
                    @else
                        {{-- Transaction change details: show old/new values side by side --}}
                        @if (!empty($changes['old']) || !empty($changes['new']))
                            <div class="flex gap-2 flex-wrap">
                                @if (!empty($changes['old']))
                                    <div class="bg-red-50 rounded-lg p-2 border border-red-100 max-w-[200px] overflow-x-auto">
                                        <p class="text-xs font-semibold text-red-700 mb-1">Old</p>
                                        <ul class="space-y-1">
                                            @foreach ($changes['old'] as $key => $value)
                                                @if (!in_array(strtolower($key), ['id', 'created_at', 'updated_at', 'deleted_at', 'company_id']))
                                                    <li class="text-xs text-gray-700">
                                                        <span class="font-semibold">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                        <span class="text-red-700">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                @if (!empty($changes['new']))
                                    <div class="bg-green-50 rounded-lg p-2 border border-green-100 max-w-[200px] overflow-x-auto">
                                        <p class="text-xs font-semibold text-green-700 mb-1">New</p>
                                        <ul class="space-y-1">
                                            @foreach ($changes['new'] as $key => $value)
                                                @if (!in_array(strtolower($key), ['id', 'created_at', 'updated_at', 'deleted_at', 'company_id']))
                                                    <li class="text-xs text-gray-700">
                                                        <span class="font-semibold">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                        <span class="text-green-700">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @else
                            <span class="text-gray-400 text-xs">&mdash;</span>
                        @endif
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No audit trail records found</h3>
                    <p class="mt-1 text-sm text-gray-500">Try adjusting your search filters</p>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<x-data-table-paginatior :paginator="$logs" />
