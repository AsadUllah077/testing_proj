<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Progress Sheet Export</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 3px solid #0078C8; }
        .header h1 { color: #0078C8; margin: 0; font-size: 20px; }
        .header p { color: #666; margin: 4px 0; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background-color: #0078C8; color: white; padding: 8px 6px; text-align: left; font-size: 10px; }
        td { padding: 7px 6px; border-bottom: 1px solid #ddd; font-size: 10px; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Progress Sheet Report</h1>
        <p>Generated on: {{ now()->format('d M Y, h:i A') }}</p>
        @if($filters['search'])
            <p>Search: {{ $filters['search'] }}</p>
        @endif
        @if($filters['date'])
            <p>Date: {{ \Carbon\Carbon::parse($filters['date'])->format('d M Y') }}</p>
        @endif
        <p>Total Records: {{ $sheets->count() }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:5%">S.No</th>
                <th style="width:12%">Date</th>
                <th style="width:18%">Name</th>
                <th style="width:30%">Working</th>
                <th style="width:10%">Quantity</th>
                <th style="width:25%">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sheets as $i => $sheet)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $sheet->date->format('d-m-Y') }}</td>
                    <td>{{ $sheet->name }}</td>
                    <td>{{ $sheet->working }}</td>
                    <td>{{ $sheet->quantity ?? '—' }}</td>
                    <td>{{ $sheet->remarks ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:20px;color:#999">No entries found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
</body>
</html>
