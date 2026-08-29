@php
    $invoiceTemplate = $invoiceTemplate ?? new \App\Models\InvoiceTemplate();
    $company = $creditNote->company ?? auth('web')->user()?->company;
    $currencyCode = $creditNote->currency->code ?? (auth('web')->user()?->company?->currency?->code ?? 'PKR');
    $selectedColumns = $selectedColumns ?? [];
    $totalCredit = $creditNote->total_after_discount ?? ($creditNote->total ?? 0);
    $showCreditNoteRemarks = empty($selectedColumns) || in_array('note', $selectedColumns, true);
@endphp

@include('pdf.partials.sale-bill-invoice', [
    'document' => $creditNote,
    'party' => $creditNote->customer,
    'partyLabel' => 'Customer',
    'documentTitle' => $invoiceTemplate->credit_note_title ?? 'Credit Note',
    'currencyCode' => $currencyCode,
    'company' => $company,
    'items' => $creditNote->creditNoteItems,
    'subtotal' => $creditNote->sub_total,
    'discount' => $creditNote->discount ?? 0,
    'tax' => $creditNote->tax,
    'total' => $totalCredit,
    'remaining_amount' => $creditNote->remaining_amount,
    'invoiceTemplate' => $invoiceTemplate,
    'selectedColumns' => $selectedColumns,
    'paperCssSize' => $paperCssSize ?? null,
    'dateLabel' => 'Credit Note Date',
    'numberLabel' => 'Credit Note Number',
    'totalLabel' => 'TOTAL CREDIT ' . $currencyCode,
    'termsTitleOverride' => 'Credit Note Remarks',
    'termsContentOverride' => $creditNote->note ?? '',
    'emptyTermsMessage' => 'No credit note remarks added.',
    'showTerms' => $showCreditNoteRemarks,
    'showDueDate' => false,
    'showPaymentAdvice' => false,
    'showPaidStamp' => false,
])
