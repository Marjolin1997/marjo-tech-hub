<?php

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UnreadConversationNotification extends Notification
{
    use Queueable;

    public function __construct(private Conversation $conversation, private User $sender, private int $unreadCount) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) config('app.frontend_url'), '/').'/messages?conversation='.$this->conversation->id;

        return (new MailMessage)
            ->subject('You have unread messages in Marjo Tech Hub')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->sender->name.' sent you '.$this->unreadCount.' unread '.($this->unreadCount === 1 ? 'message' : 'messages').'.')
            ->line('For privacy, message content is not included in this email.')
            ->action('Open conversation', $url)
            ->line('This notification is sent only after the conversation has remained unread for at least five minutes.');
    }
}
