<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Department Wise Leave Report</title>
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

        .trn-number {
            font-size: 14px;
            margin-top: 5px;
        }

        /* Main Content */
        .content {
            padding: 20px;
        }

        /* Vendor and Invoice Info */
        .info-section {
            margin-bottom: 20px;
        }

        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .info-cell {
            display: table-cell;
            vertical-align: top;
            width: 50%;
            padding: 10px;
        }

        .info-label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .info-value {
            margin-bottom: 5px;
        }

        /* Items Table */
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

        /* Summary Section */
        .summary-section {
            margin-top: 20px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 5px;
            border: none;
        }

        .summary-label {
            font-weight: bold;
            text-align: left;
        }

        .summary-value {
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            font-size: 14px;
            border-top: 1px solid #333 !important;
            padding-top: 10px !important;
        }

        /* Amount in Words */
        .amount-words {
            background: #f9f9f9;
            padding: 10px;
            border: 1px solid #ddd;
            margin: 15px 0;
            font-size: 13px;
        }

        /* Bank Details */
        .bank-details {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }

        .bank-table {
            width: 100%;
            border-collapse: collapse;
        }

        .bank-table td {
            padding: 3px 5px;
            vertical-align: top;
        }

        .bank-label {
            font-weight: bold;
            white-space: nowrap;
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

        .signature {
            float: right;
            margin-top: 30px;
            text-align: center;
        }

        .signature-line {
            width: 200px;
            border-top: 1px solid #333;
            margin-bottom: 5px;
        }

        /* Paid Stamp */
        .paid-stamp {
            position: absolute;
            right: 50px;
            top: 200px;
            transform: rotate(-15deg);
            color: #4CAF50;
            font-size: 20px;
            font-weight: bold;
            opacity: 0.8;
            border: 3px solid #4CAF50;
            padding: 15px 25px;
            border-radius: 5px;
            background: white;
            z-index: 1000;
        }

        /* Page Number */
        .page-number {
            text-align: right;
            font-size: 11px;
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">{{ auth()->user()->company->name ?? 'Company Name' }}</div>
            <div class="invoice-title">Department wise Leave Report</div>
        </div>


        @foreach ($leaves as $leaveRecord)
            <div class="content">
                <h3
                    style="text-align: center; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 20px;">
                    {{ $leaveRecord['department']->department ?? 'Department' }}
                </h3>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">S.No</th>
                            <th style="width: 15%;">Employee Name</th>
                            <th style="width: 12%;">Leave Type</th>
                            <th style="width: 12%;">Start Date</th>
                            <th style="width: 12%;">End Date</th>
                            <th style="width: 12%;">Total Days</th>
                            <th style="width: 12%;">Reason</th>
                            <th style="width: 10%;">Created By</th>
                            <th style="width: 10%;">Created Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaveRecord['records'] as $index => $record)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $record->employee->full_name ?? 'N/A' }}</td>
                                <td>{{ $record->leaveType->name ?? 'N/A' }}</td>
                                <td>{{ $record->start_date ? \Carbon\Carbon::parse($record->start_date)->format('Y-m-d') : 'N/A' }}
                                </td>
                                <td>{{ $record->end_date ? \Carbon\Carbon::parse($record->end_date)->format('Y-m-d') : 'N/A' }}
                                </td>
                                <td>{{ $record->calculateTotalDays() }}</td>
                                <td>{{ $record->adjustment_reason }}</td>
                                <td> {{ $record->createdBy->first_name . ' ' . $record->createdBy->last_name ?? 'System' }}
                                </td>
                                <td>{{ $record->created_at ? Carbon\Carbon::parse($record->created_at)->format('Y-m-d H:i') : 'N/A' }}
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 20px; color: #999;">
                                    No records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
