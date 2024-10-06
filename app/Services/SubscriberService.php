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
        
        $subscriber = Subscribers::create($validatedData);
        $verificationToken = Str::random(32);
        $subscriber->verification_token = $verificationToken;
        $subscriber->save();
        $verificationUrl = url("/api/verify-subscriber-email/{$verificationToken}");
        Mail::to($subscriber->email)->send(new VerifyEmail($verificationUrl));
        return $subscriber;
    }

    public function verifySubscriber($token)
    {
    
        $subscriber = Subscribers::where('verification_token', $token)->first();

        if ($subscriber) {
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
