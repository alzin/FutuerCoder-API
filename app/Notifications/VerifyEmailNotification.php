<?php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    protected $verificationToken;
    protected $userId;

    public function __construct($verificationToken, $userId)
    {
        $this->verificationToken = $verificationToken;
        $this->userId = $userId;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
         $url = URL::temporarySignedRoute(
            'verification.verify', now()->addMinutes(60), ['id' => $this->userId, 'hash' => $this->verificationToken]
        );

        return (new MailMessage)
        ->subject('Verify Your Email Address')
        ->line('Please click the button below to verify your email address.')
        ->action('Verify Email Address', $url)
        ->line('If you did not create an account, no further action is required.');

    }
}
