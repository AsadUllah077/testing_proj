<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Projects Export</title>
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
        .badge-planning  { background: #dbeafe; color: #1e40af; }
        .badge-active    { background: #d1fae5; color: #065f46; }
        .badge-on-hold   { background: #fef3c7; color: #92400e; }
        .badge-completed { background: #e0e7ff; color: #3730a3; }
        .badge-low      { background: #f0fdf4; color: #166534; }
        .badge-medium   { background: #fffbeb; color: #92400e; }
        .badge-high     { background: #fff7ed; color: #9a3412; }
        .badge-critical { background: #fef2f2; color: #991b1b; }
        .progress-bar { background: #e5e7eb; border-radius: 4px; height: 6px; width: 80px; display: inline-block; vertical-align: middle; }
        .progress-fill { background: #0078C8; height: 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Projects Report</h1>
    <div class="meta">Generated: {{ now()->format('d M Y, g:i A') }} &nbsp;|&nbsp; Total: {{ $projects->count() }} projects</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Project Name</th>
                <th>Client</th>
                <th>Team</th>
                <th>Manager</th>
                <th>Timeline</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Progress</th>
            </tr>
        </thead>
        <tbody>
            @forelse($projects as $i => $project)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $project->name }}</strong></td>
                <td>{{ $project->clientRecord?->name ?? $project->client ?? '—' }}</td>
                <td>{{ $project->teams->first()?->name ?? '—' }}</td>
                <td>{{ $project->manager?->full_name ?? '—' }}</td>
                <td>
                    @if($project->start_date)
                        {{ $project->start_date->format('d M Y') }}
                        @if($project->end_date)<br>{{ $project->end_date->format('d M Y') }}@endif
                    @else —
                    @endif
                </td>
                <td><span class="badge badge-{{ $project->priority }}">{{ ucfirst($project->priority) }}</span></td>
                <td><span class="badge badge-{{ $project->status }}">{{ ucfirst(str_replace('-', ' ', $project->status)) }}</span></td>
                <td>
                    <div class="progress-bar"><div class="progress-fill" style="width:{{ $project->progress }}%"></div></div>
                    {{ $project->progress }}%
                </td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center;color:#9ca3af;padding:20px;">No projects found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
