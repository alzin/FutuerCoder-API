<?php

namespace App\Services;

use App\Models\GuestUsers;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\VerifyEmail;

class GuestUserService
{
    public function createGuestUser($data)
    {
        // توليد رمز التحقق
        $verificationToken = Str::random(32);

        // إنشاء المستخدم الضيف
        $guestUser = GuestUsers::create([
            'firstName' => $data['firstName'],
            'lastName' => $data['lastName'],
            'age' => $data['age'],
            'email' => $data['email'],
            'timeZone' => $data['timeZone'],
            'verification_token' => $verificationToken
        ]);

        // إعداد رابط التحقق
        $verificationUrl = url("/api/verify-guest-email/{$verificationToken}");

        // إرسال البريد الإلكتروني
        Mail::to($guestUser->email)->send(new VerifyEmail($verificationUrl));

        return $guestUser;
    }

    public function verifyGuestUser($token)
    {
        // البحث عن المستخدم الضيف باستخدام رمز التحقق
        $guestUser = GuestUsers::where('verification_token', $token)->first();

        if ($guestUser) {
            // تأكيد البريد الإلكتروني
            $guestUser->update([
                'email_verified_at' => now(),
                'verification_token' => null,
                'email_verified' => 1
            ]);

            return true;
        }

        return false;
    }
}
