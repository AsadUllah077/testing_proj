<x-mail::message>
# Hello {{ $employeeName }}!

Your One-Time Password (OTP) for login is:

<x-mail::panel>
# {{ $otp }}
</x-mail::panel>

This OTP is valid for **1 minute**. Please do not share this OTP with anyone.

If you did not request this OTP, please ignore this email.

Thanks,
{{ config('app.name') }}
</x-mail::message>
