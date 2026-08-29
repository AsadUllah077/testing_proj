<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Department Wise Employee Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            background: #f5f5f5;
        }

        .invoice-container {
            max-width: 1200px;
            /* Increased from 800px to accommodate more columns */
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        /* Header */
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

        /* Main Content */
        .content {
            padding: 20px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 10px;
            /* Reduced from 12px */
            table-layout: fixed;
            /* Important: prevents overflow */
        }

        .items-table th {
            background: #f0f0f0;
            padding: 6px 4px;
            /* Reduced padding */
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
            word-wrap: break-word;
        }

        .items-table td {
            padding: 6px 4px;
            /* Reduced padding */
            border: 1px solid #ddd;
            word-wrap: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #333;
            font-size: 11px;
            color: #666;
            text-align: center;
        }

        /* Page Break */
        .page-break {
            page-break-after: always;
            margin-bottom: 20px;
        }

        /* Department Header */
        .department-header {
            text-align: center;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        @media print {
            body {
                background: white;
            }

            .invoice-container {
                max-width: 100%;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">{{ auth()->user()->company->name ?? 'Company Name' }}</div>
            <div class="invoice-title">Department wise Employee Report</div>
        </div>

        @foreach ($employees as $employeesRecord)
            <div class="content">
                <h3 class="department-header">
                    {{ $employeesRecord['department']->department ?? 'Department' }}
                </h3>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 4%;">S.No</th>
                            <th style="width: 14%;">Employee ID</th>
                            <th style="width: 13%;">Employee Name</th>
                            <th style="width: 9%;">Type</th>
                            <th style="width: 9%;">Phone</th>
                            <th style="width: 14%;">Email</th>
                            <th style="width: 9%;">City</th>
                            <th style="width: 9%;">State</th>
                            <th style="width: 9%;">Country</th>
                            <th style="width: 6%;">Status</th>
                            <th style="width: 9%;">Joining Date</th>
                            <th style="width: 9%;">Tenure</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employeesRecord['records'] as $index => $record)
                            @php
                                $tenure = 'N/A';
                                if ($record->date_of_joining) {
                                    $join   = \Carbon\Carbon::parse($record->date_of_joining);
                                    $diff   = $join->diff(\Carbon\Carbon::now());
                                    $years  = $diff->y;
                                    $months = $diff->m;
                                    $days   = $diff->d;
                                    $parts  = [];
                                    if ($years  > 0) $parts[] = "{$years}y";
                                    if ($months > 0) $parts[] = "{$months}m";
                                    if ($days   > 0) $parts[] = "{$days}d";
                                    $tenure = $parts ? implode(' ', $parts) : '< 1d';
                                }
                            @endphp
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $record->employee_id ?? 'N/A' }}</td>
                                <td>{{ $record->full_name ?? 'N/A' }}</td>
                                <td>{{ $record->employmentType->employee_type ?? 'N/A' }}</td>
                                <td>{{ $record->phone_number ?? 'N/A' }}</td>
                                <td>{{ $record->email ?? 'N/A' }}</td>
                                <td>{{ $record->city ?? 'N/A' }}</td>
                                <td>{{ $record->state ?? 'N/A' }}</td>
                                <td>{{ $record->country ?? 'N/A' }}</td>
                                <td>{{ ucfirst($record->status) ?? 'N/A' }}</td>
                                <td>{{ $record->date_of_joining ? \Carbon\Carbon::parse($record->date_of_joining)->format('d M Y') : 'N/A' }}</td>
                                <td>{{ $tenure }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 20px; color: #999;">
                                    No records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="page-break"></div>
        @endforeach

        <!-- Footer -->
        <div class="footer">
            <p>DISCLAIMER: This is a computer generated Report.</p>
        </div>
    </div>
</body>

</html>
