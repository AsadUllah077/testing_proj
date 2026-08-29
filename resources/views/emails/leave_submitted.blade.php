<x-mail::message>
# Leave Request Submitted

Hi {{ $leaveRequest->employee->full_name }},

Your leave request has been submitted successfully.

**Details:**
- **Leave Type:** {{ $leaveRequest->leaveType->name ?? 'N/A' }}
- **Start Date:** {{ $leaveRequest->start_date }}
- **End Date:** {{ $leaveRequest->end_date }}
- **Reason:** {{ $leaveRequest->adjustment_reason ?? 'N/A' }}

We will notify you once it's reviewed.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
