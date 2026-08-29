@php
    $invoiceTemplate = $invoiceTemplate ?? new \App\Models\InvoiceTemplate();
    $company = $company ?? auth('web')->user()?->company;
    $document = $document ?? null;
    $party = $party ?? null;
    $partyLabel = $partyLabel ?? 'Customer';
    $documentTitle = $documentTitle ?? ($invoiceTemplate->approved_invoice_title ?? 'Invoice');
    $currencyCode = $currencyCode ?? ($document?->currency?->code ?? auth('web')->user()?->company?->currency?->code ?? 'PKR');
    $selectedColumns = $selectedColumns ?? [];
    $showColumn = fn($key) => empty($selectedColumns) || in_array($key, $selectedColumns, true);
    $dateLabel = $dateLabel ?? 'Invoice Date';
    $numberLabel = $numberLabel ?? 'Invoice Number';
    $totalLabel = $totalLabel ?? ('TOTAL ' . $currencyCode);
    $dueDateLabel = $dueDateLabel ?? 'Due Date';
    $amountDueLabel = $amountDueLabel ?? 'Amount Due';
    $showDueDate = $showDueDate ?? true;
    $showTerms = $showTerms ?? true;
    $showPaymentAdvice = $showPaymentAdvice ?? ($invoiceTemplate->show_payment_advance_cutaway ?? true);
    $showPaidStamp = $showPaidStamp ?? true;
    $emptyTermsMessage = $emptyTermsMessage ?? 'No invoice terms and conditions configured.';

    if (!isset($appliedCreditNotes)) {
        $appliedCreditNotes = collect();

        if ($document && method_exists($document, 'creditNotes')) {
            $appliedCreditNotes = $document->relationLoaded('creditNotes')
                ? $document->creditNotes
                : $document->creditNotes()->get();
        }
    }

    if (!isset($appliedDebitNotes)) {
        $appliedDebitNotes = collect();

        if ($document && method_exists($document, 'debitNotes')) {
            $appliedDebitNotes = $document->relationLoaded('debitNotes')
                ? $document->debitNotes
                : $document->debitNotes()->get();
        }
    }

    $appliedCreditNotes = collect($appliedCreditNotes)
        ->filter(fn($creditNote) => (float) ($creditNote->allocated_amount ?? $creditNote->total_after_discount ?? $creditNote->total ?? 0) > 0)
        ->values();
    $appliedCreditTotal = (float) $appliedCreditNotes->sum(fn($creditNote) => (float) ($creditNote->allocated_amount ?? $creditNote->total_after_discount ?? $creditNote->total ?? 0));

    $appliedDebitNotes = collect($appliedDebitNotes)
        ->filter(fn($debitNote) => (float) ($debitNote->allocated_amount ?? $debitNote->total_after_discount ?? $debitNote->total ?? 0) > 0)
        ->values();
    $appliedDebitTotal = (float) $appliedDebitNotes->sum(fn($debitNote) => (float) ($debitNote->allocated_amount ?? $debitNote->total_after_discount ?? $debitNote->total ?? 0));

    $displayPaidAmount = (float) ($paid_amount ?? 0);
    $displayBalanceDue = array_key_exists('remaining_amount', get_defined_vars())
        ? max(0, (float) $remaining_amount)
        : max(0, (float) ($total ?? 0) - $appliedCreditTotal - $appliedDebitTotal - $displayPaidAmount);
    $showAppliedCreditNotes = $showAppliedCreditNotes ?? ($appliedCreditNotes->isNotEmpty() && $showColumn('credit_notes'));
    $showAppliedDebitNotes = $showAppliedDebitNotes ?? ($appliedDebitNotes->isNotEmpty() && (!isset($selectedColumns) || empty($selectedColumns) || in_array('debit_notes', $selectedColumns, true) || in_array('credit_notes', $selectedColumns, true)));

    $formatDate = function ($value, $format = 'j M Y') {
        if (!$value) {
            return 'N/A';
        }

        try {
            return $value instanceof \Carbon\Carbon
                ? $value->format($format)
                : \Carbon\Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $rawDate = $document->date ?? null;
    $dueDateValue = $document->due_date ?? null;

    if (!$dueDateValue && $rawDate) {
        try {
            $dueDateValue = \Carbon\Carbon::parse($rawDate)->addDays(30);
        } catch (\Throwable $e) {
            $dueDateValue = null;
        }
    }

    $displayDate = $formatDate($rawDate);
    $displayDueDate = $formatDate($dueDateValue);

    $normalizeLines = function (array $lines): array {
        $normalized = [];

        foreach ($lines as $line) {
            foreach (preg_split('/\r\n|\r|\n/', (string) $line) as $part) {
                $part = trim($part);

                if ($part !== '') {
                    $normalized[] = $part;
                }
            }
        }

        return $normalized;
    };

    $companyLines = $normalizeLines(array_filter([
        $invoiceTemplate->contact_details ?? null,
        $company?->phone ? 'Phone: ' . $company->phone : null,
        $company?->mobile ? 'Mobile: ' . $company->mobile : null,
        $company?->email ? 'Email: ' . $company->email : null,
        $company?->state?->name ?? null,
        $company?->country?->name ?? null,
    ]));

    $partyLines = $normalizeLines(array_filter([
        $party?->billing_address ?? $party?->address ?? null,
        $party?->phone ? 'Phone: ' . $party->phone : null,
        $party?->email ? 'Email: ' . $party->email : null,
    ]));

    $termsTitle = $termsTitleOverride ?? 'Invoice Terms and Conditions';
    $termsContent = array_key_exists('termsContentOverride', get_defined_vars())
        ? trim((string) $termsContentOverride)
        : trim((string) ($invoiceTemplate->terms_payment_advice ?? ''));
    $plainTextAsList = false;

    if ($termsContent === '' && !array_key_exists('termsContentOverride', get_defined_vars())) {
        $invoiceTerm = \App\Models\InvoiceTermCondition::defaultForCompany($company?->id ?? auth('web')->user()?->company_id);

        if ($invoiceTerm) {
            $termsTitle = $invoiceTerm->title;
            $termsContent = $invoiceTerm->content;
            $plainTextAsList = true;
        }
    }

    $termsContent = \App\Models\InvoiceTermCondition::sanitizeContent($termsContent, $plainTextAsList);

    $paymentAdviceLines = $normalizeLines(array_filter([
        $invoiceTemplate->payment_bank ? 'Bank: ' . $invoiceTemplate->payment_bank : null,
        $invoiceTemplate->payment_credit_card ? 'Credit Card: ' . $invoiceTemplate->payment_credit_card : null,
        $invoiceTemplate->payment_paypal ? 'PayPal: ' . $invoiceTemplate->payment_paypal : null,
    ]));
    $paymentAdviceText = !empty($paymentAdviceLines)
        ? implode('<br>', array_map('e', $paymentAdviceLines))
        : 'Please include the invoice number with your payment.';
    $paymentAmount = $displayBalanceDue;
    $electronicInvoiceQr = null;
    if (!empty($document?->electronic_invoice_qr_payload)) {
        try {
            $electronicInvoiceQr = (new \Milon\Barcode\DNS2D)->getBarcodePNG($document->electronic_invoice_qr_payload, 'QRCODE', 4, 4);
        } catch (\Throwable $e) {
            $electronicInvoiceQr = null;
        }
    }
@endphp

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $documentTitle }} - {{ $document->invoice_no ?? '' }}</title>
    <style>
        @page {
            size: {{ $paperCssSize ?? \App\Support\PdfPaperSize::cssSize(null) }};
            margin: {{ $invoiceTemplate->top_margin ?? '0.65' }}{{ $invoiceTemplate->measures_in ?? 'cm' }} 0.75{{ $invoiceTemplate->measures_in ?? 'cm' }} {{ $invoiceTemplate->bottom_margin ?? '0.65' }}{{ $invoiceTemplate->measures_in ?? 'cm' }} 0.75{{ $invoiceTemplate->measures_in ?? 'cm' }};
        }

        body {
            color: #111;
            font-family: {{ $invoiceTemplate->font ?? 'Arial' }}, sans-serif;
            font-size: {{ $invoiceTemplate->font_size ?? '9pt' }};
            line-height: 1.25;
            margin: 0;
        }

        .page {
            width: 100%;
        }

        .top-table,
        .items-table,
        .summary-table,
        .payment-table,
        .advice-details {
            border-collapse: collapse;
            width: 100%;
        }

        .top-table td {
            vertical-align: top;
        }

        .title {
            font-size: 30px;
            font-weight: 400;
            letter-spacing: 0;
            margin: 44px 0 10px;
            text-transform: uppercase;
        }

        .party-name {
            font-size: 12px;
            margin-top: 6px;
        }

        .company-box {
            font-size: 12px;
            line-height: 1.25;
            text-align: left;
        }

        .company-box strong,
        .party-name strong {
            font-weight: 700;
        }

        .meta-box {
            font-size: 12px;
            padding-top: 47px;
        }

        .meta-row {
            margin-bottom: 10px;
        }

        .meta-label {
            font-weight: 700;
        }

        .reference-value {
            color: #111;
        }

        .items-table {
            margin-top: 104px;
        }

        .items-table th {
            border-bottom: 1px solid #111;
            font-weight: 700;
            padding: 0 4px 7px;
            text-align: left;
        }

        .items-table td {
            border-bottom: 1px solid #d8d8d8;
            padding: 9px 4px;
            vertical-align: top;
        }

        .items-table tr,
        .terms-box,
        .cut-line,
        .payment-title,
        .payment-table {
            page-break-inside: avoid;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary-table {
            margin-left: auto;
            margin-top: 4px;
            width: 44%;
        }

        .summary-table td {
            padding: 6px 4px;
        }

        .summary-table .divider td {
            border-top: 1px solid #111;
        }

        .summary-table .grand td {
            font-weight: 700;
            padding-top: 8px;
        }

        .due-date {
            font-size: 15px;
            font-weight: 700;
            margin-top: 60px;
        }

        .terms-box {
            border: 1px solid #cfcfcf;
            margin-top: 24px;
            min-height: 118px;
            padding: 14px 16px;
        }

        .terms-title {
            font-weight: 700;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .terms-content {
            font-size: 11px;
        }

        .terms-content ol,
        .terms-content ul {
            margin: 0 0 0 18px;
            padding: 0;
        }

        .terms-content li {
            margin-bottom: 4px;
        }

        .cut-line {
            border-top: 1px dashed #444;
            margin: 44px 0 10px;
            position: relative;
        }

        .cut-line span {
            background: #fff;
            font-size: 18px;
            left: 0;
            padding-right: 5px;
            position: relative;
            top: -12px;
        }

        .payment-title {
            font-size: 29px;
            font-weight: 400;
            letter-spacing: 0;
            margin-bottom: 4px;
        }

        .payment-table td {
            vertical-align: top;
        }

        .payment-to {
            padding-left: 18px;
            width: 52%;
        }

        .payment-to-address {
            margin-bottom: 10px;
        }

        .payment-advice-box {
            border: 1px solid #cfcfcf;
            margin-top: 16px;
            min-height: 78px;
            padding: 12px 14px;
            width: 88%;
        }

        .payment-advice-box .label {
            font-weight: 700;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .payment-advice-content {
            font-size: 11px;
            line-height: 1.35;
        }

        .advice-details {
            font-size: 12px;
        }

        .advice-details td {
            border-bottom: 1px solid #ddd;
            padding: 4px 0;
        }

        .advice-details .label {
            font-weight: 700;
            width: 43%;
        }

        .advice-party-details {
            font-size: 10px;
            line-height: 1.25;
            margin-top: 2px;
        }

        .amount-line {
            border-bottom: 1px solid #111;
            height: 22px;
            margin-top: 4px;
        }

        .amount-help {
            font-size: 8px;
            text-align: center;
        }

        .paid-stamp {
            border: 3px solid #4CAF50;
            color: #4CAF50;
            font-size: 28px;
            font-weight: bold;
            opacity: 0.24;
            padding: 8px 18px;
            position: absolute;
            right: 60px;
            top: 145px;
            transform: rotate(-15deg);
        }
    </style>
</head>

<body>
    @if ($showPaidStamp && (float) ($remaining_amount ?? 0) <= 0)
        <div class="paid-stamp">PAID</div>
    @endif

    <div class="page">
        <table class="top-table">
            <tr>
                <td style="width: 58%;">
                    <div class="title">{{ $documentTitle }}</div>
                    <div class="party-name">
                        <strong>{{ $party?->name ?? 'N/A' }}</strong>
                        @if (!empty($partyLines))
                            <br>{!! implode('<br>', array_map('e', $partyLines)) !!}
                        @endif
                    </div>
                </td>
                <td style="width: 18%;">
                    <div class="meta-box">
                        @if ($showColumn('date'))
                            <div class="meta-row">
                                <div class="meta-label">{{ $dateLabel }}</div>
                                <div>{{ $displayDate }}</div>
                            </div>
                        @endif
                        @if ($showColumn('invoice_no'))
                            <div class="meta-row">
                                <div class="meta-label">{{ $numberLabel }}</div>
                                <div>{{ $document->invoice_no ?? 'N/A' }}</div>
                            </div>
                        @endif
                        @if ($showColumn('ref_no'))
                            <div class="meta-row">
                                <div class="meta-label">Reference</div>
                                <div class="reference-value">{{ $document->ref_no ?? 'N/A' }}</div>
                            </div>
                        @endif
                        @if (!empty($document->customer_po_no))
                            <div class="meta-row">
                                <div class="meta-label">Customer PO</div>
                                <div class="reference-value">{{ $document->customer_po_no }}</div>
                            </div>
                        @endif
                        @if (!empty($document->purchase_order_no))
                            <div class="meta-row"><div class="meta-label">Purchase Order</div><div>{{ $document->purchase_order_no }}</div></div>
                        @endif
                        @if (!empty($document->goods_receipt_no))
                            <div class="meta-row"><div class="meta-label">GRN</div><div>{{ $document->goods_receipt_no }}</div></div>
                        @endif
                    </div>
                </td>
                <td style="width: 24%;">
                    <div class="company-box" style="padding-top: 47px;">
                        <strong>{{ $company?->name ?? auth('web')->user()?->company?->name ?? 'Company Name' }}</strong>
                        @if (!empty($companyLines))
                            <br>{!! implode('<br>', array_map('e', $companyLines)) !!}
                        @endif
                        @if (!empty($document->tax_invoice_reference))
                            <br><strong>Tax Ref:</strong> {{ $document->tax_invoice_reference }}
                        @endif
                        @if ($electronicInvoiceQr)
                            <br><img src="data:image/png;base64,{{ $electronicInvoiceQr }}" alt="Electronic invoice QR" style="width:72px;height:72px;margin-top:6px;">
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right" style="width: 13%;">Quantity</th>
                    <th class="text-right" style="width: 14%;">Unit Price</th>
                    <th class="text-center" style="width: 12%;">Tax</th>
                    <th class="text-right" style="width: 16%;">Amount {{ $currencyCode }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php
                        $quantity = ($item->kg ?? 0) > 0 ? $item->kg : ($item->quantity ?? 0);
                        $taxRate = $item->taxRate ?? $item->tax ?? null;
                        $taxLabel = $item->tax_name_snapshot
                            ? $item->tax_name_snapshot . ' (' . number_format((float) $item->tax_rate_snapshot, 2) . '%)'
                            : ($taxRate ? (($taxRate->name ?? 'Tax') . ' (' . number_format((float) ($taxRate->tax_rate ?? 0), 2) . '%)') : null);
                    @endphp
                    <tr>
                        <td>
                            {{ $item->description ?: ($item->item ?: ($item->manual_item ?: 'Item')) }}
                            @if ($showColumn('account') && $item->account)
                                <br><small>Account: {{ $item->account->name }}</small>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format((float) $quantity, 2) }} {{ $item->unit_of_measure ?? '' }}</td>
                        <td class="text-right">{{ number_format((float) ($item->price ?? $item->unit_price ?? 0), 2) }}</td>
                        <td class="text-center">
                            @if ($showColumn('tax') && $taxLabel)
                                {{ $taxLabel }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right">{{ number_format((float) ($item->total ?? 0), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary-table">
            @if ($showColumn('sub_total'))
                <tr>
                    <td>Subtotal</td>
                    <td class="text-right">{{ number_format((float) ($subtotal ?? 0), 2) }}</td>
                </tr>
            @endif
            @if ($showColumn('tax'))
                <tr>
                    <td>Total Tax</td>
                    <td class="text-right">{{ number_format((float) ($tax ?? 0), 2) }}</td>
                </tr>
            @endif
            @if ($showColumn('discount') && (float) ($discount ?? 0) > 0)
                <tr>
                    <td>Discount</td>
                    <td class="text-right">-{{ number_format((float) $discount, 2) }}</td>
                </tr>
            @endif
            @if ((float) ($document->withholding_tax ?? 0) > 0)
                <tr>
                    <td>Withholding Tax</td>
                    <td class="text-right">-{{ number_format((float) $document->withholding_tax, 2) }}</td>
                </tr>
            @endif
            @if ($showColumn('total'))
                <tr class="divider grand">
                    <td>{{ $totalLabel }}</td>
                    <td class="text-right">{{ number_format((float) ($total ?? 0), 2) }}</td>
                </tr>
            @endif
            @if ($showAppliedCreditNotes)
                @foreach ($appliedCreditNotes as $creditNote)
                    <tr>
                        <td>
                            Credit Note {{ $creditNote->invoice_no ?? $creditNote->unique_no ?? $creditNote->id }}
                            @if ($creditNote->date)
                                <br><small>{{ $formatDate($creditNote->date) }}</small>
                            @endif
                        </td>
                        <td class="text-right">-{{ number_format((float) ($creditNote->allocated_amount ?? $creditNote->total_after_discount ?? $creditNote->total ?? 0), 2) }}</td>
                    </tr>
                @endforeach
            @endif
            @if ($showAppliedDebitNotes)
                @foreach ($appliedDebitNotes as $debitNote)
                    <tr>
                        <td>
                            Debit Note {{ $debitNote->unique_no ?? $debitNote->id }}
                            @if ($debitNote->date)
                                <br><small>{{ $formatDate($debitNote->date) }}</small>
                            @endif
                        </td>
                        <td class="text-right">-{{ number_format((float) ($debitNote->allocated_amount ?? $debitNote->total_after_discount ?? $debitNote->total ?? 0), 2) }}</td>
                    </tr>
                @endforeach
            @endif
            @if ($showColumn('paid_amount') && $displayPaidAmount > 0)
                <tr>
                    <td>Amount Paid</td>
                    <td class="text-right">{{ number_format($displayPaidAmount, 2) }}</td>
                </tr>
            @endif
            @if (($showAppliedCreditNotes || $showAppliedDebitNotes || $displayPaidAmount > 0) && $showColumn('remaining_amount'))
                <tr class="divider grand">
                    <td>Balance Due {{ $currencyCode }}</td>
                    <td class="text-right">{{ number_format($displayBalanceDue, 2) }}</td>
                </tr>
            @endif
        </table>
        <div style="clear: both;"></div>

        @if ($showDueDate)
            <div class="due-date">{{ $dueDateLabel }}: {{ $displayDueDate }}</div>
        @endif

        @if ($showTerms)
            <div class="terms-box">
                <div class="terms-title">{{ $termsTitle }}</div>
                <div class="terms-content">
                    @if ($termsContent !== '')
                        {!! $termsContent !!}
                    @else
                        {{ $emptyTermsMessage }}
                    @endif
                </div>
            </div>
        @endif

        @if ($showPaymentAdvice)
            <div class="cut-line"><span>&#9986;</span></div>
            <div class="payment-title">PAYMENT ADVICE</div>
            <table class="payment-table">
                <tr>
                    <td class="payment-to">
                        <div class="payment-to-address">
                            To: {{ $company?->name ?? 'Company Name' }}<br>
                            @if (!empty($companyLines))
                                {!! implode('<br>', array_map('e', $companyLines)) !!}
                            @endif
                        </div>

                        <div class="payment-advice-box">
                            <div class="label">Payment Advice</div>
                            <div class="payment-advice-content">{!! $paymentAdviceText !!}</div>
                        </div>
                    </td>
                    <td style="width: 48%;">
                        <table class="advice-details">
                            <tr>
                                <td class="label">{{ $partyLabel }}</td>
                                <td>
                                    <strong>{{ $party?->name ?? 'N/A' }}</strong>
                                    @if (!empty($partyLines))
                                        <div class="advice-party-details">{!! implode('<br>', array_map('e', $partyLines)) !!}</div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="label">{{ $numberLabel }}</td>
                                <td>{{ $document->invoice_no ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="label">{{ $amountDueLabel }}</td>
                                <td>{{ number_format((float) $paymentAmount, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="label">{{ $dueDateLabel }}</td>
                                <td>{{ $displayDueDate }}</td>
                            </tr>
                            <tr>
                                <td class="label">Amount Enclosed</td>
                                <td>
                                    <div class="amount-line"></div>
                                    <div class="amount-help">Enter the amount you are paying above</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        @endif
    </div>
</body>

</html>
