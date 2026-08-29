<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Sale Transaction Export</title>
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
        <h1>Sale Transaction Report</h1>
        <p>Generated on: {{ now()->format('F d, Y - h:i A') }}</p>
        <p>Total Records: {{ $sales->count() }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 6%;">S.No</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 14%;">Reference No</th>
                <th style="width: 16%;">Customer</th>
                <th style="width: 16%;">Product</th>
                <th style="width: 14%;">Account</th>
                <th style="width: 6%;">KG</th>
                <th style="width: 6%;">Qty</th>
                <th style="width: 8%;">Price</th>
                <th style="width: 12%;" class="text-right">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $index => $sale)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ date('m-d-Y', strtotime($sale->sale?->date)) }}</td>
                    <td>{{ $sale->unique_no }}</td>
                    <td>{{ $sale->sale?->customer?->name }}</td>
                    <td>{{ $sale->item }}</td>
                    <td>{{ $sale->account?->name }}</td>
                    <td>{{ $sale->kg }}</td>
                    <td>{{ $sale->quantity }}</td>
                    <td>{{ number_format($sale->price, 2) }}</td>
                    <td class="text-right">{{ number_format($sale->total, 2) }}</td>
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


    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
</body>

</html>
