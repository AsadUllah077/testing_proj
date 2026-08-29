@php
    $customer = $sale->customer;
    $invoiceNo = $sale->invoice_no ?: $sale->unique_no;
    $invoiceDate = \Carbon\Carbon::parse($sale->date);
    $dueDate = $invoiceDate->copy()->addDays(30);
    $remainingAmount = (float) ($sale->remaining_amount ?? 0);
    $paidAmount = (float) ($sale->paid_amount ?? 0);
    $totalAmount = (float) ($sale->total ?? 0);
    $toneConfig = [
        'friendly' => [
            'label' => 'Friendly Reminder',
            'accent' => '#15803d',
            'bg' => '#f0fdf4',
            'border' => '#bbf7d0',
            'intro' => 'A quick note that this invoice is still open. If payment is already on the way, thank you and please disregard this reminder.',
            'request' => 'Please arrange payment when convenient, or contact us if you need anything from our side.',
        ],
        'firm' => [
            'label' => 'Payment Reminder',
            'accent' => '#d97706',
            'bg' => '#fff8e6',
            'border' => '#fed7aa',
            'intro' => 'This invoice is now past due. We kindly request that you review and settle the outstanding balance.',
            'request' => 'Please arrange payment at your earliest convenience. If this has already been paid, kindly ignore this message.',
        ],
        'urgent' => [
            'label' => 'Action Required',
            'accent' => '#b42318',
            'bg' => '#fef2f2',
            'border' => '#fecaca',
            'intro' => 'This invoice is significantly overdue and requires immediate attention.',
            'request' => 'Please arrange payment immediately, or contact our accounts team to discuss a payment arrangement.',
        ],
        'neutral' => [
            'label' => 'Payment Reminder',
            'accent' => '#d97706',
            'bg' => '#fff8e6',
            'border' => '#fed7aa',
            'intro' => 'This is a friendly reminder that payment for this invoice has not yet been received.',
            'request' => 'Please arrange payment at your earliest convenience. If you have already paid, please disregard this message.',
        ],
    ];
    $config = $toneConfig[$tone] ?? $toneConfig['neutral'];
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reminder</title>
</head>
<body style="margin:0;padding:0;background:#f4f7f9;color:#172033;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7f9;margin:0;padding:24px 0;">
        <tr>
            <td align="center" style="padding:0 12px;">
                <table role="presentation" width="660" cellspacing="0" cellpadding="0" style="width:660px;max-width:100%;background:#ffffff;border:1px solid #e4e8ee;border-collapse:collapse;">
                    <tr>
                        <td style="padding:28px 32px 22px;border-bottom:4px solid #13b5ea;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="vertical-align:top;">
                                        <div style="font-size:20px;font-weight:700;color:#172033;">{{ $companyName }}</div>
                                        <div style="font-size:12px;color:#697386;margin-top:5px;">Invoice payment reminder</div>
                                    </td>
                                    <td align="right" style="vertical-align:top;">
                                        <div style="font-size:24px;line-height:1;font-weight:400;text-transform:uppercase;color:#172033;">Reminder</div>
                                        <div style="font-size:11px;color:#697386;margin-top:8px;">Invoice {{ $invoiceNo }}</div>
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
                                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#697386;letter-spacing:.4px;">Bill To</div>
                                        <div style="font-size:16px;font-weight:700;color:#172033;margin-top:6px;">{{ $customer->name }}</div>
                                        @if ($customer->email)
                                            <div style="font-size:12px;color:#697386;margin-top:4px;">{{ $customer->email }}</div>
                                        @endif
                                        @if ($customer->phone)
                                            <div style="font-size:12px;color:#697386;margin-top:2px;">{{ $customer->phone }}</div>
                                        @endif
                                    </td>
                                    <td style="width:44%;vertical-align:top;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f2fbff;border:1px solid #bdeafa;border-collapse:collapse;">
                                            <tr>
                                                <td align="right" style="padding:18px;">
                                                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#426071;letter-spacing:.4px;">Amount Due</div>
                                                    <div style="font-size:28px;font-weight:700;color:#172033;margin-top:6px;">{{ number_format($remainingAmount, 2) }}</div>
                                                    <div style="font-size:12px;color:#697386;margin-top:7px;">{{ $daysOverdue }} day{{ $daysOverdue === 1 ? '' : 's' }} overdue</div>
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
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:{{ $config['bg'] }};border:1px solid {{ $config['border'] }};border-left:4px solid {{ $config['accent'] }};border-collapse:collapse;">
                                <tr>
                                    <td style="padding:15px 16px;">
                                        <div style="font-size:13px;font-weight:700;color:{{ $config['accent'] }};">{{ $config['label'] }}: Invoice {{ $invoiceNo }} is overdue</div>
                                        <div style="font-size:12px;color:#3f4858;margin-top:6px;">{{ $config['intro'] }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 32px 0;">
                            <div style="font-size:13px;font-weight:700;color:#172033;margin-bottom:8px;">Invoice Details</div>
                            <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:12px;">
                                <tr>
                                    <td style="width:38%;border-bottom:1px solid #e6e9ee;padding:10px 6px;color:#697386;">Invoice Number</td>
                                    <td align="right" style="border-bottom:1px solid #e6e9ee;padding:10px 6px;color:#0078c8;font-weight:700;">{{ $invoiceNo }}</td>
                                </tr>
                                <tr>
                                    <td style="border-bottom:1px solid #e6e9ee;padding:10px 6px;color:#697386;">Invoice Date</td>
                                    <td align="right" style="border-bottom:1px solid #e6e9ee;padding:10px 6px;color:#172033;font-weight:700;">{{ $invoiceDate->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <td style="border-bottom:1px solid #e6e9ee;padding:10px 6px;color:#697386;">Due Date</td>
                                    <td align="right" style="border-bottom:1px solid #e6e9ee;padding:10px 6px;color:#b42318;font-weight:700;">{{ $dueDate->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <td style="border-bottom:1px solid #e6e9ee;padding:10px 6px;color:#697386;">Original Amount</td>
                                    <td align="right" style="border-bottom:1px solid #e6e9ee;padding:10px 6px;color:#172033;font-weight:700;">{{ number_format($totalAmount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="border-bottom:1px solid #e6e9ee;padding:10px 6px;color:#697386;">Amount Paid</td>
                                    <td align="right" style="border-bottom:1px solid #e6e9ee;padding:10px 6px;color:#15803d;font-weight:700;">{{ number_format($paidAmount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="background:#f6f8fa;border-top:1px solid #172033;padding:11px 6px;color:#172033;font-weight:700;">Outstanding Balance</td>
                                    <td align="right" style="background:#f6f8fa;border-top:1px solid #172033;padding:11px 6px;color:#b42318;font-size:16px;font-weight:700;">{{ number_format($remainingAmount, 2) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 32px 0;">
                            <div style="font-size:13px;color:#2f3b4f;line-height:1.6;">
                                Dear <strong>{{ $customer->name }}</strong>,
                                <br><br>
                                {{ $config['request'] }}
                                <br><br>
                                If there is a query with this invoice, please contact our accounts team so we can help resolve it quickly.
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 32px 30px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border:1px solid #e4e8ee;border-collapse:collapse;">
                                <tr>
                                    <td style="padding:14px 16px;font-size:12px;color:#697386;line-height:1.5;">
                                        Kind regards,<br>
                                        <strong style="color:#172033;">{{ $companyName }} Accounts Team</strong>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:18px 32px;background:#f8fafc;border-top:1px solid #e4e8ee;font-size:11px;color:#697386;">
                            This is an automated payment reminder from {{ $companyName }}. Please ignore this email if payment has already been made.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
