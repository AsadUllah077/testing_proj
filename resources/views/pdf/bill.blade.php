@php
    $invoiceTemplate = $invoiceTemplate ?? new \App\Models\InvoiceTemplate();
    $documentTitle = $invoiceTemplate->approved_invoice_title ?? ($title ?? 'Bill Invoice');
    $currencyCode = $bill->currency->code ?? auth('web')->user()?->company?->currency?->code ?? 'PKR';
@endphp

@include('pdf.partials.sale-bill-invoice', [
    'document' => $bill,
    'party' => $vendor,
    'partyLabel' => 'Vendor',
    'documentTitle' => $documentTitle,
    'currencyCode' => $currencyCode,
])
