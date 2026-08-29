<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: {{ $invoiceTemplate->top_margin ?? 40 }}px {{ $invoiceTemplate->address_padding ?? 30 }}px {{ $invoiceTemplate->bottom_margin ?? 40 }}px;
        }

        body {
            font-family: {{ $invoiceTemplate->font ?? 'DejaVu Sans' }}, sans-serif;
            color: #333;
            font-size: {{ $invoiceTemplate->font_size ?? 12 }}px;
        }

        h1,
        h2,
        h3 {
            margin: 0;
            padding: 0;
        }

        .header {
            border-bottom: 2px solid #0078C8;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .company {
            font-size: 18px;
            font-weight: bold;
            color: #222;
        }

        .report-title {
            font-size: 16px;
            margin-top: 5px;
            color: #0078C8;
        }

        .period {
            margin-top: 3px;
            color: #666;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th {
            background: #f5f6f7;
            color: #444;
            padding: 8px;
            text-align: left;
            font-size: 11px;
            border-bottom: 1px solid #ddd;
        }

        td {
            padding: 7px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }

        tr.group-header td {
            background: #eef3f7;
            font-weight: bold;
            padding: 8px;
            border-top: 2px solid #ccc;
        }

        .totals-row td {
            font-weight: bold;
            border-top: 2px solid #555;
            padding-top: 10px;
        }

        .footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            text-align: center;
            color: #777;
            font-size: 10px;
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
        <div class="company">{{ $companyName ?? 'Your Company Name' }}</div>
        <div class="report-title">Journal Report</div>
        <div class="period">Period: {{ dateFormat($from) }} to {{ dateFormat($to) }}</div>
    </div>

    @php
        $cols = $selectedColumns ?? [
            'date',
            'unique_no',
            'account',
            'debit',
            'credit',
            'description',
            'counterparty',
            'type',
            'invoice_no',
        ];
        $colCount = count($cols);
    @endphp

    @foreach ($journals as $uniqueNo => $groups)
        @php
            $allRows = $groups->flatten();
        @endphp

        <table>
            <tr class="group-header">
                <td colspan="{{ $colCount }}">Journal #{{ $uniqueNo }}</td>
            </tr>

            <thead>
                <tr>
                    @if (in_array('date', $cols))
                        <th>Date</th>
                    @endif
                    @if (in_array('unique_no', $cols))
                        <th>Journal No</th>
                    @endif
                    @if (in_array('account', $cols))
                        <th>Account</th>
                    @endif
                    @if (in_array('debit', $cols))
                        <th>Debit</th>
                    @endif
                    @if (in_array('credit', $cols))
                        <th>Credit</th>
                    @endif
                    @if (in_array('description', $cols))
                        <th>Description</th>
                    @endif
                    @if (in_array('counterparty', $cols))
                        <th>Counterparty</th>
                    @endif
                    @if (in_array('type', $cols))
                        <th>Type</th>
                    @endif
                    @if (in_array('invoice_no', $cols))
                        <th>Invoice No</th>
                    @endif
                </tr>
            </thead>

            <tbody>
                @foreach ($groups as $partyKey => $rows)
                    @php
                        $first = $rows->first();

                        if ($first->payable) {
                            $partyName = ucfirst($first->payable_type) . ': ' . $first->payable->display_name;
                        } elseif ($first->customer) {
                            $partyName = 'Customer: ' . $first->customer->name;
                        } else {
                            $partyName = 'General';
                        }
                    @endphp

                    {{-- Party Header --}}
                    <tr style="background:#eef3f7;font-weight:bold;">
                        <td colspan="{{ $colCount }}">{{ $partyName }}</td>
                    </tr>

                    {{-- Party Lines --}}
                    @foreach ($rows as $t)
                        <tr>
                            @if (in_array('date', $cols))
                                <td>{{ dateFormat($t->date) }}</td>
                            @endif
                            @if (in_array('unique_no', $cols))
                                <td>{{ $t->unique_no }}</td>
                            @endif
                            @if (in_array('account', $cols))
                                <td>{{ $t->account->name ?? '-' }}</td>
                            @endif
                            @if (in_array('debit', $cols))
                                <td>{{ numberFormat($t->debit) }}</td>
                            @endif
                            @if (in_array('credit', $cols))
                                <td>{{ numberFormat($t->credit) }}</td>
                            @endif
                            @if (in_array('description', $cols))
                                <td>{{ $t->description }}</td>
                            @endif
                            @if (in_array('counterparty', $cols))
                                <td>{{ $t->counterparty ?? '' }}</td>
                            @endif
                            @if (in_array('type', $cols))
                                <td>{{ $t->type ?? '' }}</td>
                            @endif
                            @if (in_array('invoice_no', $cols))
                                <td>{{ $t->invoice_no ?? '' }}</td>
                            @endif
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        <br><br>
    @endforeach


    <table>
        <tr class="totals-row">
            @if (in_array('date', $cols))
                <td></td>
            @endif
            @if (in_array('unique_no', $cols))
                <td></td>
            @endif
            @if (in_array('account', $cols))
                <td></td>
            @endif
            @if (in_array('debit', $cols))
                <td>Total Debit: {{ numberFormat($totalDebit) }}</td>
            @endif
            @if (in_array('credit', $cols))
                <td>Total Credit: {{ numberFormat($totalCredit) }}</td>
            @endif
            @if (in_array('description', $cols))
                <td></td>
            @endif
            @if (in_array('counterparty', $cols))
                <td></td>
            @endif
            @if (in_array('type', $cols))
                <td></td>
            @endif
            @if (in_array('invoice_no', $cols))
                <td></td>
            @endif
        </tr>
    </table>

    <div class="footer">
        Page <span class="page-number"></span>
    </div>

</body>

</html>
