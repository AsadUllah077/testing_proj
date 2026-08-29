@component('mail::message')
# {{ $announcement->title }}

@if ($recipientName)
Dear **{{ $recipientName }}**,
@else
Dear Team Member,
@endif

We have an important update regarding **{{ $announcement->title }}**.

@component('mail::panel')
**Announcement Details:**
- **Category:** {{ $announcement->category ?? 'General' }}
- **Priority:** {{ ucfirst($announcement->priority) }}
@endcomponent

---

{{ strip_tags($announcement->content) }}

---

Regards,
**{{ $companyName }} Team**

This is an automated notification from the {{ $companyName }} ERP System. Please do not reply to this email.
@endcomponent
