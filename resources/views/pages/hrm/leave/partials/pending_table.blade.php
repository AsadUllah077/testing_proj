<div class="overflow-x-auto w-full p-4">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left"><x-sortable column="full_name" label="Employee" /></th>
                <th class="px-4 py-3 text-left"><x-sortable column="department" label="Department" /></th>
                <th class="px-4 py-3 text-left"><x-sortable column="leave_type_id" label="Leave Type" /></th>
                <th class="px-4 py-3 text-left"><x-sortable column="duration" label="Duration" /></th>
                <th class="px-4 py-3 text-left"><x-sortable column="start_date" label="Dates" /></th>
                <th class="px-4 py-3 text-left"><x-sortable column="created_by" label="Requested By" /></th>
                <th class="px-4 py-3 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($leaveRecords as $record)
                <tr id="row-{{ $record->id }}" class="hover:bg-gray-50">
                    <td class="px-4 py-2">
                        <div class="flex items-center gap-2">
                            <div>
                                <div class="font-medium text-gray-800">
                                    {{ $record->employee->full_name }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $record->employee->employee_id }}
                                </div>
                            </div>
                        </div>
                    </td>

                    <td class="px-4 py-2">
                        <span class="text-sm text-gray-700">
                            {{ $record->employee->department->department ?? 'N/A' }}
                        </span>
                    </td>

                    <td class="px-4 py-2">
                        <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs">
                            {{ $record->leaveType?->name }}
                        </span>
                    </td>

                    <td class="px-4 py-2">
                        @php
                            $duration = $record->duration_description;
                            $days = $record->calculateTotalDays();
                        @endphp
                        <div class="font-medium">{{ $days }} days</div>
                        <div class="text-xs text-gray-500">{{ $duration }}</div>
                    </td>

                    <td class="px-4 py-2">
                        {{ $record->start_date->format('d M, Y') }}
                        @if (!$record->start_date->eq($record->end_date))
                            <br>
                            <span class="text-xs text-gray-500">to
                                {{ $record->end_date->format('d M, Y') }}</span>
                        @endif
                    </td>

                    <td class="px-4 py-2">
                        <div class="text-sm">
                            {{ $record->createdBy?->first_name . ' ' . $record->createdBy?->last_name ?? 'System' }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $record->created_at->format('d M, Y') }}
                        </div>
                    </td>

                    <td class="px-6 py-4 text-center relative">
                        <button
                            class="text-gray-400 hover:text-gray-600 dropdown-btn p-1 rounded hover:bg-gray-100 transition-colors"
                            data-id="{{ $record->id }}">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>

                        <div id="dropdown-{{ $record->id }}"
                            class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-20 overflow-hidden">
                            <a href="{{ route('hrm.leave.show', $record->id) }}"
                                class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd"
                                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                View Details
                            </a>

                            <button type="button" onclick="approveLeave({{ $record->id }})"
                                class="w-full text-left px-4 py-2.5 hover:bg-green-50 flex items-center gap-3 text-sm text-gray-700 hover:text-green-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                Approve
                            </button>

                            <button type="button" onclick="rejectLeave({{ $record->id }})"
                                class="w-full text-left px-4 py-2.5 hover:bg-red-50 flex items-center gap-3 text-sm text-gray-700 hover:text-red-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                                Reject
                            </button>

                            <a href="{{ route('hrm.leave.edit', $record->id) }}"
                                class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                                Edit
                            </a>

                            <button type="button"
                                class="delete-btn w-full text-left px-4 py-2.5 hover:bg-red-50 flex items-center gap-3 text-sm text-gray-700 hover:text-red-600 transition-colors"
                                data-url="{{ route('hrm.leave.destroy', $record->id) }}"
                                data-row="#row-{{ $record->id }}" onclick="confirmDelete(this)">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            <p class="text-lg font-medium text-gray-900">No pending leave requests found</p>
                            <p class="text-sm text-gray-500 mt-1">All leave requests have been processed</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-data-table-paginatior :paginator="$leaveRecords" />
