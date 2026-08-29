@include('pdf.credit-note-single-pdf', [
    'creditNote' => $creditNote,
    'invoiceTemplate' => $invoiceTemplate,
    'selectedColumns' => $selectedColumns ?? [],
    'paperCssSize' => $paperCssSize ?? null,
])
