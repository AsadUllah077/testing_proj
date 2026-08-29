@php
    $asOfDate = \Carbon\Carbon::parse($statementDate);
    $statementNumber = 'ST-' . str_pad((string) $customer->id, 5, '0', STR_PAD_LEFT) . '-' . $asOfDate->format('Ymd');
    $aging = ['current' => 0, 'b1' => 0, 'b2' => 0, 'b3' => 0, 'b4' => 0];
    $overdueCount = 0;

    foreach ($invoices as $invoice) {
        $balance = (float) ($invoice->remaining_amount ?? 0);

        if ($balance <= 0) {
            continue;
        }

        $dueDate = \Carbon\Carbon::parse($invoice->date)->addDays(30);

        if ($dueDate->greaterThanOrEqualTo($asOfDate)) {
            $aging['current'] += $balance;
            continue;
        }

        $overdueCount++;
        $daysOverdue = $dueDate->diffInDays($asOfDate);

        if ($daysOverdue <= 30) {
            $aging['b1'] += $balance;
        } elseif ($daysOverdue <= 60) {
            $aging['b2'] += $balance;
        } elseif ($daysOverdue <= 90) {
            $aging['b3'] += $balance;
        } else {
            $aging['b4'] += $balance;
        }
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Statement</title>
</head>
<body style="margin:0;padding:0;background:#f4f7f9;color:#172033;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7f9;margin:0;padding:24px 0;">
        <tr>
            <td align="center" style="padding:0 12px;">
                <table role="presentation" width="680" cellspacing="0" cellpadding="0" style="width:680px;max-width:100%;background:#ffffff;border:1px solid #e4e8ee;border-collapse:collapse;">
                    <tr>
                        <td style="padding:28px 32px 22px;border-bottom:4px solid #13b5ea;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="vertical-align:top;">
                                        <div style="font-size:20px;font-weight:700;color:#172033;">{{ $companyName }}</div>
                                        <div style="font-size:12px;color:#697386;margin-top:5px;">Account statement for {{ $customer->name }}</div>
                                    </td>
                                    <td align="right" style="vertical-align:top;">
                                        <div style="font-size:24px;line-height:1;font-weight:400;text-transform:uppercase;color:#172033;">Statement</div>
                                        <div style="font-size:11px;color:#697386;margin-top:8px;">{{ $statementNumber }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 32px 0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="width:56%;vertical-align:top;padding-right:18px;">
                                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#697386;letter-spacing:.4px;">Statement To</div>
                                        <div style="font-size:16px;font-weight:700;color:#172033;margin-top:6px;">{{ $customer->name }}</div>
                                        @if ($customer->email)
                                            <div style="font-size:12px;color:#697386;margin-top:4px;">{{ $customer->email }}</div>
                                        @endif
                                        @if ($customer->phone)
                                            <div style="font-size:12px;color:#697386;margin-top:2px;">{{ $customer->phone }}</div>
                                        @endif
                                        <div style="font-size:12px;color:#697386;margin-top:12px;">Statement date: <strong style="color:#172033;">{{ $asOfDate->format('d M Y') }}</strong></div>
                                        <div style="font-size:12px;color:#697386;margin-top:2px;">Outstanding invoices only</div>
                                    </td>
                                    <td style="width:44%;vertical-align:top;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f2fbff;border:1px solid #bdeafa;border-collapse:collapse;">
                                            <tr>
                                                <td align="right" style="padding:18px 18px;">
                                                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#426071;letter-spacing:.4px;">Amount Due</div>
                                                    <div style="font-size:28px;font-weight:700;color:#172033;margin-top:6px;">{{ number_format($totalOutstanding, 2) }}</div>
                                                    <div style="font-size:12px;color:#697386;margin-top:7px;">
                                                        @if ($overdueCount > 0)
                                                            {{ $overdueCount }} overdue invoice{{ $overdueCount === 1 ? '' : 's' }}
                                                        @else
                                                            No overdue invoices
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 32px 0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="width:33.33%;border:1px solid #e4e8ee;padding:13px;">
                                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#697386;">Total Invoiced</div>
                                        <div style="font-size:17px;font-weight:700;color:#172033;margin-top:5px;">{{ number_format($totalInvoiced, 2) }}</div>
                                    </td>
                                    <td style="width:33.33%;border:1px solid #e4e8ee;padding:13px;">
                                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#697386;">Payments Received</div>
                                        <div style="font-size:17px;font-weight:700;color:#15803d;margin-top:5px;">{{ number_format($totalPaid, 2) }}</div>
                                    </td>
                                    <td style="width:33.33%;border:1px solid #e4e8ee;padding:13px;">
                                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#697386;">Outstanding</div>
                                        <div style="font-size:17px;font-weight:700;color:#b42318;margin-top:5px;">{{ number_format($totalOutstanding, 2) }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 32px 0;">
                            <div style="font-size:13px;font-weight:700;color:#172033;margin-bottom:8px;">Aged Balance</div>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                <tr>
                                    @foreach ([['Current', $aging['current'], '#0078c8'], ['1-30 Days', $aging['b1'], '#f59e0b'], ['31-60 Days', $aging['b2'], '#d97706'], ['61-90 Days', $aging['b3'], '#dc2626'], ['90+ Days', $aging['b4'], '#991b1b']] as $bucket)
                                        <td style="width:20%;border:1px solid #e4e8ee;border-top:3px solid {{ $bucket[2] }};padding:10px 8px;text-align:center;">
                                            <div style="font-size:9px;font-weight:700;text-transform:uppercase;color:#697386;">{{ $bucket[0] }}</div>
                                            <div style="font-size:13px;font-weight:700;color:#172033;margin-top:4px;">{{ number_format($bucket[1], 2) }}</div>
                                        </td>
                                    @endforeach
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 32px 0;">
                            <div style="font-size:13px;font-weight:700;color:#172033;margin-bottom:8px;">Outstanding Invoices</div>
                            <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:12px;">
                                <thead>
                                    <tr>
                                        <th align="left" style="border-bottom:1px solid #172033;padding:8px 6px;font-size:10px;text-transform:uppercase;color:#172033;">Invoice</th>
                                        <th align="left" style="border-bottom:1px solid #172033;padding:8px 6px;font-size:10px;text-transform:uppercase;color:#172033;">Due</th>
                                        <th align="right" style="border-bottom:1px solid #172033;padding:8px 6px;font-size:10px;text-transform:uppercase;color:#172033;">Invoiced</th>
                                        <th align="right" style="border-bottom:1px solid #172033;padding:8px 6px;font-size:10px;text-transform:uppercase;color:#172033;">Paid</th>
                                        <th align="right" style="border-bottom:1px solid #172033;padding:8px 6px;font-size:10px;text-transform:uppercase;color:#172033;">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoices as $invoice)
                                        @php
                                            $dueDate = \Carbon\Carbon::parse($invoice->date)->addDays(30);
                                            $isOverdue = (float) ($invoice->remaining_amount ?? 0) > 0 && $dueDate->lt($asOfDate);
                                        @endphp
                                        <tr>
                                            <td style="border-bottom:1px solid #e6e9ee;padding:9px 6px;color:#0078c8;font-weight:700;">{{ $invoice->invoice_no ?: $invoice->unique_no }}</td>
                                            <td style="border-bottom:1px solid #e6e9ee;padding:9px 6px;color:{{ $isOverdue ? '#b42318' : '#2f3b4f' }};">{{ $dueDate->format('d M Y') }}</td>
                                            <td align="right" style="border-bottom:1px solid #e6e9ee;padding:9px 6px;color:#2f3b4f;">{{ number_format($invoice->total, 2) }}</td>
                                            <td align="right" style="border-bottom:1px solid #e6e9ee;padding:9px 6px;color:#15803d;">{{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                                            <td align="right" style="border-bottom:1px solid #e6e9ee;padding:9px 6px;color:#172033;font-weight:700;">{{ number_format($invoice->remaining_amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" style="background:#f6f8fa;border-top:1px solid #172033;padding:10px 6px;font-weight:700;color:#172033;">Statement Totals</td>
                                        <td align="right" style="background:#f6f8fa;border-top:1px solid #172033;padding:10px 6px;font-weight:700;">{{ number_format($totalInvoiced, 2) }}</td>
                                        <td align="right" style="background:#f6f8fa;border-top:1px solid #172033;padding:10px 6px;font-weight:700;color:#15803d;">{{ number_format($totalPaid, 2) }}</td>
                                        <td align="right" style="background:#f6f8fa;border-top:1px solid #172033;padding:10px 6px;font-weight:700;color:#b42318;">{{ number_format($totalOutstanding, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 32px 30px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#fff8e6;border-left:4px solid #f59e0b;border-collapse:collapse;">
                                <tr>
                                    <td style="padding:13px 16px;font-size:12px;color:#5f4000;">
                                        Please remit payment for all outstanding invoices at your earliest convenience. Contact our accounts team if you have any questions.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:18px 32px;background:#f8fafc;border-top:1px solid #e4e8ee;font-size:11px;color:#697386;">
                            This statement was automatically generated by {{ $companyName }} on {{ $asOfDate->format('d M Y') }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
