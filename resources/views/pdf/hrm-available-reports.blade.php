<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>HRM Available Reports</title>
    <style>
        @page { margin: 11mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #263238; font-size: 7.5px; }
        h1 { margin: 0; color: #0078c8; font-size: 19px; }
        h2 { color: #0f4c81; font-size: 12px; margin: 13px 0 5px; border-bottom: 1px solid #9fb3c8; padding-bottom: 3px; }
        .sub { margin: 3px 0 8px; color: #607d8b; }
        .notice { padding: 5px 7px; background: #eef6fb; border-left: 3px solid #0078c8; margin-bottom: 7px; }
        .cards { width: 100%; border-collapse: separate; border-spacing: 4px; margin: 7px 0; }
        .cards td { background: #f4f8fb; border: 1px solid #d9e2ec; padding: 6px; width: 16.66%; }
        .cards strong { display: block; color: #0f4c81; font-size: 12px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .data th { background: #0f4c81; color: white; text-align: left; padding: 4px; font-size: 6.8px; }
        .data td { border-bottom: 1px solid #d9e2ec; padding: 3px 4px; vertical-align: top; font-size: 6.8px; }
        .data tr:nth-child(even) td { background: #f8fafc; }
        .right { text-align: right; }
        .page-break { page-break-before: always; }
        .footer { color: #78909c; text-align: center; margin-top: 8px; }
    </style>
</head>
<body>
    <h1>HRM Available Reports</h1>
    <div class="sub">Generated {{ now()->format('d M Y, h:i A') }} · HRM only · Payroll excluded</div>

    @php($activeFilters = collect($filters)->except(['per_page', 'export_report'])->filter(fn($value) => $value !== null && $value !== ''))
    @if($activeFilters->isNotEmpty())
        <div class="notice">
            <strong>Applied filters:</strong>
            {{ $activeFilters->map(fn($value, $key) => ucwords(str_replace('_', ' ', $key)).': '.$value)->implode(' · ') }}
        </div>
    @endif

    <table class="cards">
        <tr>
            <td><strong>{{ number_format($summary['active_employees']) }}</strong>Active employees</td>
            <td><strong>{{ number_format($summary['attendance_records']) }}</strong>Attendance records</td>
            <td><strong>{{ number_format($summary['attendance_exceptions']) }}</strong>Attendance exceptions</td>
            <td><strong>{{ number_format($summary['approved_leave_days'], 1) }}</strong>Approved leave days</td>
            <td><strong>{{ number_format($summary['open_jobs']) }}</strong>Open jobs</td>
            <td><strong>{{ number_format($summary['exceptions']) }}</strong>Data exceptions</td>
        </tr>
    </table>

    <h2>Workforce Headcount & Demographics</h2>
    <table class="data">
        <thead><tr><th>Category</th><th>Breakdown</th></tr></thead>
        <tbody>
            <tr><td>Employee status</td><td>{{ $employees->countBy(fn($e) => ucfirst($e->status ?: 'Unknown'))->map(fn($count, $name) => "{$name}: {$count}")->implode(' · ') }}</td></tr>
            <tr><td>Gender</td><td>{{ $demographics['gender']->map(fn($count, $name) => "{$name}: {$count}")->implode(' · ') }}</td></tr>
            <tr><td>Employee type</td><td>{{ $demographics['types']->map(fn($count, $name) => "{$name}: {$count}")->implode(' · ') }}</td></tr>
            <tr><td>Tenure</td><td>{{ collect($tenure)->map(fn($count, $name) => "{$name}: {$count}")->implode(' · ') }}</td></tr>
        </tbody>
    </table>

    <h2>Employee Master Register</h2>
    <table class="data">
        <thead>
            <tr><th>Employee ID</th><th>Name</th><th>Department</th><th>Type</th><th>Designation</th><th>Location</th><th>Status</th><th>Joining</th><th class="right">Tenure</th></tr>
        </thead>
        <tbody>
            @forelse($employees->take(100) as $employee)
                <tr>
                    <td>{{ $employee->employee_id ?: '—' }}</td>
                    <td>{{ $employee->full_name }}</td>
                    <td>{{ $employee->department?->department ?? '—' }}</td>
                    <td>{{ $employee->employmentType?->employee_type ?? '—' }}</td>
                    <td>{{ $employee->designation ?: '—' }}</td>
                    <td>{{ $employee->work_location ?: '—' }}</td>
                    <td>{{ ucfirst($employee->status) }}</td>
                    <td>{{ $employee->date_of_joining ? \Carbon\Carbon::parse($employee->date_of_joining)->format('d-m-Y') : '—' }}</td>
                    <td class="right">{{ $employee->tenure_years !== null ? number_format($employee->tenure_years, 1).'y' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="9">No employees found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($employees->count() > 100)<div class="sub">Showing first 100 employees. Use CSV for the complete register.</div>@endif

    <div class="page-break"></div>
    <h2>Attendance Detail & Exceptions</h2>
    <table class="data">
        <thead>
            <tr><th>Employee</th><th>Date</th><th>Check In</th><th>Check Out</th><th>Status</th><th class="right">Hours</th><th>Exceptions</th></tr>
        </thead>
        <tbody>
            @forelse($attendance->take(100) as $record)
                <tr>
                    <td>{{ $record->employee?->full_name ?? 'Missing employee' }}</td>
                    <td>{{ $record->attendance_date?->format('d-m-Y') }}</td>
                    <td>{{ $record->formatted_check_in }}</td>
                    <td>{{ $record->formatted_check_out }}</td>
                    <td>{{ ucfirst($record->report_status) }}</td>
                    <td class="right">{{ $record->total_work_hours ?? '—' }}</td>
                    <td>{{ implode(' · ', $record->exception_labels) ?: 'None' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No attendance records found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($attendance->count() > 100)<div class="sub">Showing first 100 attendance rows. Use CSV for complete detail.</div>@endif

    <h2>Leave Utilization & Balances</h2>
    <table class="data">
        <thead>
            <tr><th>Employee</th><th>Department</th><th>Leave Type</th><th>Year</th><th class="right">Entitlement</th><th class="right">Used</th><th class="right">Remaining</th><th class="right">Utilization</th></tr>
        </thead>
        <tbody>
            @forelse($leaveBalances->take(100) as $balance)
                <tr>
                    <td>{{ $balance->employee?->full_name ?? 'Missing employee' }}</td>
                    <td>{{ $balance->employee?->department?->department ?? '—' }}</td>
                    <td>{{ $balance->leaveType?->name ?? '—' }}</td>
                    <td>{{ $balance->year }}</td>
                    <td class="right">{{ number_format($balance->total_leaves, 1) }}</td>
                    <td class="right">{{ number_format($balance->used_leaves, 1) }}</td>
                    <td class="right">{{ number_format($balance->remaining_leaves, 1) }}</td>
                    <td class="right">{{ number_format($balance->report_utilization, 1) }}%</td>
                </tr>
            @empty
                <tr><td colspan="8">No leave balances found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-break"></div>
    <h2>Open Positions & Job Aging</h2>
    <table class="data">
        <thead><tr><th>Job ID</th><th>Title</th><th>Department</th><th>Status</th><th>Posted</th><th>Closing</th><th class="right">Age</th><th class="right">Candidates</th><th>Exception</th></tr></thead>
        <tbody>
            @forelse($jobs as $job)
                <tr>
                    <td>{{ $job->job_id }}</td>
                    <td>{{ $job->job_title }}</td>
                    <td>{{ $job->department?->department ?? '—' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $job->status)) }}</td>
                    <td>{{ $job->posted_date?->format('d-m-Y') ?? '—' }}</td>
                    <td>{{ $job->closing_date?->format('d-m-Y') ?? '—' }}</td>
                    <td class="right">{{ number_format($job->age_days) }}d</td>
                    <td class="right">{{ number_format($job->report_candidates_count) }}</td>
                    <td>{{ $job->is_overdue ? 'Past closing date' : '' }}</td>
                </tr>
            @empty
                <tr><td colspan="9">No jobs found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Survey Participation</h2>
    <table class="data">
        <thead><tr><th>Survey</th><th>Status</th><th>Period</th><th>Anonymous</th><th class="right">Audience</th><th class="right">Responses</th><th class="right">Rate</th></tr></thead>
        <tbody>
            @forelse($surveys as $survey)
                <tr>
                    <td>{{ $survey->title }}</td>
                    <td>{{ $survey->status }}</td>
                    <td>{{ $survey->start_date?->format('d-m-Y') }} – {{ $survey->end_date?->format('d-m-Y') }}</td>
                    <td>{{ $survey->is_anonymous ? 'Yes' : 'No' }}</td>
                    <td class="right">{{ number_format($survey->audience_count) }}</td>
                    <td class="right">{{ number_format($survey->response_count) }}</td>
                    <td class="right">{{ number_format($survey->response_rate, 1) }}%</td>
                </tr>
            @empty
                <tr><td colspan="7">No surveys found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>HR Data Quality Exceptions</h2>
    <table class="data">
        <thead><tr><th>Area</th><th>Reference</th><th>Name</th><th>Exception</th></tr></thead>
        <tbody>
            @forelse($exceptions->take(150) as $exception)
                <tr><td>{{ $exception['area'] }}</td><td>{{ $exception['reference'] }}</td><td>{{ $exception['name'] }}</td><td>{{ $exception['message'] }}</td></tr>
            @empty
                <tr><td colspan="4">No data-quality exceptions found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($exceptions->count() > 150)<div class="sub">Showing first 150 exceptions. Use CSV for complete detail.</div>@endif

    <div class="footer">Company-scoped computer-generated HRM report. Payroll is not included.</div>
</body>
</html>
