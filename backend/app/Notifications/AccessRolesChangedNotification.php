<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccessRolesChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private User $actor,
        private array $addedRoles,
        private array $removedRoles,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your Marjo Tech Hub access was updated')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->actor->name.' updated your workspace access.');

        if ($this->addedRoles !== []) {
            $mail->line('Added role'.(count($this->addedRoles) === 1 ? '' : 's').': '.implode(', ', $this->addedRoles).'.');
        }

        if ($this->removedRoles !== []) {
            $mail->line('Removed role'.(count($this->removedRoles) === 1 ? '' : 's').': '.implode(', ', $this->removedRoles).'.');
        }

        return $mail
            ->action('Open Marjo Tech Hub', rtrim((string) config('app.frontend_url'), '/'))
            ->line('Your effective permissions are determined by your current workspace roles.');
    }
}
