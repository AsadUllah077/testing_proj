<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMailemployee extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $employeeName;

    /**
     * Create a new message instance.
     */
    public function __construct($otp, $employeeName)
    {
        $this->otp = $otp;
        $this->employeeName = $employeeName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Login OTP',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.otpemployee',
            with: [
                'otp' => $this->otp,
                'employeeName' => $this->employeeName,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
