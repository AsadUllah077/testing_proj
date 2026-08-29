<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bank Reconciliation Summary</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        h1, h2, h3, h4, p {
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 20px;
        }
        .header h1 { font-size: 20px; margin-bottom: 5px; color: #111; }
        .header h2 { font-size: 16px; margin-bottom: 5px; font-weight: normal; color: #444; }
        .header h3 { font-size: 14px; margin-bottom: 5px; font-weight: normal; color: #666; }
        .header p { font-size: 12px; color: #888; margin-top: 5px; }

        .summary-container {
            width: 100%;
            margin-bottom: 40px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table {
            width: 70%;
            margin: 0 auto;
        }
        .summary-table td {
            padding: 8px 0;
        }
        .text-right {
            text-align: right;
        }
        .text-gray {
            color: #555;
        }
        .font-bold {
            font-weight: bold;
        }
        .border-top {
            border-top: 1px solid #333;
        }
        .border-bottom {
            border-bottom: 1px solid #ddd;
        }
        .pl-4 {
            padding-left: 20px;
        }
        .text-red {
            color: #d32f2f;
        }
        .text-green {
            color: #388e3c;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-top: 30px;
        }
        .details-table th {
            text-align: left;
            padding: 8px 4px;
            border-bottom: 1px solid #ccc;
            color: #555;
            font-weight: bold;
        }
        .details-table td {
            padding: 8px 4px;
            border-bottom: 1px solid #eee;
        }
        .details-table tfoot td {
            border-top: 1px solid #ccc;
            border-bottom: none;
            font-weight: bold;
        }
        .w-32 { width: 15%; }
        .w-48 { width: 25%; }
        .w-amount { width: 15%; text-align: right; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Bank Reconciliation Summary</h1>
        <h2>{{ auth('web')->user()->company->name ?? 'Company Name' }}</h2>
        <h3>{{ $bankAccount->name }}</h3>
        <p>As at {{ date('d M Y') }}</p>
    </div>

    <div class="summary-container">
        <table class="summary-table">
            <tbody>
                <!-- Balance in Xero -->
                <tr>
                    <td class="font-bold">Balance in Xero</td>
                    <td class="text-right font-bold">{{ number_format($ledgerBalance, 2) }}</td>
                </tr>
                
                <!-- Outstanding Payments -->
                @if($outstandingPayments->count() > 0)
                <tr>
                    <td class="text-gray pl-4">Plus Outstanding Payments</td>
                    <td class="text-right text-gray">{{ number_format($outstandingPayments->sum('credit'), 2) }}</td>
                </tr>
                @endif

                <!-- Outstanding Receipts -->
                @if($outstandingReceipts->count() > 0)
                <tr>
                    <td class="text-gray pl-4">Less Outstanding Receipts</td>
                    <td class="text-right text-gray">({{ number_format($outstandingReceipts->sum('debit'), 2) }})</td>
                </tr>
                @endif

                <!-- Unreconciled Statement Lines (Receipts) -->
                @php $unreconciledReceipts = $unreconciledStatementLines->where('amount', '>', 0); @endphp
                @if($unreconciledReceipts->count() > 0)
                <tr>
                    <td class="text-gray pl-4">Plus Unreconciled Statement Lines</td>
                    <td class="text-right text-gray">{{ number_format($unreconciledReceipts->sum('amount'), 2) }}</td>
                </tr>
                @endif

                <!-- Unreconciled Statement Lines (Payments) -->
                @php $unreconciledPayments = $unreconciledStatementLines->where('amount', '<', 0); @endphp
                @if($unreconciledPayments->count() > 0)
                <tr>
                    <td class="text-gray pl-4">Less Unreconciled Statement Lines</td>
                    <td class="text-right text-gray">({{ number_format(abs($unreconciledPayments->sum('amount')), 2) }})</td>
                </tr>
                @endif

                <!-- Statement Balance -->
                <tr>
                    <td class="font-bold border-top" style="padding-top: 10px;">Statement Balance</td>
                    <td class="text-right font-bold border-top" style="padding-top: 10px;">{{ number_format($adjustedBalance, 2) }}</td>
                </tr>

                <!-- Imported Statement Balance -->
                <tr>
                    <td class="text-gray" style="padding-top: 20px;">Imported Statement Balance</td>
                    <td class="text-right text-gray" style="padding-top: 20px;">{{ number_format($statementBalance, 2) }}</td>
                </tr>
                
                <!-- Difference -->
                <tr>
                    <td class="font-bold border-top" style="padding-top: 10px;">Difference</td>
                    <td class="text-right font-bold border-top {{ $variance != 0 ? 'text-red' : 'text-green' }}" style="padding-top: 10px;">{{ number_format($variance, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Outstanding Payments Details -->
    @if($outstandingPayments->count() > 0)
    <div class="section-title">Outstanding Payments</div>
    <table class="details-table">
        <thead>
            <tr>
                <th class="w-32">Date</th>
                <th>Description</th>
                <th class="w-48">Reference</th>
                <th class="w-amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($outstandingPayments as $item)
            <tr>
                <td>{{ $item->date?->format('d M Y') }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->reference_id }}</td>
                <td class="text-right">{{ number_format($item->credit, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">Total</td>
                <td class="text-right">{{ number_format($outstandingPayments->sum('credit'), 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif

    <!-- Outstanding Receipts Details -->
    @if($outstandingReceipts->count() > 0)
    <div class="section-title">Outstanding Receipts</div>
    <table class="details-table">
        <thead>
            <tr>
                <th class="w-32">Date</th>
                <th>Description</th>
                <th class="w-48">Reference</th>
                <th class="w-amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($outstandingReceipts as $item)
            <tr>
                <td>{{ $item->date?->format('d M Y') }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->reference_id }}</td>
                <td class="text-right">{{ number_format($item->debit, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">Total</td>
                <td class="text-right">{{ number_format($outstandingReceipts->sum('debit'), 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif

    <!-- Unreconciled Statement Lines Details -->
    @if($unreconciledStatementLines->count() > 0)
    <div class="section-title">Unreconciled Statement Lines</div>
    <table class="details-table">
        <thead>
            <tr>
                <th class="w-32">Date</th>
                <th>Description</th>
                <th class="w-48">Reference</th>
                <th class="w-amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($unreconciledStatementLines as $item)
            <tr>
                <td>{{ $item->date?->format('d M Y') }}</td>
                <td>{{ $item->payee ? $item->payee . ' - ' : '' }}{{ $item->description }}</td>
                <td>{{ $item->reference }}</td>
                <td class="text-right">{{ number_format($item->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">Total</td>
                <td class="text-right">{{ number_format($unreconciledStatementLines->sum('amount'), 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif

</body>
</html>
