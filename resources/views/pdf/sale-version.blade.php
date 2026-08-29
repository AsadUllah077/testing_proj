<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice Version {{ $version->version }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        h1 { margin-bottom: 4px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 22px; }
        th, td { border: 1px solid #d1d5db; padding: 7px; }
        th { background: #f3f4f6; text-align: left; }
        .right { text-align: right; }
        .totals { width: 42%; margin-left: auto; }
    </style>
</head>
<body>
@php($invoice = $snapshot['invoice'] ?? [])
@php($customer = $snapshot['customer'] ?? [])
@php($items = $snapshot['items'] ?? [])
<h1>{{ $company->name }} — Sale Invoice</h1>
<div class="muted">Immutable version {{ $version->version }} captured {{ $version->created_at->format('M d, Y H:i') }}</div>
<p><strong>Invoice:</strong> {{ $invoice['invoice_no'] ?? 'N/A' }}<br>
<strong>Date:</strong> {{ $invoice['date'] ?? 'N/A' }} &nbsp; <strong>Due:</strong> {{ $invoice['due_date'] ?? 'N/A' }}<br>
<strong>Customer:</strong> {{ $customer['name'] ?? 'N/A' }}<br>
<strong>Customer PO:</strong> {{ $invoice['customer_po_no'] ?? 'N/A' }}</p>

<table>
    <thead><tr><th>Description</th><th>Quantity/UOM</th><th class="right">Price</th><th class="right">Discount</th><th class="right">Tax</th><th class="right">Total</th></tr></thead>
    <tbody>
    @foreach ($items as $item)
        <tr>
            <td>{{ $item['description'] ?: ($item['item'] ?: ($item['manual_item'] ?? 'Item')) }}</td>
            <td>{{ ($item['kg'] ?? 0) > 0 ? $item['kg'] : ($item['quantity'] ?? 0) }} {{ $item['unit_of_measure'] ?? '' }}</td>
            <td class="right">{{ number_format((float) ($item['price'] ?? 0), 2) }}</td>
            <td class="right">{{ number_format((float) ($item['discount_amount'] ?? 0), 2) }}</td>
            <td class="right">{{ number_format((float) ($item['tax_amount'] ?? 0), 2) }}</td>
            <td class="right">{{ number_format((float) ($item['total'] ?? 0), 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totals">
    <tr><th>Subtotal</th><td class="right">{{ number_format((float) ($invoice['sub_total'] ?? 0), 2) }}</td></tr>
    <tr><th>Discount</th><td class="right">-{{ number_format((float) ($invoice['discount_total'] ?? 0), 2) }}</td></tr>
    <tr><th>Tax</th><td class="right">{{ number_format((float) ($invoice['tax'] ?? 0), 2) }}</td></tr>
    <tr><th>Grand Total</th><td class="right"><strong>{{ number_format((float) ($invoice['grand_total'] ?? $invoice['total_after_discount'] ?? 0), 2) }}</strong></td></tr>
</table>
</body>
</html>
