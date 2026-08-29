<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Individual Attendance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            background: #f5f5f5;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            padding: 20px;
            border-bottom: 2px solid #333;
            text-align: center;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            margin: 5px 0;
            color: #333;
        }

        .content {
            padding: 20px;
        }

        .employee-info {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 4px;
        }

        .employee-info h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
            color: #333;
        }

        .employee-info p {
            margin: 3px 0;
            font-size: 13px;
            color: #555;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 12px;
        }

        .items-table th {
            background: #f0f0f0;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }

        .items-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary-section {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 4px;
        }

        .summary-section h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
        }

        .summary-grid {
            display: table;
            width: 100%;
        }

        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 8px;
        }

        .summary-item .label {
            font-size: 11px;
            color: #666;
            margin-bottom: 4px;
        }

        .summary-item .value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #333;
            font-size: 11px;
            color: #666;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">{{ auth()->user()->company->name ?? 'Company Name' }}</div>
            <div class="invoice-title">Individual Attendance Report</div>
        </div>

        <div class="content">
            <!-- Employee Info -->
            <div class="employee-info">
                <h3>{{ $employee->full_name ?? 'N/A' }}</h3>
                <p><strong>Department:</strong> {{ $employee->department->department ?? 'N/A' }}</p>
                @if ($employee->employee_card_id)
                    <p><strong>Employee ID:</strong> {{ $employee->employee_card_id }}</p>
                @endif
            </div>

            <!-- Summary -->
            @php
                $totalRecords = $records->count();
                $presentDays = $records->whereNotNull('check_in')->count();
                $absentDays = $records->whereNull('check_in')->count();
                $totalHours = $records->sum('total_work_hours');
                $avgHours = $totalRecords > 0 ? $totalHours / max($presentDays, 1) : 0;
            @endphp
            <div class="summary-section">
                <h4>Summary</h4>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="label">Total Days</div>
                        <div class="value">{{ $totalRecords }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Present</div>
                        <div class="value" style="color: #27ae60;">{{ $presentDays }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Absent</div>
                        <div class="value" style="color: #e74c3c;">{{ $absentDays }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Avg Hours/Day</div>
                        <div class="value">{{ number_format($avgHours, 1) }}h</div>
                    </div>
                </div>
            </div>

            <!-- Attendance Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Date</th>
                        <th style="width: 12%;">Check In</th>
                        <th style="width: 12%;">Check Out</th>
                        <th style="width: 12%;">Work Type</th>
                        <th style="width: 12%;">Total Hours</th>
                        <th style="width: 12%;">Status</th>
                        <th style="width: 25%;">Remarks/Leave Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $index => $record)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ $record->attendance_date ? \Carbon\Carbon::parse($record->attendance_date)->format('Y-m-d') : 'N/A' }}
                            </td>
                            <td>{{ $record->check_in ? \Carbon\Carbon::parse($record->check_in)->format('h:i A') : '---' }}
                            </td>
                            <td>{{ $record->check_out ? \Carbon\Carbon::parse($record->check_out)->format('h:i A') : '---' }}
                            </td>
                            <td class="text-center">{{ ucfirst($record->working_type ?? 'on-site') }}</td>
                            <td>
                                @php
                                    $hoursDecimal = abs($record->total_work_hours ?? 0);
                                    $hours = floor($hoursDecimal);
                                    $minutes = round(($hoursDecimal - $hours) * 60);
                                @endphp
                                <strong>{{ $hours }}h {{ str_pad($minutes, 2, '0', STR_PAD_LEFT) }}m</strong>
                            </td>
                            <td>
                                <span
                                    style="font-size: 11px;">{{ ucfirst($record->leave_info ? 'Leave' : $record->status) }}</span>
                            </td>
                            <td style="font-size: 9px; line-height: 1.2;">
                                {{ $record->leave_info ?? '---' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px; color: #999;">
                                No attendance records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>DISCLAIMER: This is a computer generated Report.</p>
        </div>
    </div>
</body>

</html>
