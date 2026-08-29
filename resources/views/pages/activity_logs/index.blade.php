@extends('layouts.auth')

@section('title', 'Activity Logs')

@section('content')

    <div class="bg-white rounded-lg shadow-lg overflow-hidden px-2">

        <form method="GET">
            <div class="p-4 border-b border-gray-200">
                <div class="grid md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Users</label>
                        <select name="user_id"
                            class="select2 w-full border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                            <option value="">-- All Users --</option>
                            @php
                                use App\Models\User;
                                $users = User::where('company_id', loggedCompanyId()['company_id'])->get();
                            @endphp
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->fullName }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @php
                        use App\Models\ActivityLog;
                        $models = ActivityLog::select('loggable_type')->distinct()->pluck('loggable_type')->sort();

                    @endphp
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Model</label>
                        <select name="model"
                            class="select2 w-full border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                            <option value="">-- All Models --</option>
                            @foreach ($models as $model)
                                <option value="{{ $model }}" {{ request('model') == $model ? 'selected' : '' }}>
                                    {{ class_basename($model) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Event</label>
                        <select name="event"
                            class="select2 w-full border border-gray-300 rounded text-sm focus:ring-0 focus:outline-none">
                            <option value="">-- All Events --</option>
                            <option value="created" {{ request('event') == 'created' ? 'selected' : '' }}>Created</option>
                            <option value="updated" {{ request('event') == 'updated' ? 'selected' : '' }}>Updated</option>
                            <option value="deleted" {{ request('event') == 'deleted' ? 'selected' : '' }}>Deleted</option>
                        </select>
                    </div>

                    {{-- <div>
                        <label class="block text-xs text-gray-500 mb-1">Date Range</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}"
                            class="w-full border border-gray-300 rounded text-sm px-3 py-2 focus:ring-0 focus:outline-none date"
                            placeholder="Start Date">
                    </div> --}}

                    <div>
                        <x-default-page />
                    </div>

                    <div class="flex gap-2 items-end">

                        <button type="submit"
                            class="px-4 py-2 border text-[#0078C8] border-[#0078C8] hover:text-white rounded hover:bg-[#0078C8] transition-colors">
                            SEARCH
                        </button>
                        <a href="{{ url()->current() }}"
                            class="px-4 py-2 bg-[#0078C8] border border-[#0078C8] text-white rounded hover:bg-[#0a8de4] transition-colors">
                            RESET
                        </a>
                    </div>
                </div>
            </div>
    </div>
    </form>

    <div class="bg-gradient-to-r bg-[#0078C8] px-6 py-5">
        <h2 class="text-2xl font-bold text-white">Activity Logs</h2>
        <p class="text-blue-100 text-sm mt-1">Track all system activities and changes</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">User</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Model</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Event</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Old Values
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">New Values
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Date</th>
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($logs as $log)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div
                                    class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 font-semibold text-sm">
                                        @if ($log->employee_id)
                                            {{ substr($log->employee?->full_name ?? 'SY', 0, 2) }}
                                        @elseif($log->user_id)
                                            {{ substr($log->user?->first_name ?? 'S', 0, 1) }}{{ substr($log->user?->last_name ?? 'Y', 0, 1) }}
                                        @else
                                            {{ substr('System', 0, 2) }}
                                        @endif
                                    </span>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">
                                        @if ($log->employee_id)
                                            {{ $log->employee?->full_name }}
                                        @elseif($log->user_id)
                                            {{ $log->user?->first_name }} {{ $log->user?->last_name }}
                                        @else
                                            System
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        @if ($log->employee_id)
                                            {{ $log->employee?->email }}
                                        @elseif($log->user_id)
                                            {{ $log->user?->email }}
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                                {{ class_basename($log->loggable_type) }}
                            </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="inline-flex px-3 py-1 text-xs font-semibold rounded-full capitalize
                                                                    @if ($log->action === 'created') bg-green-100 text-green-800
                                                                    @elseif($log->action === 'updated') bg-blue-100 text-blue-800
                                                                    @elseif($log->action === 'deleted') bg-red-100 text-red-800
                                                                    @else bg-gray-100 text-gray-800 @endif">
                                {{ $log->action }}
                            </span>
                        </td>

                        <td class="px-6 py-4">
                            @if (!empty($log->changes['old']))
                                <div class="bg-red-50 rounded-lg p-3 border border-red-100 max-w-xs overflow-x-auto">
                                    <ul class="space-y-1.5">
                                        @foreach ($log->changes['old'] as $key => $value)
                                            @if (!in_array(strtolower($key), ['id', 'created_at', 'updated_at', 'deleted_at']))
                                                <li class="text-xs text-gray-700">
                                                    <span
                                                        class="font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                    <span
                                                        class="text-red-700">{{ resolveActivityFieldValue($key, $value) }}</span>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>

                        <td class="px-6 py-4">
                            @if (!empty($log->changes['new']))
                                <div class="bg-green-50 rounded-lg p-3 border border-green-100 max-w-xs overflow-x-auto">
                                    <ul class="space-y-1.5">
                                        @foreach ($log->changes['new'] as $key => $value)
                                            @if (!in_array(strtolower($key), ['id', 'created_at', 'updated_at', 'deleted_at']))
                                                <li class="text-xs text-gray-700">
                                                    <span
                                                        class="font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                    <span
                                                        class="text-green-700">{{ resolveActivityFieldValue($key, $value) }}</span>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <div class="flex flex-col">
                                <span
                                    class="font-medium">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y') }}</span>
                                <span
                                    class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($log->created_at)->format('h:i A') }}</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No activity logs found</h3>
                            <p class="mt-1 text-sm text-gray-500">Try adjusting your search filters</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
        {{ $logs->withQueryString()->links() }}
    </div>

    </div>

@endsection
