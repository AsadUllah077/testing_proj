@php
    $invoiceTemplate = $invoiceTemplate ?? new \App\Models\InvoiceTemplate();
    $company = $debitNote->company ?? auth('web')->user()?->company;
    $currencyCode = $debitNote->vendor?->currency?->code ?? (auth('web')->user()?->company?->currency?->code ?? 'PKR');
    $selectedColumns = $selectedColumns ?? [];
    $totalDebit = $debitNote->total ?? 0;
    $showDebitNoteRemarks = empty($selectedColumns) || in_array('note', $selectedColumns, true);
@endphp

@include('pdf.partials.sale-bill-invoice', [
    'document' => $debitNote,
    'party' => $debitNote->vendor,
    'partyLabel' => 'Vendor',
    'documentTitle' => $invoiceTemplate->debit_note_title ?? 'Debit Note',
    'currencyCode' => $currencyCode,
    'company' => $company,
    'items' => $debitNote->items,
    'subtotal' => $debitNote->sub_total,
    'discount' => $debitNote->discount ?? 0,
    'tax' => $debitNote->tax,
    'total' => $totalDebit,
    'remaining_amount' => $debitNote->remaining_amount,
    'invoiceTemplate' => $invoiceTemplate,
    'selectedColumns' => $selectedColumns,
    'paperCssSize' => $paperCssSize ?? null,
    'dateLabel' => 'Debit Note Date',
    'numberLabel' => 'Debit Note Number',
    'totalLabel' => 'TOTAL DEBIT ' . $currencyCode,
    'termsTitleOverride' => 'Debit Note Remarks',
    'termsContentOverride' => $debitNote->note ?? '',
    'emptyTermsMessage' => 'No debit note remarks added.',
    'showTerms' => $showDebitNoteRemarks,
    'showDueDate' => false,
    'showPaymentAdvice' => false,
    'showPaidStamp' => false,
])
