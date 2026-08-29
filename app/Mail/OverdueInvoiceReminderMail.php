<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OverdueInvoiceReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Sale $sale;
    public int $daysOverdue;
    public string $companyName;
    public string $tone;
    public string $subjectPrefix;

    public function __construct(Sale $sale, int $daysOverdue, string $companyName, string $tone = 'neutral', string $subjectPrefix = 'Payment Reminder')
    {
        $this->sale          = $sale;
        $this->daysOverdue   = $daysOverdue;
        $this->companyName   = $companyName;
        $this->tone          = $tone;
        $this->subjectPrefix = $subjectPrefix;
    }

    public function build(): static
    {
        return $this
            ->subject("{$this->subjectPrefix}: Invoice #{$this->sale->invoice_no} - {$this->daysOverdue} Day(s) Overdue")
            ->view('emails.overdue-invoice-reminder');
    }
}
