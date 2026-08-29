<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: {{ $invoiceTemplate->top_margin ?? 40 }}px {{ $invoiceTemplate->address_padding ?? 30 }}px {{ $invoiceTemplate->bottom_margin ?? 40 }}px;
        }

        body {
            font-family: {{ $invoiceTemplate->font ?? 'DejaVu Sans' }}, sans-serif;
            font-size: {{ $invoiceTemplate->font_size ?? 12 }}px;
            color: #333;
        }

        .header {
            padding-bottom: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #0078C8;
        }

        .company {
            font-size: 18px;
            font-weight: bold;
        }

        .title {
            font-size: 16px;
            color: #0078C8;
            margin-top: 4px;
        }

        .period {
            color: #666;
            font-size: 12px;
            margin-top: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background: #f5f6f7;
            padding: 7px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }

        td {
            padding: 7px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }

        .opening-row td {
            background: #eef3f7;
            font-weight: bold;
        }

        .totals-row td {
            font-weight: bold;
            border-top: 2px solid #555;
        }

        .footer {
            position: fixed;
            bottom: -15px;
            text-align: center;
            width: 100%;
            font-size: 10px;
            color: #777;
        }

        .page-number:before {
            content: counter(page);
        }
    </style>
</head>

<body>

    <div class="header">
        @if (isset($invoiceTemplate) && $invoiceTemplate->show_logo && $invoiceTemplate->logo)
            <img src="{{ public_path('storage/' . $invoiceTemplate->logo) }}" alt="Logo"
                style="width: 100px; height: auto; float: {{ $invoiceTemplate->logo_alignment == 'right' ? 'right' : 'left' }}; margin-bottom: 10px;">
            <div style="clear: both;"></div>
        @endif
        <div class="company">{{ $companyName }}</div>
        <div class="title">Ledger Report</div>
        <div class="period">
            Period: {{ dateFormat($from) }} to {{ dateFormat($to) }}
        </div>
    </div>

    @php
        $cols = $selectedColumns ?? [
            'date',
            'journal_no',
            'description',
            'debit',
            'credit',
            'running_balance',
            'gross',
            'tax',
        ];
        $colCount = count($cols);
    @endphp

    @foreach ($ledgers as $ledger)
        <div style="margin-bottom: 30px; page-break-inside: avoid;">
            <div style="background: #0078C8; color: white; padding: 5px 10px; font-weight: bold; margin-bottom: 5px;">
                {{ $ledger['account']->name }} ({{ $ledger['account']->account_number }})
            </div>
            <table>
                <tr class="opening-row">
                    <td colspan="{{ $colCount - 1 }}">Opening Balance</td>
                    <td style="text-align: right;">{{ numberFormat($ledger['opening_balance']) }}</td>
                </tr>

                <thead>
                    <tr>
                        @if (in_array('date', $cols))
                            <th>Date</th>
                        @endif
                        @if (in_array('journal_no', $cols))
                            <th>Journal</th>
                        @endif
                        @if (in_array('description', $cols))
                            <th>Description</th>
                        @endif
                        @if (in_array('debit', $cols))
                            <th style="text-align: right;">Debit</th>
                        @endif
                        @if (in_array('credit', $cols))
                            <th style="text-align: right;">Credit</th>
                        @endif
                        @if (in_array('running_balance', $cols))
                            <th style="text-align: right;">Running Balance</th>
                        @endif
                        @if (in_array('gross', $cols))
                            <th style="text-align: right;">Gross</th>
                        @endif
                        @if (in_array('tax', $cols))
                            <th style="text-align: right;">Tax</th>
                        @endif
                    </tr>
                </thead>

                <tbody>
                    @foreach ($ledger['rows'] as $row)
                        <tr>
                            @if (in_array('date', $cols))
                                <td>{{ dateFormat($row['date']) }}</td>
                            @endif
                            @if (in_array('journal_no', $cols))
                                <td>{{ $row['journal_no'] }}</td>
                            @endif
                            @if (in_array('description', $cols))
                                <td>{{ $row['description'] }}</td>
                            @endif
                            @if (in_array('debit', $cols))
                                <td style="text-align: right;">{{ numberFormat($row['debit']) }}</td>
                            @endif
                            @if (in_array('credit', $cols))
                                <td style="text-align: right;">{{ numberFormat($row['credit']) }}</td>
                            @endif
                            @if (in_array('running_balance', $cols))
                                <td style="text-align: right;">{{ numberFormat($row['running_balance']) }}</td>
                            @endif
                            @if (in_array('gross', $cols))
                                <td style="text-align: right;">{{ numberFormat($row['gross']) }}</td>
                            @endif
                            @if (in_array('tax', $cols))
                                <td style="text-align: right;">{{ numberFormat($row['tax']) }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>

                <tr class="totals-row">
                    @php
                        $beforeDebit = count(array_intersect(['date', 'journal_no', 'description'], $cols));
                        $afterCredit = count(array_intersect(['running_balance', 'gross', 'tax'], $cols));
                    @endphp
                    @if ($beforeDebit > 0)
                        <td colspan="{{ $beforeDebit }}">Total</td>
                    @endif
                    @if (in_array('debit', $cols))
                        <td style="text-align: right;">{{ numberFormat($ledger['total_debit']) }}</td>
                    @endif
                    @if (in_array('credit', $cols))
                        <td style="text-align: right;">{{ numberFormat($ledger['total_credit']) }}</td>
                    @endif
                    @if (in_array('running_balance', $cols))
                        <td style="text-align: right;"></td>
                    @endif
                    @if (in_array('gross', $cols))
                        <td style="text-align: right;">{{ numberFormat($ledger['total_gross']) }}</td>
                    @endif
                    @if (in_array('tax', $cols))
                        <td style="text-align: right;">{{ numberFormat($ledger['total_tax']) }}</td>
                    @endif
                </tr>
                <tr class="totals-row">
                    <td colspan="{{ $colCount - 1 }}">Closing Balance</td>
                    <td style="text-align: right;">{{ numberFormat($ledger['closing_balance']) }}</td>
                </tr>
            </table>
        </div>
    @endforeach

    <div class="footer">
        Page <span class="page-number"></span>
    </div>


</body>

</html>
