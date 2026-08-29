<div class="overflow-x-auto w-full p-4">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-700">#</th>
                <th class="px-4 py-3 text-left font-medium text-gray-700" data-col="emp_employee_id"><x-sortable
                        column="employee_id" label="Employee ID" />
                </th>
                <th class="px-4 py-3 text-left font-medium text-gray-700" data-col="emp_employee"><x-sortable
                        column="full_name" label="Employee" />
                </th>
                <th class="px-4 py-3 text-left font-medium text-gray-700" data-col="emp_department"><x-sortable
                        column="department_id" label="Department" />
                </th>
                <th class="px-4 py-3 text-left font-medium text-gray-700" data-col="emp_resign_date"><x-sortable
                        column="resign_date" label="Resign Date" /></th>
                <th class="px-4 py-3 text-left font-medium text-gray-700" data-col="emp_designation"><x-sortable
                        column="designation" label="Designation" /></th>
                <th class="px-4 py-3 text-left font-medium text-gray-700" data-col="emp_status"><x-sortable
                        column="status" label="Status" />
                </th>
                <th class="px-4 py-3 text-left font-medium text-gray-700" data-col="emp_tenure"><x-sortable
                        column="date_of_joining" label="Tenure" /></th>
                <th class="px-4 py-3 text-center font-medium text-gray-700">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($employees as $employee)
                <tr id="row-{{ $employee->id }}" class="hover:bg-gray-50">
                    <!-- Serial No Column -->
                    <td class="px-4 py-3 text-sm text-gray-500">
                        {{ $employees->firstItem() + $loop->index }}
                    </td>
                    <!-- Employee ID Column -->
                    <td class="px-4 py-3" data-col="emp_employee_id">
                        <span class="text-sm font-medium text-gray-700 bg-gray-100 px-2 py-1 rounded">
                            {{ $employee->employee_id ?? 'N/A' }}
                        </span>
                    </td>

                    <!-- Employee Name Column -->
                    <td class="px-4 py-3" data-col="emp_employee">
                        <div class="flex items-center gap-3">
                            <!-- Profile Picture -->
                            @if ($employee->profile_picture)
                                <img src="{{ Storage::url($employee->profile_picture) }}"
                                    alt="{{ $employee->full_name }}"
                                    class="h-12 w-12 rounded-full object-cover border border-gray-200">
                            @else
                                <div
                                    class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center border border-gray-200">
                                    <span class="text-lg font-semibold text-blue-600">
                                        {{ substr($employee->full_name, 0, 1) }}
                                    </span>
                                </div>
                            @endif
                            <div>
                                <a href="{{ route('hrm.employee.show', $employee->id) }}"
                                    class="font-medium text-gray-900 hover:text-[#0078C8] transition-colors">
                                    {{ $employee->full_name }}
                                </a>
                                <div class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                                    @if ($employee->email)
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                        </svg>
                                        {{ $employee->email }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Department Column -->
                    <td class="px-4 py-3" data-col="emp_department">
                        <div class="flex items-center gap-2">
                            @if ($employee->department)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                    {{ $employee->department->department }}
                                </span>
                            @else
                                <span class="text-sm text-gray-500">N/A</span>
                            @endif
                        </div>
                    </td>

                    <!-- Resign Date Column -->
                    <td class="px-4 py-3" data-col="emp_resign_date">
                        <span class="text-sm text-gray-700">
                            {{ $employee->resign_date ? \Carbon\Carbon::parse($employee->resign_date)->format('d M Y') : '--' }}
                        </span>
                    </td>

                    <!-- Designation Column -->
                    <td class="px-4 py-3" data-col="emp_designation">
                        <div class="text-sm text-gray-900 ">
                            {{ $employee->designation ?? 'N/A' }}
                        </div>

                    </td>

                    <!-- Status Column -->
                    <td class="px-4 py-3" data-col="emp_status">
                        <div class="flex flex-col gap-1">
                            <span
                                class="inline-block w-fit px-2.5 py-0.5 rounded-full text-xs font-medium
                                                                    {{ $employee->status == 'active'
                                                                        ? 'bg-green-100 text-green-800'
                                                                        : ($employee->status == 'inactive'
                                                                            ? 'bg-yellow-100 text-yellow-800'
                                                                            : ($employee->status == 'terminated'
                                                                                ? 'bg-red-100 text-red-800'
                                                                                : 'bg-gray-100 text-gray-800')) }}">
                                {{ ucfirst($employee->status) }}
                            </span>

                        </div>
                    </td>

                    <!-- Tenure Column -->
                    <td class="px-4 py-3" data-col="emp_tenure">
                        @if($employee->date_of_joining)
                            @php
                                $join    = \Carbon\Carbon::parse($employee->date_of_joining);
                                $endDate = $employee->resign_date ? \Carbon\Carbon::parse($employee->resign_date) : \Carbon\Carbon::now();
                                $diff   = $join->diff($endDate);
                                $years  = $diff->y;
                                $months = $diff->m;
                                $days   = $diff->d;
                                $parts  = [];
                                if ($years  > 0) $parts[] = "{$years}y";
                                if ($months > 0) $parts[] = "{$months}m";
                                if ($days   > 0) $parts[] = "{$days}d";
                                $tenure = $parts ? implode(' ', $parts) : '< 1d';
                            @endphp
                            <div class="text-sm text-gray-800 font-medium">
                                {{ $tenure }}
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                Since {{ $join->format('d M Y') }}
                            </div>
                        @else
                            <span class="text-sm text-gray-400">N/A</span>
                        @endif
                    </td>

                    <!-- Action Column -->
                    <td class="px-4 py-3 text-center relative">
                        <button
                            class="text-gray-400 hover:text-gray-600 dropdown-btn p-1 rounded hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                            </svg>
                        </button>

                        <div
                            class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-20 overflow-hidden">
                            <a href="{{ route('hrm.employee.show', $employee->id) }}"
                                class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd"
                                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                View Details
                            </a>

                            <a href="{{ route('hrm.employee.edit', $employee->id) }}"
                                class="w-full text-left px-4 py-2.5 hover:bg-blue-50 flex items-center gap-3 text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                                Edit
                            </a>



                            <button type="button"
                                class="delete-btn w-full text-left px-4 py-2.5 hover:bg-red-50 flex items-center gap-3 text-sm text-gray-700 hover:text-red-600 transition-colors"
                                data-url="{{ route('hrm.employee.destroy', $employee->id) }}"
                                data-row="#row-{{ $employee->id }}" onclick="confirmDelete(this)">
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
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <p class="text-lg font-medium text-gray-400">No employees found</p>
                            <p class="text-sm text-gray-500 mt-2">Add your first employee to get started</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>


<x-data-table-paginatior :paginator="$employees" />
