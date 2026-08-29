<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0078C8; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 30px; }
        .footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        .invoice-details { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .btn { display: inline-block; background: #0078C8; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>TAX INVOICE</h1>
        </div>

        <div class="content">
            <p>Dear {{ $sale->customer->name }},</p>

            <p>Thank you for your business! Please find attached your tax invoice #{{ $sale->invoice_no }}.</p>

            <div class="invoice-details">
                <h3>Invoice Summary</h3>
                <p><strong>Invoice No:</strong> {{ $sale->invoice_no }}</p>
                <p><strong>Date:</strong> {{ $sale->date->format('d-m-Y') }}</p>
                <p><strong>Total Amount:</strong> {{ $sale->currency->code }} {{ number_format($sale->total, 2) }}</p>
                <p><strong>Payment Status:</strong> {{ $sale->remaining_amount == 0 ? 'Paid' : 'Pending' }}</p>
            </div>

            <p>The detailed invoice is attached as a PDF file. If you have any questions, please don't hesitate to contact us.</p>

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
