<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clients Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 20px; }
        h1 { font-size: 16px; color: #0078C8; margin-bottom: 4px; }
        .meta { font-size: 10px; color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        thead { background-color: #0078C8; color: #fff; }
        thead th { padding: 7px 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
        tbody tr:nth-child(even) { background-color: #f3f4f6; }
        tbody td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        .badge { display: inline-block; padding: 2px 7px; border-radius: 9999px; font-size: 9px; font-weight: 600; }
        .badge-active   { background: #d1fae5; color: #065f46; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .badge-company  { background: #dbeafe; color: #1e40af; }
        .badge-individual { background: #f3e8ff; color: #6b21a8; }
    </style>
</head>
<body>
    <h1>Clients Report</h1>
    <div class="meta">Generated: {{ now()->format('d M Y, g:i A') }} &nbsp;|&nbsp; Total: {{ $clients->count() }} clients</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Client Name</th>
                <th>Email</th>
                <th>Type</th>
                <th>Contact</th>
                <th>Industry</th>
                <th>Location</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($clients as $i => $client)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $client->name }}</strong></td>
                <td>{{ $client->email ?? '—' }}</td>
                <td><span class="badge badge-{{ $client->type }}">{{ ucfirst($client->type) }}</span></td>
                <td>
                    {{ $client->contact_person ?? '' }}
                    @if($client->contact_phone ?? $client->phone)
                        <br><small>{{ $client->contact_phone ?? $client->phone }}</small>
                    @endif
                </td>
                <td>{{ $client->industry ?? '—' }}</td>
                <td>{{ collect([$client->city, $client->country])->filter()->implode(', ') ?: '—' }}</td>
                <td><span class="badge badge-{{ $client->status }}">{{ ucfirst($client->status) }}</span></td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:20px;">No clients found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
