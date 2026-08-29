<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; background: #f4f6f9; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 30px auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #0078C8; padding: 28px 32px; color: white; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 300; }
        .header p { margin: 6px 0 0; opacity: .8; font-size: 13px; }
        .body { padding: 32px; }
        .greeting { font-size: 16px; margin-bottom: 16px; }
        .summary-box { background: #f0f9ff; border-left: 4px solid #0078C8; padding: 16px 20px; border-radius: 4px; margin: 20px 0; }
        .summary-box p { margin: 4px 0; font-size: 13px; color: #1e3a5f; }
        .summary-box .net { font-size: 20px; font-weight: bold; color: #0078C8; margin-top: 8px; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
        .info-row:last-child { border-bottom: none; }
        .label { color: #6b7280; }
        .value { font-weight: bold; color: #1e293b; }
        .note { font-size: 12px; color: #9ca3af; margin-top: 24px; font-style: italic; }
        .footer { background: #f8fafc; padding: 16px 32px; text-align: center; font-size: 11px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Your Payslip is Ready</h1>
            <p>{{ $payslip->payRun->period_start->format('M d') }} – {{ $payslip->payRun->period_end->format('M d, Y') }}</p>
        </div>
        <div class="body">
            <p class="greeting">Dear <strong>{{ $payslip->employee->full_name }}</strong>,</p>
            <p>Please find your payslip attached for the pay period ending <strong>{{ $payslip->payRun->period_end->format('M d, Y') }}</strong>.</p>

            <div class="summary-box">
                <p><strong>Pay Summary</strong></p>
                <div class="info-row">
                    <span class="label">Gross Earnings</span>
                    <span class="value">{{ currency()?->symbol }} {{ number_format($payslip->gross_earnings, 2) }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Total Deductions</span>
                    <span class="value">({{ currency()?->symbol }} {{ number_format($payslip->total_deductions, 2) }})</span>
                </div>
                <p class="net">Net Pay: {{ currency()?->symbol }} {{ number_format($payslip->net_pay, 2) }}</p>
            </div>

            <p>Your full payslip is attached as a PDF. Please keep it for your records.</p>
            <p class="note">This is an automated email. Please do not reply. For any queries, contact the HR department.</p>
        </div>
        <div class="footer">
            {{ $payslip->employee->company->name ?? config('app.name') }} &bull; Confidential
        </div>
    </div>
</body>
</html>
