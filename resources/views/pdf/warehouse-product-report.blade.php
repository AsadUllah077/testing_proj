<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Warehouse Product Report</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #263238; font-size: 8px; }
        h1 { margin: 0; color: #0078c8; font-size: 19px; }
        .sub { margin: 3px 0 10px; color: #607d8b; }
        .cards { width: 100%; margin: 8px 0 12px; border-collapse: separate; border-spacing: 5px; }
        .cards td { background: #f4f8fb; border: 1px solid #d9e2ec; padding: 7px; }
        .cards strong { display: block; color: #0f4c81; font-size: 14px; }
        table.data { width: 100%; border-collapse: collapse; }
        .data th { background: #0f4c81; color: white; text-align: left; padding: 5px; font-size: 7px; }
        .data td { border-bottom: 1px solid #d9e2ec; padding: 4px 5px; vertical-align: top; font-size: 7px; }
        .data tr:nth-child(even) td { background: #f8fafc; }
        .right { text-align: right; }
        .filters { background: #eef6fb; border-left: 3px solid #0078c8; padding: 6px 8px; margin: 7px 0; }
        .footer { margin-top: 9px; color: #78909c; text-align: center; }
    </style>
</head>
<body>
    <h1>Warehouse Product Report</h1>
    <div class="sub">Generated {{ now()->format('d M Y, h:i A') }} · Record and unit counts are reported separately.</div>

    @php($activeFilters = collect($filters)->except(['per_page'])->filter(fn($value) => $value !== null && $value !== ''))
    @if($activeFilters->isNotEmpty())
        <div class="filters">
            <strong>Applied filters:</strong>
            {{ $activeFilters->map(fn($value, $key) => ucwords(str_replace('_', ' ', $key)).': '.$value)->implode(' · ') }}
        </div>
    @endif

    <table class="cards">
        <tr>
            <td><strong>{{ number_format($summary['record_count']) }}</strong>Product records</td>
            <td><strong>{{ number_format($summary['total_units']) }}</strong>Inventory units</td>
            <td><strong>{{ number_format($summary['available_units']) }}</strong>Available units</td>
            <td><strong>{{ number_format($summary['issued_units']) }}</strong>Issued units</td>
            <td><strong>{{ number_format($summary['sold_units']) }}</strong>Sold units</td>
            <td><strong>{{ number_format($summary['exception_count']) }}</strong>Exceptions</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Unique ID</th>
                <th>Title</th>
                <th>Category</th>
                <th>Location</th>
                <th>Status</th>
                <th class="right">Total</th>
                <th class="right">Sold</th>
                <th class="right">Remaining</th>
                <th>Workflow</th>
                <th>Disposition</th>
                <th>Technician</th>
                <th class="right">Age</th>
                <th>Last Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                <tr>
                    <td>{{ $product->unique_id }}</td>
                    <td>{{ $product->title }}</td>
                    <td>{{ $product->category?->name ?? '—' }}</td>
                    <td>{{ $product->warehouseLocation?->name ?? '—' }}</td>
                    <td>{{ $product->reporting_status_label }}{{ $product->is_partially_sold ? ' / Partially Sold' : '' }}</td>
                    <td class="right">{{ number_format($product->total_units) }}</td>
                    <td class="right">{{ number_format($product->sold_units) }}</td>
                    <td class="right">{{ number_format($product->remaining_units) }}</td>
                    <td>{{ $product->workflowStage?->name ?? '—' }}</td>
                    <td>{{ $product->disposition?->name ?? '—' }}</td>
                    <td>{{ $product->assignedTo?->full_name ?? 'Unassigned' }}</td>
                    <td class="right">{{ number_format($product->age_days) }}d</td>
                    <td>{{ $product->last_action ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="13" style="text-align:center;padding:18px">No products match the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Company-scoped computer-generated report.</div>
</body>
</html>
