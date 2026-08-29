@php
    $termsTitle = 'Terms & Payment Advice';
    $termsContent = trim((string) ($invoiceTemplate->terms_payment_advice ?? ''));
    $plainTextAsList = false;

    if ($termsContent === '') {
        $companyId = auth('web')->user()?->company_id ?? auth()->user()?->company_id ?? null;
        $invoiceTerm = \App\Models\InvoiceTermCondition::defaultForCompany($companyId);

        if ($invoiceTerm) {
            $termsTitle = $invoiceTerm->title;
            $termsContent = $invoiceTerm->content;
            $plainTextAsList = true;
        }
    }

    $termsContent = \App\Models\InvoiceTermCondition::sanitizeContent($termsContent, $plainTextAsList);
@endphp

@if ($termsContent !== '')
    <div class="terms-section">
        <strong>{{ $termsTitle }}</strong>
        <div class="terms-content">{!! $termsContent !!}</div>
    </div>
@endif
