<?php

namespace App\Services;

use App\Models\subscribers;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\VerifyEmail;

class SubscriberService
{
    public function createSubscriber($validatedData)
    {
        // إنشاء المشترك
        $subscriber = Subscribers::create($validatedData);

        // توليد رمز التحقق
        $verificationToken = Str::random(32);

        // حفظ رمز التحقق في قاعدة البيانات
        $subscriber->verification_token = $verificationToken;
        $subscriber->save();

        // إعداد رابط التحقق
        $verificationUrl = url("/api/verify-subscriber-email/{$verificationToken}");

        // إرسال البريد الإلكتروني
        Mail::to($subscriber->email)->send(new VerifyEmail($verificationUrl));

        return $subscriber;
    }

    public function verifySubscriber($token)
    {
        // البحث عن المشترك باستخدام رمز التحقق
        $subscriber = Subscribers::where('verification_token', $token)->first();

        if ($subscriber) {
            // تأكيد البريد الإلكتروني
            $subscriber->update([
                'email_verified_at' => now(),
                'verification_token' => null,
                'email_verified' => 1
            ]);

            return true;
        }

        return false;
    }
}
