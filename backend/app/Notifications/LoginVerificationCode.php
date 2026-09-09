<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginVerificationCode extends Notification
{
    use Queueable;
    public function __construct(private readonly string $code) {}
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Marjo Tech Hub verification code')
            ->greeting('Sign-in verification')
            ->line('Use this one-time code to finish signing in:')
            ->line($this->code)
            ->line('This code expires in 10 minutes. If you did not try to sign in, you can ignore this email.');
    }
}
