<?php

namespace App\Mail;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class VendorStatementMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Vendor      $vendor,
        public Collection  $bills,    // Bill models with remaining_amount > 0
        public float       $totalBilled,
        public float       $totalPaid,
        public float       $totalOutstanding,
        public string      $companyName,
        public string      $statementDate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Account Statement — {$this->vendor->name} as of {$this->statementDate}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vendor-statement',
        );
    }
}
