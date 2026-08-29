<div class="overflow-x-auto bg-white rounded-lg shadow">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="w-12 px-4 py-3 text-center">
                    <input type="checkbox" id="attendance-select-all"
                        class="h-4 w-4 rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]"
                        aria-label="Select all attendance records on this page">
                </th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
                    data-col="at_id">
                    <x-sortable column="id" label="S.No" />
                </th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
                    data-col="at_employee_id">
                    <x-sortable column="employee_id" label="Employee" />
                </th>
                <th scope="col"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
                    data-col="at_attendance_date">
                    <x-sortable column="attendance_date" label="Date" />
                </th>
                <th scope="col"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
                    data-col="at_department_id">
                    <x-sortable column="department_id" label="Department" />
                </th>
                <th scope="col"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
                    data-col="at_check_in">
                    <x-sortable column="check_in" label="Check In" />
                </th>
                <th scope="col"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
                    data-col="at_check_out">
                    <x-sortable column="check_out" label="Check Out" />
                </th>
                <th scope="col"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
                    data-col="at_total_work_hours">
                    <x-sortable column="total_work_hours" label="Work Hours" />
                </th>
                <th scope="col"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
                    data-col="at_status">
                    <x-sortable column="status" label="Status" />
                </th>
                <th scope="col"
                    class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Over Time
                </th>
                <th scope="col"
                    class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Actions
                </th>
            </tr>
        </thead>

        <tbody class="bg-white divide-y divide-gray-100">
            @forelse($attendances as $attendance)
                <tr id="row-{{ $attendance->id }}" class="hover:bg-gray-50 transition-colors">
                    <td class="w-12 px-4 py-4 text-center">
                        @if($attendance->check_in && $attendance->check_out)
                            <input type="checkbox" value="{{ $attendance->id }}"
                                class="attendance-row-checkbox h-4 w-4 rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]"
                                aria-label="Select attendance record {{ $attendance->id }}">
                        @else
                            <input type="checkbox" disabled
                                class="attendance-row-checkbox h-4 w-4 rounded border-gray-200 bg-gray-100 cursor-not-allowed"
                                aria-label="Attendance record {{ $attendance->id }} cannot be transferred without both check-in and check-out"
                                title="Both check-in and check-out are required before transfer">
                        @endif
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700" data-col="at_id">
                        {{ $loop->iteration }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap" data-col="at_employee_id">
                        <div class="font-medium text-gray-900">
                            @if($attendance->employee)
                                <a href="{{ route('hrm.employee.show', $attendance->employee->id) }}" target="_blank"
                                    class="text-[#0078C8] hover:text-[#06578d] hover:underline transition-colors">
                                    {{ $attendance->employee->full_name }}
                                </a>
                            @else
                                <span class="text-gray-400">Employee unavailable</span>
                            @endif
                        </div>
                    </td>

                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700" data-col="at_attendance_date">
                        {{ $attendance->attendance_date->format('d/m/Y') }}
                    </td>

                    <td class="px-4 py-4 whitespace-nowrap text-sm" data-col="at_department_id">
                        @php
                            $dept = $attendance->employee?->department?->department ?? 'N/A';
                            $colors = [
                                'Engineering' => 'bg-blue-100 text-blue-800',
                                'Marketing' => 'bg-purple-100 text-purple-800',
                                'Sales' => 'bg-red-100 text-red-800',
                                'Product' => 'bg-indigo-100 text-indigo-800',
                                'default' => 'bg-gray-100 text-gray-800',
                            ];
                            $colorClass = $colors[$dept] ?? $colors['default'];
                        @endphp
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
                            {{ $dept }}
                        </span>
                    </td>

                    <td data-col="at_check_in"
                        class="px-4 py-4 whitespace-nowrap text-sm {{ $attendance->check_in ? 'text-gray-700' : 'text-gray-500' }}">
                        {{ $attendance->check_in ? Carbon\Carbon::parse($attendance->check_in)->format('h:i A') : '---' }}
                    </td>

                    <td data-col="at_check_out"
                        class="px-4 py-4 whitespace-nowrap text-sm {{ $attendance->check_out ? 'text-gray-700' : 'text-gray-500' }}">
                        {{ $attendance->check_out ? Carbon\Carbon::parse($attendance->check_out)->format('h:i A') : '---' }}
                    </td>

                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700" data-col="at_total_work_hours">
                        @if($attendance->check_in && !$attendance->check_out)
                            @php
                                $checkIn = Carbon\Carbon::parse($attendance->check_in);
                                $diffInSeconds = $checkIn->diffInSeconds(now());
                                $hours = floor($diffInSeconds / 3600);
                                $minutes = floor(($diffInSeconds % 3600) / 60);
                                $seconds = $diffInSeconds % 60;
                            @endphp
                            <span class="live-work-hours font-medium text-[#0078C8]" data-checkin-time="{{ $checkIn->toIso8601String() }}">
                                {{ $hours }}h {{ str_pad($minutes, 2, '0', STR_PAD_LEFT) }}m {{ str_pad($seconds, 2, '0', STR_PAD_LEFT) }}s
                            </span>
                        @else
                            @php
                                $hoursDecimal = abs($attendance->total_work_hours ?? 0);
                                $hours = floor($hoursDecimal);
                                $minutes = round(($hoursDecimal - $hours) * 60);
                            @endphp
                            <span>{{ $hours }}h {{ str_pad($minutes, 2, '0', STR_PAD_LEFT) }}m</span>
                        @endif
                    </td>

                    <td class="px-4 py-4 whitespace-nowrap text-sm" data-col="at_status">
                        @php
                            $status = $attendance->status; // absent, present, late, remote
                            $statusColor = 'bg-gray-100 text-gray-800';

                            // Check for leave if absent
                            if ($status == 'absent') {
                                $date = $attendance->attendance_date->toDateString();
                                $leave = \App\Models\LeaveRecord::where('employee_id', $attendance->employee_id)
                                    ->whereDate('start_date', '<=', $date)
                                    ->whereDate('end_date', '>=', $date)
                                    ->first();

                                if ($leave) {
                                    $status = 'leave';
                                    $statusColor = 'bg-blue-100 text-blue-800';
                                } else {
                                    $statusColor = 'bg-red-100 text-red-800';
                                }
                            } elseif ($status == 'present') {
                                $statusColor = 'bg-green-100 text-green-800';
                            } elseif ($status == 'late') {
                                $statusColor = 'bg-orange-100 text-orange-800';
                            } elseif ($status == 'remote') {
                                $statusColor = 'bg-indigo-100 text-indigo-800';
                            }
                        @endphp
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                            {{ ucfirst($status) }}
                        </span>
                    </td>

                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">
                        {{ $attendance->formatted_overtime ?? '0H OM' }}
                    </td>

                    <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium relative">
                        <button
                            class="text-gray-400 hover:text-gray-600 dropdown-btn p-1 rounded hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>

                        <div
                            class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-20 overflow-hidden">
                            <a href="{{ route('hrm.attendance.show', $attendance->id) }}"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd"
                                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                View Details
                            </a>

                            <a href="{{ route('hrm.attendance.edit', $attendance->id) }}"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                                Edit
                            </a>

                            <button type="button" onclick="confirmDelete(this)"
                                data-url="{{ route('hrm.attendance.destroy', $attendance->id) }}"
                                data-row="#row-{{ $attendance->id }}"
                                class="delete-btn w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors">
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
                    <td colspan="11" class="px-4 py-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-lg font-medium text-gray-400">No attendance records found</p>
                        <p class="text-sm text-gray-500 mt-2">Add your first attendance record to get started</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<x-data-table-paginatior :paginator="$attendances" />
