<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0078C8; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 30px; }
        .footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        .bill-details { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .btn { display: inline-block; background: #0078C8; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>BILL INVOICE</h1>
        </div>

        <div class="content">
            <p>Dear {{ $bill->vendor->name }},</p>

            <p>Please find attached your bill invoice #{{ $bill->invoice_no }}.</p>

            <div class="bill-details">
                <h3>Bill Summary</h3>
                <p><strong>Bill No:</strong> {{ $bill->invoice_no }}</p>
                <p><strong>Date:</strong> {{ $bill->date->format('d-m-Y') }}</p>
                <p><strong>Total Amount:</strong> {{ $bill->currency->symbol ?? 'PKR' }} {{ number_format($bill->total, 2) }}</p>
                <p><strong>Payment Status:</strong> {{ $bill->remaining_amount == 0 ? 'Paid' : 'Pending' }}</p>
            </div>

            <p>The detailed bill is attached as a PDF file. If you have any questions, please don't hesitate to contact us.</p>

            <p>Best regards,<br>
            {{ auth()->user()->company->name ?? 'Company Name' }} Team</p>
        </div>

        <div class="footer">
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>© {{ date('Y') }} {{ auth()->user()->company->name ?? 'Company Name' }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
