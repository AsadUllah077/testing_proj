@extends('layouts.auth')

@section('title', 'HRM Reports')

@section('content')
    @php
        $exportQuery = request()->except([
            'employees_page', 'attendance_page', 'leave_page', 'jobs_page', 'surveys_page', 'exceptions_page'
        ]);
        $employeeStatusColors = [
            'active' => 'bg-emerald-50 text-emerald-700',
            'inactive' => 'bg-slate-100 text-slate-700',
            'resigned' => 'bg-amber-50 text-amber-700',
            'terminated' => 'bg-red-50 text-red-700',
        ];
    @endphp

    <div class="mx-auto px-3 py-4 space-y-4">
        <div class="bg-white border border-gray-200 rounded-xl p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-[#0078C8]">Human Resource Management</div>
                <h1 class="mt-1 text-2xl font-semibold text-gray-900">HRM Reports</h1>
                <p class="mt-1 text-sm text-gray-500">Reports supported by the fields currently  in HRM. Payroll is not included.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if(auth()->user()->hasPermission('hrm.reports.export'))
                    <a href="{{ route('hrm.reports.export.pdf', $exportQuery) }}"
                        class="px-4 py-2 rounded-lg border border-[#0078C8] text-[#0078C8] text-xs font-semibold hover:bg-blue-50">
                        Export Summary PDF
                    </a>
                    <div class="relative group">
                        <button type="button"
                            class="px-4 py-2 rounded-lg bg-[#0078C8] text-white text-xs font-semibold hover:bg-[#006bb0]">
                            Export CSV ▾
                        </button>
                        <div class="hidden group-hover:block absolute right-0 z-30 w-52 bg-white border border-gray-200 rounded-lg shadow-lg py-1">
                            @foreach([
                                'employees' => 'Employee Register',
                                'attendance' => 'Attendance Detail',
                                'leave' => 'Leave Balances',
                                'jobs' => 'Open Positions',
                                'surveys' => 'Survey Participation',
                                'exceptions' => 'Data Quality Exceptions',
                            ] as $key => $label)
                                <a class="block px-4 py-2 text-xs text-gray-700 hover:bg-blue-50"
                                    href="{{ route('hrm.reports.export.csv', array_merge($exportQuery, ['export_report' => $key])) }}">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('hrm.reports.index') }}" class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-3">
                <div class="xl:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Employee / job search</label>
                    <input name="q" value="{{ request('q') }}" placeholder="Name, employee ID, email or job"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-[#0078C8] focus:ring-[#0078C8]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Department</label>
                    <select name="department_id" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">All departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>
                                {{ $department->department }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Employee type</label>
                    <select name="employment_type_id" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">All employee types</option>
                        @foreach($employeeTypes as $type)
                            <option value="{{ $type->id }}" @selected((string) request('employment_type_id') === (string) $type->id)>
                                {{ $type->employee_type }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Employee status</label>
                    <select name="employee_status" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">All statuses</option>
                        @foreach(['active', 'inactive', 'resigned', 'terminated'] as $status)
                            <option value="{{ $status }}" @selected(request('employee_status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Work location</label>
                    <select name="work_location" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">All locations</option>
                        @foreach($workLocations as $location)
                            <option value="{{ $location }}" @selected(request('work_location') === $location)>{{ $location }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">From date</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="w-full rounded-lg border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">To date</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="w-full rounded-lg border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Leave year</label>
                    <select name="year" class="w-full rounded-lg border-gray-300 text-sm">
                        @foreach(range(now()->year, now()->year - 5) as $year)
                            <option value="{{ $year }}" @selected((int) request('year', now()->year) === $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Attendance</label>
                    <select name="attendance_status" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">All records</option>
                        @foreach(['present' => 'Present', 'absent' => 'Absent row', 'late' => 'Late', 'remote' => 'Remote', 'exception' => 'Any exception'] as $key => $label)
                            <option value="{{ $key }}" @selected(request('attendance_status') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Job status</label>
                    <select name="job_status" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">All jobs</option>
                        @foreach(['draft', 'open', 'on_hold', 'closed', 'cancelled'] as $status)
                            <option value="{{ $status }}" @selected(request('job_status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Rows per table</label>
                    <select name="per_page" class="w-full rounded-lg border-gray-300 text-sm">
                        @foreach([10, 20, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) request('per_page', 20) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <a href="{{ route('hrm.reports.index') }}"
                    class="px-4 py-2 rounded-lg border border-gray-300 text-xs font-semibold text-gray-600">Reset</a>
                <button class="px-5 py-2 rounded-lg bg-[#0078C8] text-white text-xs font-semibold">Apply filters</button>
            </div>
        </form>

        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
            @foreach([
                ['Active employees', $summary['active_employees'], 'text-emerald-600', '#workforce'],
                ['Attendance records', $summary['attendance_records'], 'text-blue-600', '#attendance'],
                ['Attendance exceptions', $summary['attendance_exceptions'], 'text-amber-600', '#attendance'],
                ['Approved leave days', $summary['approved_leave_days'], 'text-purple-600', '#leave'],
                ['Open jobs', $summary['open_jobs'], 'text-indigo-600', '#jobs'],
                ['Data exceptions', $summary['exceptions'], 'text-red-600', '#exceptions'],
            ] as [$label, $value, $color, $anchor])
                <a href="{{ $anchor }}" class="bg-white border border-gray-200 rounded-xl p-4 hover:shadow-sm">
                    <div class="text-xs text-gray-500">{{ $label }}</div>
                    <div class="mt-1 text-2xl font-semibold {{ $color }}">{{ number_format($value, is_float($value) ? 1 : 0) }}</div>
                </a>
            @endforeach
        </div>

        <div id="workforce" class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <div class="xl:col-span-2 bg-white border border-gray-200 rounded-xl p-5">
                <h2 class="text-base font-semibold text-gray-900">Workforce Movement</h2>
                <p class="text-xs text-gray-500">Joiners and leavers from current employee records during the last 12 months.</p>
                <div id="movementChart" class="min-h-[320px]"></div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <h2 class="text-base font-semibold text-gray-900">Tenure Distribution</h2>
                <p class="text-xs text-gray-500">Based on employee joining date.</p>
                <div id="tenureChart" class="min-h-[320px]"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <h2 class="text-base font-semibold text-gray-900">Employees by Department</h2>
                <div id="departmentChart" class="min-h-[330px]"></div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <h2 class="text-base font-semibold text-gray-900">Workforce Demographics</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div id="genderChart" class="min-h-[290px]"></div>
                    <div id="typeChart" class="min-h-[290px]"></div>
                </div>
            </div>
        </div>

        <section class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="p-5 border-b border-gray-200">
                <h2 class="text-base font-semibold text-gray-900">Employee Master Register</h2>
                <p class="text-xs text-gray-500">Operational employee fields only; sensitive compensation and bank information are excluded.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-[11px] uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="text-left px-4 py-3">Employee</th>
                            <th class="text-left px-4 py-3">Department / Type</th>
                            <th class="text-left px-4 py-3">Designation / Location</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-left px-4 py-3">Joining</th>
                            <th class="text-right px-4 py-3">Tenure</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($employeeRows as $employee)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('hrm.employee.show', $employee->id) }}" class="font-semibold text-[#0078C8] hover:underline">
                                        {{ $employee->full_name }}
                                    </a>
                                    <div class="text-xs text-gray-400">{{ $employee->employee_id ?: 'No employee ID' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ $employee->department?->department ?? 'Unassigned' }}</div>
                                    <div class="text-xs text-gray-400">{{ $employee->employmentType?->employee_type ?? 'Unassigned' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ $employee->designation ?: '—' }}</div>
                                    <div class="text-xs text-gray-400">{{ $employee->work_location ?: '—' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ $employeeStatusColors[strtolower($employee->status)] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ ucfirst($employee->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $employee->date_of_joining ? \Carbon\Carbon::parse($employee->date_of_joining)->format('d M Y') : '—' }}</td>
                                <td class="px-4 py-3 text-right">{{ $employee->tenure_years !== null ? number_format($employee->tenure_years, 1).'y' : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No employees match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($employeeRows->hasPages())<div class="p-4 border-t">{{ $employeeRows->links() }}</div>@endif
        </section>

        <section id="attendance" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="p-5 border-b border-gray-200 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Attendance Detail & Exceptions</h2>
                    <p class="text-xs text-gray-500">Defaults to the last 30 days. Absence here means an existing row without check-in, not a derived scheduled absence.</p>
                </div>
                <span class="text-xs font-semibold text-amber-700 bg-amber-50 px-3 py-1 rounded-full">{{ $summary['late_records'] }} late</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-[11px] uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="text-left px-4 py-3">Employee</th>
                            <th class="text-left px-4 py-3">Date</th>
                            <th class="text-left px-4 py-3">Check In / Out</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-right px-4 py-3">Hours</th>
                            <th class="text-left px-4 py-3">Exceptions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($attendanceRows as $record)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $record->employee?->full_name ?? 'Missing employee' }}</div>
                                    <div class="text-xs text-gray-400">{{ $record->employee?->employee_id }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $record->attendance_date?->format('d M Y') }}</td>
                                <td class="px-4 py-3">
                                    <span>{{ $record->formatted_check_in }}</span>
                                    <span class="text-gray-300 mx-1">→</span>
                                    <span>{{ $record->formatted_check_out }}</span>
                                </td>
                                <td class="px-4 py-3">{{ ucfirst($record->report_status) }}{{ $record->working_type ? ' · '.ucfirst($record->working_type) : '' }}</td>
                                <td class="px-4 py-3 text-right">{{ $record->formatted_work_hours ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    @forelse($record->exception_labels as $label)
                                        <span class="inline-block mb-1 px-2 py-1 rounded bg-amber-50 text-amber-700 text-[10px]">{{ $label }}</span>
                                    @empty
                                        <span class="text-xs text-emerald-600">No exception</span>
                                    @endforelse
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No attendance records match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($attendanceRows->hasPages())<div class="p-4 border-t">{{ $attendanceRows->links() }}</div>@endif
        </section>

        <section id="leave" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="p-5 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Leave Utilization & Balance</h2>
                    <p class="text-xs text-gray-500">{{ request('year', now()->year) }} balances by employee and leave type.</p>
                </div>
                <div class="text-sm"><strong>{{ number_format($summary['leave_balance_remaining'], 1) }}</strong> remaining days</div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-[11px] uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="text-left px-4 py-3">Employee</th>
                            <th class="text-left px-4 py-3">Department</th>
                            <th class="text-left px-4 py-3">Leave Type</th>
                            <th class="text-right px-4 py-3">Entitlement</th>
                            <th class="text-right px-4 py-3">Used</th>
                            <th class="text-right px-4 py-3">Remaining</th>
                            <th class="text-right px-4 py-3">Utilization</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($leaveBalanceRows as $balance)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $balance->employee?->full_name ?? 'Missing employee' }}</div>
                                    <div class="text-xs text-gray-400">{{ $balance->employee?->employee_id }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $balance->employee?->department?->department ?? 'Unassigned' }}</td>
                                <td class="px-4 py-3">{{ $balance->leaveType?->name ?? 'Missing type' }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($balance->total_leaves, 1) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($balance->used_leaves, 1) }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $balance->remaining_leaves < 0 ? 'text-red-600' : '' }}">{{ number_format($balance->remaining_leaves, 1) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($balance->report_utilization, 1) }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">No leave balances match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($leaveBalanceRows->hasPages())<div class="p-4 border-t">{{ $leaveBalanceRows->links() }}</div>@endif
        </section>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <section id="jobs" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="p-5 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-900">Open Positions & Job Aging</h2>
                    <p class="text-xs text-gray-500">Current job status, age and candidate volume.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-[11px] uppercase text-gray-500">
                            <tr>
                                <th class="text-left px-4 py-3">Job</th>
                                <th class="text-left px-4 py-3">Status</th>
                                <th class="text-right px-4 py-3">Age</th>
                                <th class="text-right px-4 py-3">Candidates</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($jobRows as $job)
                                <tr>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('hrm.jobs.show', $job->id) }}" class="font-medium text-[#0078C8] hover:underline">{{ $job->job_title }}</a>
                                        <div class="text-xs text-gray-400">{{ $job->job_id }} · {{ $job->department?->department ?? 'Unassigned' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ ucwords(str_replace('_', ' ', $job->status)) }}
                                        @if($job->is_overdue)<div class="text-[10px] text-red-600">Past closing date</div>@endif
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ number_format($job->age_days) }}d</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($job->report_candidates_count) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No jobs found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($jobRows->hasPages())<div class="p-4 border-t">{{ $jobRows->links() }}</div>@endif
            </section>

            <section id="surveys" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="p-5 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-900">Survey Participation</h2>
                    <p class="text-xs text-gray-500">Eligible audiences are deduplicated across assignments.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-[11px] uppercase text-gray-500">
                            <tr>
                                <th class="text-left px-4 py-3">Survey</th>
                                <th class="text-left px-4 py-3">Status</th>
                                <th class="text-right px-4 py-3">Audience</th>
                                <th class="text-right px-4 py-3">Responses</th>
                                <th class="text-right px-4 py-3">Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($surveyRows as $survey)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ $survey->title }}</div>
                                        <div class="text-xs text-gray-400">{{ $survey->is_anonymous ? 'Anonymous' : 'Identified' }}</div>
                                    </td>
                                    <td class="px-4 py-3">{{ $survey->status }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($survey->audience_count) }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($survey->response_count) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold">{{ number_format($survey->response_rate, 1) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No surveys found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($surveyRows->hasPages())<div class="p-4 border-t">{{ $surveyRows->links() }}</div>@endif
            </section>
        </div>

        <section id="exceptions" class="bg-white border border-red-200 rounded-xl overflow-hidden">
            <div class="p-5 border-b border-red-100">
                <h2 class="text-base font-semibold text-gray-900">HR Data Quality, Compliance & Audit Exceptions</h2>
                <p class="text-xs text-gray-500">Current-data exceptions found under the selected filters.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-red-50 text-[11px] uppercase tracking-wide text-red-700">
                        <tr>
                            <th class="text-left px-4 py-3">Area</th>
                            <th class="text-left px-4 py-3">Reference</th>
                            <th class="text-left px-4 py-3">Name</th>
                            <th class="text-left px-4 py-3">Exception</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($exceptionRows as $exception)
                            <tr>
                                <td class="px-4 py-3"><span class="px-2 py-1 rounded bg-gray-100 text-xs">{{ $exception['area'] }}</span></td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $exception['reference'] ?: '—' }}</td>
                                <td class="px-4 py-3">{{ $exception['name'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-red-700">{{ $exception['message'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-emerald-600">No data-quality exceptions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($exceptionRows->hasPages())<div class="p-4 border-t">{{ $exceptionRows->links() }}</div>@endif
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const movement = @json($workforceMovement);
            const tenure = @json($tenure);
            const demographics = @json($demographics);

            new ApexCharts(document.querySelector('#movementChart'), {
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                series: [
                    { name: 'Joiners', data: movement.joiners },
                    { name: 'Leavers', data: movement.leavers }
                ],
                xaxis: { categories: movement.labels },
                colors: ['#16a34a', '#ef4444'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                dataLabels: { enabled: false },
                legend: { position: 'top' }
            }).render();

            new ApexCharts(document.querySelector('#tenureChart'), {
                chart: { type: 'donut', height: 320, toolbar: { show: false } },
                series: Object.values(tenure),
                labels: Object.keys(tenure),
                colors: ['#0078C8', '#14b8a6', '#8b5cf6', '#f59e0b', '#ef4444', '#94a3b8'],
                legend: { position: 'bottom' }
            }).render();

            new ApexCharts(document.querySelector('#departmentChart'), {
                chart: { type: 'bar', height: 330, toolbar: { show: false } },
                series: [{ name: 'Employees', data: Object.values(demographics.departments) }],
                xaxis: { categories: Object.keys(demographics.departments), labels: { rotate: -35 } },
                colors: ['#0078C8'],
                plotOptions: { bar: { borderRadius: 4, distributed: true } },
                dataLabels: { enabled: false },
                legend: { show: false }
            }).render();

            [
                ['#genderChart', demographics.gender, 'Gender'],
                ['#typeChart', demographics.types, 'Employee Type']
            ].forEach(([selector, values, title]) => {
                new ApexCharts(document.querySelector(selector), {
                    chart: { type: 'donut', height: 290, toolbar: { show: false } },
                    series: Object.values(values),
                    labels: Object.keys(values),
                    title: { text: title, align: 'center', style: { fontSize: '13px' } },
                    legend: { position: 'bottom' },
                    dataLabels: { enabled: false }
                }).render();
            });
        });
    </script>
@endsection
