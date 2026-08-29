<?php

namespace App\Mail;

use App\Models\Bill;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorBillMail extends Mailable
{
    use Queueable, SerializesModels;

    public $bill;
    public $pdfContent;

    public function __construct(Bill $bill, $pdfContent)
    {
        $this->bill = $bill;
        $this->pdfContent = $pdfContent;
    }

    public function build()
    {
        $fileName = 'Bill_' . $this->bill->invoice_no . '.pdf';
        
        return $this->subject('Bill Invoice - ' . $this->bill->invoice_no)
            ->view('emails.vendor-bill')
            ->attachData($this->pdfContent, $fileName, [
                'mime' => 'application/pdf',
            ]);
    }
}