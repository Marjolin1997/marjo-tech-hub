<?php

namespace App\Console\Commands;

use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Notifications\UnreadConversationNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NotifyUnreadConversations extends Command
{
    protected $signature = 'messaging:notify-unread';
    protected $description = 'Email users about conversations that have remained unread for at least five minutes';

    public function handle(): int
    {
        $cutoff = now()->subMinutes(5);
        $ids = ConversationParticipant::query()
            ->whereHas('user')
            ->whereHas('conversation', fn ($q) => $q->whereNotNull('last_message_at')->where('last_message_at', '<=', $cutoff))
            ->pluck('id');

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, $cutoff): void {
                $state = ConversationParticipant::query()->lockForUpdate()->with(['user', 'conversation'])->find($id);
                if (! $state?->user || ! $state->conversation) return;

                $unread = Message::query()
                    ->where('conversation_id', $state->conversation_id)
                    ->where('sender_id', '!=', $state->user_id)
                    ->when($state->last_read_message_id, fn ($q, $lastRead) => $q->where('id', '>', $lastRead))
                    ->orderBy('id')
                    ->get();
                if ($unread->isEmpty()) return;

                $latest = $unread->last();
                if ($latest->created_at->isAfter($cutoff)) return;
                if ($state->last_notified_message_id && $latest->id <= $state->last_notified_message_id) return;

                $sender = $latest->sender;
                if (! $sender) return;

                $state->user->notify(new UnreadConversationNotification($state->conversation, $sender, $unread->count()));
                $state->forceFill(['last_notified_message_id' => $latest->id, 'last_notified_at' => now()])->save();
            }, 3);
        }

        return self::SUCCESS;
    }
}
