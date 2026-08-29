<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $sale;
    public $pdfContent;

    public function __construct(Sale $sale, $pdfContent)
    {
        $this->sale = $sale;
        $this->pdfContent = $pdfContent;
    }

    public function build()
    {
        $fileName = 'Invoice_' . $this->sale->invoice_no . '.pdf';

        return $this->subject('Tax Invoice - ' . $this->sale->invoice_no)
            ->view('emails.invoice')
            ->attachData($this->pdfContent, $fileName, [
                'mime' => 'application/pdf',
            ]);
    }
}
