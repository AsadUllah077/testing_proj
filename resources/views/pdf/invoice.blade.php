@php
    $invoiceTemplate = $invoiceTemplate ?? new \App\Models\InvoiceTemplate();
    $documentTitle = $invoiceTemplate->approved_invoice_title ?? ($title ?? 'Invoice');
    $currencyCode = $sale->currency->code ?? auth('web')->user()?->company?->currency?->code ?? 'PKR';
@endphp

@include('pdf.partials.sale-bill-invoice', [
    'document' => $sale,
    'party' => $customer,
    'partyLabel' => 'Customer',
    'documentTitle' => $documentTitle,
    'currencyCode' => $currencyCode,
])
