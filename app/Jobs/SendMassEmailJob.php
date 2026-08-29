<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\MassMailerCampaign;
use App\Models\MassMailerContact;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendMassEmailJob implements ShouldQueue
{
    use Queueable;

    protected $campaign;
    protected $contactIds;

    /**
     * Create a new job instance.
     */
    public function __construct(MassMailerCampaign $campaign, array $contactIds)
    {
        $this->campaign = $campaign;
        $this->contactIds = $contactIds;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->campaign->update(['status' => 'sending']);

        foreach ($this->contactIds as $contactId) {
            $contact = MassMailerContact::find($contactId);
            if (!$contact)
                continue;

            try {
                // In a real app, you would create a Mailable.
                // For simplicity, we use raw mail or log it.
                Mail::raw($this->campaign->body, function ($message) use ($contact) {
                    $message->to($contact->email)
                        ->subject($this->campaign->subject);
                });

                $this->campaign->increment('sent_contacts');
            } catch (\Exception $e) {
                Log::error("Mass Mailer Error for contact {$contact->email}: " . $e->getMessage());
                $this->campaign->increment('failed_contacts');
            }
        }

        $this->campaign->update(['status' => 'completed']);
    }
}
