<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Attendance Report - By Name</title>
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

        .employee-header {
            text-align: left;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .employee-header h3 {
            margin: 0 0 4px 0;
            font-size: 16px;
        }

        .employee-header p {
            margin: 0;
            font-size: 12px;
            color: #666;
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
            <div class="invoice-title">Attendance Report - By Name</div>
        </div>

        @foreach ($attendance as $deptData)
            <div class="content">
                <h2
                    style="text-align: center; color: #333; border-bottom: 2px solid #0078C8; padding-bottom: 5px; margin-bottom: 20px;">
                    Department: {{ $deptData['department_name'] }}
                </h2>

                @foreach ($deptData['employees'] as $employeeData)
                    <div class="employee-header">
                        <h3>{{ $employeeData['employee_name'] }}</h3>
                        <p>Department: {{ $deptData['department_name'] }}</p>
                    </div>

                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Work Type</th>
                                <th>Total Hours</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employeeData['records'] as $index => $record)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>{{ $record->attendance_date ? \Carbon\Carbon::parse($record->attendance_date)->format('Y-m-d') : 'N/A' }}
                                    </td>
                                    <td>{{ $record->check_in ? \Carbon\Carbon::parse($record->check_in)->format('h:i A') : 'N/A' }}
                                    </td>
                                    <td>{{ $record->check_out ? \Carbon\Carbon::parse($record->check_out)->format('h:i A') : 'N/A' }}
                                    </td>
                                    <td class="text-center">{{ ucfirst($record->working_type ?? 'on-site') }}</td>
                                    <td>
                                        @php
                                            $hoursDecimal = abs($record->total_work_hours ?? 0);
                                            $hours = floor($hoursDecimal);
                                            $minutes = round(($hoursDecimal - $hours) * 60);
                                        @endphp
                                        <strong>{{ $hours }}h
                                            {{ str_pad($minutes, 2, '0', STR_PAD_LEFT) }}m</strong>
                                    </td>
                                    <td>
                                        <span style="font-size: 11px;">{{ ucfirst($record->status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 20px; color: #999;">
                                        No records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div style="margin-bottom: 30px; border-bottom: 1px dashed #eee;"></div>
                @endforeach
            </div>

            <div style="page-break-after: always; margin-bottom: 20px;"></div>
        @endforeach

        <!-- Footer -->
        <div class="footer">
            <p>DISCLAIMER: This is a computer generated Report.</p>
        </div>
    </div>
</body>

</html>
