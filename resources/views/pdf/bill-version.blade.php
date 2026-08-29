<!doctype html>
<html><head><meta charset="utf-8"><title>Bill version {{ $version->version }}</title>
<style>body{font-family:Arial,sans-serif;font-size:11px;color:#222}h1{margin-bottom:4px}.meta{color:#666;margin-bottom:20px}table{width:100%;border-collapse:collapse;margin-top:16px}th,td{padding:7px;border-bottom:1px solid #ddd;text-align:left}.right{text-align:right}.summary{width:42%;margin-left:auto}.summary td:first-child{font-weight:bold}</style></head>
<body>
@php($header = $snapshot['bill'] ?? [])
<h1>{{ $company->name ?? 'Company' }} — Purchase Bill</h1>
<div class="meta">Immutable version {{ $version->version }} · {{ ucfirst($version->event) }} · {{ $version->created_at }}</div>
<table>
    <tr><th>Invoice</th><td>{{ $header['invoice_no'] ?? 'N/A' }}</td><th>Internal no.</th><td>{{ $header['unique_no'] ?? 'N/A' }}</td></tr>
    <tr><th>Date</th><td>{{ $header['date'] ?? 'N/A' }}</td><th>Due date</th><td>{{ $header['due_date'] ?? 'N/A' }}</td></tr>
    <tr><th>Approval</th><td>{{ ucfirst($header['approval_status'] ?? 'draft') }}</td><th>Payment</th><td>{{ ucfirst(str_replace('_', ' ', $header['payment_status'] ?? 'unpaid')) }}</td></tr>
</table>
<table><thead><tr><th>Item</th><th>Description</th><th class="right">Quantity</th><th class="right">Price</th><th class="right">Tax</th><th class="right">Total</th></tr></thead><tbody>
@foreach (($snapshot['items'] ?? []) as $item)
<tr><td>{{ $item['item'] ?? $item['manual_item'] ?? 'Item' }}</td><td>{{ $item['description'] ?? '' }}</td><td class="right">{{ number_format((float) (($item['kg'] ?? 0) > 0 ? $item['kg'] : ($item['quantity'] ?? 0)), 2) }} {{ $item['unit_of_measure'] ?? '' }}</td><td class="right">{{ number_format((float) ($item['price'] ?? 0), 2) }}</td><td class="right">{{ number_format((float) ($item['tax_amount'] ?? 0), 2) }}</td><td class="right">{{ number_format((float) ($item['total'] ?? 0), 2) }}</td></tr>
@endforeach
</tbody></table>
<table class="summary"><tr><td>Subtotal</td><td class="right">{{ number_format((float) ($header['sub_total'] ?? 0), 2) }}</td></tr><tr><td>Discount</td><td class="right">-{{ number_format((float) ($header['discount_total'] ?? 0), 2) }}</td></tr><tr><td>Tax</td><td class="right">{{ number_format((float) ($header['tax'] ?? 0), 2) }}</td></tr><tr><td>Withholding</td><td class="right">-{{ number_format((float) ($header['withholding_tax'] ?? 0), 2) }}</td></tr><tr><td>Payable</td><td class="right"><strong>{{ number_format((float) ($header['total_after_discount'] ?? 0), 2) }}</strong></td></tr></table>
</body></html>
