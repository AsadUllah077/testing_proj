<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Product Issues Export</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 3px solid #0078C8; }
        .header h1 { color: #0078C8; margin: 0; font-size: 20px; }
        .header p { color: #666; margin: 4px 0; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background-color: #0078C8; color: white; padding: 7px 5px; text-align: left; font-size: 9px; }
        td { padding: 6px 5px; border-bottom: 1px solid #ddd; font-size: 9px; word-break: break-word; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Product Issues To Employees Report</h1>
        <p>Generated on: {{ now()->format('d M Y, h:i A') }}</p>
        <p>Total Records: {{ $issues->count() }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:4%">S.No</th>
                <th style="width:12%">Date Issued</th>
                <th style="width:12%">Product ID</th>
                <th style="width:18%">Product Title</th>
                <th style="width:15%">Employee</th>
                <th style="width:5%">Qty</th>
                <th style="width:10%">Issued By</th>
                <th style="width:10%">Status</th>
                <th style="width:14%">Returned On</th>
            </tr>
        </thead>
        <tbody>
            @forelse($issues as $i => $issue)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $issue->issue_date ? $issue->issue_date->format('d-m-Y H:i') : '—' }}</td>
                    <td>{{ $issue->product->unique_id ?? '—' }}</td>
                    <td>{{ $issue->product->title ?? '—' }}</td>
                    <td>{{ $issue->employee->full_name ?? '—' }}</td>
                    <td>{{ $issue->quantity }}</td>
                    <td>{{ $issue->issuedBy->username ?? 'System' }}</td>
                    <td>{{ $issue->status }}</td>
                    <td>{{ $issue->return_date ? $issue->return_date->format('d-m-Y') : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:20px;color:#999">No product issues found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
</body>
</html>
