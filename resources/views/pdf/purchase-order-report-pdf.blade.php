<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Purchase Order Report Export</title>
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
        <h1>Purchase Order Report Report</h1>
        <p>Generated on: {{ now()->format('F d, Y - h:i A') }}</p>
        <p>Total Records: {{ $purchaseOrderQuotationItems->count() }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 6%;">S.No</th>
                <th style="width: 10%;">Order Date</th>
                <th style="width: 16%;">Departure Date </th>
                <th style="width: 14%;">Reference No</th>
                <th style="width: 16%;">Requested By</th>
                <th style="width: 14%;">Product </th>
                <th style="width: 8%;">Status</th>
                <th style="width: 8%;">Account </th>
                <th style="width: 8%;">Quantity </th>
                <th style="width: 8%;">Price </th>
                <th style="width: 8%;">Total Amount </th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchaseOrderQuotationItems as $index => $purchaseOrderQuotationItem)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ date('m-d-Y', strtotime($purchaseOrderQuotationItem->purchaseOrderQuotation->order_date)) }}</td>
                    <td>{{ date('m-d-Y', strtotime($purchaseOrderQuotationItem->purchaseOrderQuotation->departure_date)) }}
                    </td>
                    <td>{{ $purchaseOrderQuotationItem->purchaseOrderQuotation->ref_no }}</td>
                    <td>{{ $purchaseOrderQuotationItem->purchaseOrderQuotation->createdBy->fullName }}</td>
                    <td>{{ $purchaseOrderQuotationItem->item }}</td>
                    <td> @php
                        $statuses = [
                            1 => 'Cancelled',
                            2 => 'Approved',
                            3 => 'Fully Paid',
                            4 => 'Partial Paid',
                            0 => 'Pending'
                        ];
                    @endphp
                        {{ $statuses[$purchaseOrderQuotationItem->purchaseOrderQuotation->status] ?? 'Unknown' }}
                    </td>
                    <td>{{ $purchaseOrderQuotationItem->account?->name }}</td>
                    <td>{{ $purchaseOrderQuotationItem->quantity }}</td>
                    <td>{{ $purchaseOrderQuotationItem->price }}</td>
                    <td>{{ $purchaseOrderQuotationItem->total }}</td>
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