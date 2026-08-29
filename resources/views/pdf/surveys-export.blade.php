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
    <h1>HRM Surveys Report</h1>
    <p style="color:#6b7280; font-size:11px;">Generated: {{ now()->format('d M Y, H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Title</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Responses</th>
                <th>Anonymous</th>
            </tr>
        </thead>
        <tbody>
            @forelse($surveys as $i => $s)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $s->title }}</td>
                <td>{{ $s->status }}</td>
                <td>{{ $s->start_date ? $s->start_date->format('d M Y') : 'N/A' }}</td>
                <td>{{ $s->end_date ? $s->end_date->format('d M Y') : 'N/A' }}</td>
                <td>{{ $s->responses_count }}</td>
                <td>{{ $s->is_anonymous ? 'Yes' : 'No' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center; color:#9ca3af;">No surveys found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
