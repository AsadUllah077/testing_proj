<?php

namespace App\Trait;

use App\Mail\EmailVerification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

trait OtpTrait
{
    public function sendOtp($user)
    {
        $otp = rand(1000, 9999);
        $user->otp = $otp;
        $user->save();
        $details = [
            'code' => $otp
        ];
        Mail::to($user->email)->send(new EmailVerification($details));
        Log::info('OTP is: ' . $user->otp);
        return $otp;
    }

    public function verifyOtp($user, $otp)
    {
        if ($user->otp == $otp) {
            $user->otp = 0;
            $user->save();
            return true;
        } else {
            return false;
        }
    }
}
