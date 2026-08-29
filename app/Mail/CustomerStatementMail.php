<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CustomerStatementMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Customer    $customer,
        public Collection  $invoices,    // Sale models with remaining_amount > 0
        public float       $totalInvoiced,
        public float       $totalPaid,
        public float       $totalOutstanding,
        public string      $companyName,
        public string      $statementDate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Account Statement — {$this->customer->name} as of {$this->statementDate}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-statement',
        );
    }
}
