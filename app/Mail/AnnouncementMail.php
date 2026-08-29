<?php

namespace App\Mail;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnnouncementMail extends Mailable
{
    use Queueable, SerializesModels;

    public Announcement $announcement;
    public string $recipientName;
    public string $companyName;

    /**
     * Create a new message instance.
     */
    public function __construct(Announcement $announcement, string $recipientName = '', string $companyName = '')
    {
        $this->announcement = $announcement;
        $this->recipientName = $recipientName;
        $this->companyName = $companyName ?: config('app.name');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), $this->companyName . ' - Announcement'),
            subject: '[Announcement] ' . $this->announcement->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.announcement',
            with: [
                'announcement' => $this->announcement,
                'recipientName' => $this->recipientName,
                'companyName' => $this->companyName,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
