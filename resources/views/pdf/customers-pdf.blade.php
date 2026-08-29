<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Customers Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 3px solid #0078C8;
        }

        .header h1 {
            color: #0078C8;
            margin: 0;
            font-size: 24px;
        }

        .header p {
            color: #666;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background-color: #0078C8;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
        }

        td {
            padding: 10px 8px;
            border-bottom: 1px solid #ddd;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Customers Report</h1>
        <p>Generated on: {{ now()->format('F d, Y - h:i A') }}</p>
        <p>Total Records: {{ $customers->count() }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">S.No</th>
                <th style="width: 18%;">Name</th>
                <th style="width: 20%;">Email</th>
                <th style="width: 14%;">Phone</th>
                <th style="width: 20%;">Address</th>
                <th style="width: 12%;" class="text-right">Due Balance</th>
                <th style="width: 8%;">Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $index => $customer)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ $customer->phone ?? '—' }}</td>
                    <td>{{ $customer->address ?? '—' }}</td>
                    <td class="text-right">{{ number_format($customer->due_balance ?? 0, 2) }}</td>
                    <td>{{ $customer->created_at->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #999;">
                        No customers found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
</body>

</html>
