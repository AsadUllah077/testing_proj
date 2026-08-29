<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        h1 { font-size: 16px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #0078C8; color: #fff; padding: 8px; text-align: left; font-size: 11px; }
        td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        tr:nth-child(even) td { background: #f9fafb; }
    </style>
</head>
<body>
    <h1>Employee Resignations Report</h1>
    <p style="color:#6b7280; font-size:11px;">Generated: {{ now()->format('d M Y, H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employee</th>
                <th>Email</th>
                <th>Resign Date</th>
                <th>Last Working Day</th>
                <th>Title</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse($resignations as $i => $r)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $r->employee->full_name ?? 'N/A' }}</td>
                <td>{{ $r->employee->email ?? '' }}</td>
                <td>{{ $r->resign_date ? $r->resign_date->format('d M Y') : 'N/A' }}</td>
                <td>{{ $r->last_working_day ? $r->last_working_day->format('d M Y') : 'N/A' }}</td>
                <td>{{ $r->title }}</td>
                <td>{{ $r->description ?? '' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center; color:#9ca3af;">No resignations found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
