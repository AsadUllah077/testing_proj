<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bank Reconciliation</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { margin-bottom: 4px; } .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin: 18px 0; }
        th, td { padding: 7px; border-bottom: 1px solid #ddd; }
        th { background: #f3f4f6; text-align: left; }
        .right { text-align: right; } .total { font-weight: bold; border-top: 2px solid #374151; }
        .ok { color: #047857; } .bad { color: #b91c1c; }
        .signoff { margin-top: 42px; display: table; width: 100%; }
        .signoff > div { display: table-cell; width: 33%; padding-right: 20px; }
        .signoff > div > div { display: block; width: auto; padding-right: 0; }
        .line { border-top: 1px solid #111827; margin-top: 34px; padding-top: 5px; }
    </style>
</head>
<body>
    <h1>Bank Reconciliation Statement</h1>
    <div>{{ auth('web')->user()->company->name ?? 'Company' }} — {{ $bankAccount->name }}</div>
    <div class="muted">
        Cutoff: {{ \Carbon\Carbon::parse($cutoff)->format('d M Y') }}
        @if($statement) | Statement: {{ $statement->statement_number }} | Status: {{ ucfirst($statement->status) }} @endif
    </div>

    <table>
        <tr><th colspan="2">Balance per bank</th></tr>
        <tr><td>Declared statement closing balance</td><td class="right">{{ number_format($declaredClosingBalance, 2) }}</td></tr>
        <tr><td>Plus: deposits in transit</td><td class="right">{{ number_format($depositsInTransit, 2) }}</td></tr>
        <tr><td>Less: outstanding payments</td><td class="right">({{ number_format($outstandingCheques, 2) }})</td></tr>
        <tr class="total"><td>Adjusted bank balance</td><td class="right">{{ number_format($adjustedBankBalance, 2) }}</td></tr>
        <tr><th colspan="2">Balance per books</th></tr>
        <tr><td>Ledger balance</td><td class="right">{{ number_format($ledgerBalance, 2) }}</td></tr>
        <tr><td>Plus: bank-only receipts</td><td class="right">{{ number_format($bankOnlyReceipts, 2) }}</td></tr>
        <tr><td>Less: bank-only payments</td><td class="right">({{ number_format($bankOnlyPayments, 2) }})</td></tr>
        <tr class="total"><td>Adjusted book balance</td><td class="right">{{ number_format($adjustedBookBalance, 2) }}</td></tr>
        <tr class="total"><td>Difference</td><td class="right {{ abs($variance) < 0.005 ? 'ok' : 'bad' }}">{{ number_format($variance, 2) }}</td></tr>
    </table>

    @if($statement)
        <p><strong>Statement control:</strong> Opening {{ number_format($openingBalance, 2) }} + movements {{ number_format($statementMovements, 2) }} = calculated closing {{ number_format($calculatedClosingBalance, 2) }}. Declared closing difference: <span class="{{ abs($statementVariance) < 0.005 ? 'ok' : 'bad' }}">{{ number_format($statementVariance, 2) }}</span>.</p>
    @endif

    @if($unreconciledStatementLines->isNotEmpty())
        <h3>Statement exceptions</h3>
        <table>
            <tr><th>Date</th><th>Payee / description</th><th>Reference</th><th class="right">Remaining</th></tr>
            @foreach($unreconciledStatementLines as $line)
                <tr><td>{{ $line->date->format('d M Y') }}</td><td>{{ $line->payee ?: $line->description }}</td><td>{{ $line->reference }}</td><td class="right">{{ number_format($line->remaining_amount, 2) }}</td></tr>
            @endforeach
        </table>
    @endif

    <div class="signoff">
        <div><div class="line">Prepared by / date</div></div>
        <div><div class="line">Reviewed by / date</div></div>
        <div><div class="line">Approved by / date</div></div>
    </div>
</body>
</html>
