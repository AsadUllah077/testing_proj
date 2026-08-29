<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Products Export</title>
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
        <h1>Warehouse Products Report</h1>
        <p>Generated on: {{ now()->format('d M Y, h:i A') }}</p>
        {{-- @if(!empty($filters))
            @foreach($filters as $key => $val)
                @if($val)<p>{{ ucfirst(str_replace('_',' ',$key)) }}: {{ $val }}</p>@endif
            @endforeach
        @endif --}}
        <p>Total Records: {{ $products->count() }}</p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($products as $i => $product)
                <tr>
                    @foreach($columns as $column)
                        <td>{{ $exporter->valueFor($product, $column, $i + 1) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" style="text-align:center;padding:20px;color:#999">No products found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
</body>
</html>
