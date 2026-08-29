@include('pdf.debit-note-single-pdf', [
    'debitNote' => $debitNote,
    'invoiceTemplate' => $invoiceTemplate,
    'selectedColumns' => $selectedColumns ?? [],
    'paperCssSize' => $paperCssSize ?? null,
])
