<?php

namespace App\Notifications;

use App\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly UserInvitation $invitation, private readonly string $plainToken) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontend = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
        $url = $frontend.'/accept-invitation?token='.urlencode($this->plainToken).'&email='.urlencode($this->invitation->email);

        return (new MailMessage)
            ->subject('You have been invited to Marjo Tech Hub')
            ->greeting('Welcome to Marjo Tech Hub')
            ->line('You have been invited to join the workspace with the following role(s): '.implode(', ', $this->invitation->roles).'.')
            ->action('Accept invitation', $url)
            ->line('This invitation expires on '.$this->invitation->expires_at->toDayDateTimeString().'.')
            ->line('If you were not expecting this invitation, you can ignore this email.');
    }
}
