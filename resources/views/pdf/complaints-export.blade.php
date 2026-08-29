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
    <h1>Employee Complaints Report</h1>
    <p style="color:#6b7280; font-size:11px;">Generated: {{ now()->format('d M Y, H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Title</th>
                <th>Date</th>
                <th>From</th>
                <th>Against</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse($complaints as $i => $c)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $c->title }}</td>
                <td>{{ $c->date ? $c->date->format('d M Y') : 'N/A' }}</td>
                <td>{{ $c->fromEmployee->full_name ?? 'N/A' }}</td>
                <td>{{ $c->againstEmployee->full_name ?? 'N/A' }}</td>
                <td>{{ $c->description ?? '' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center; color:#9ca3af;">No complaints found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
