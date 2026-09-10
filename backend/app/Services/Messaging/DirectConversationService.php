<?php

namespace App\Services\Messaging;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DirectConversationService
{
    public function findOrCreate(User $actor, User $recipient): Conversation
    {
        if ($actor->is($recipient)) {
            throw ValidationException::withMessages(['recipient_id' => 'You cannot start a direct conversation with yourself.']);
        }

        return DB::transaction(function () use ($actor, $recipient): Conversation {
            $existing = Conversation::query()
                ->where('type', 'direct')
                ->whereHas('participants', fn ($q) => $q->where('users.id', $actor->id))
                ->whereHas('participants', fn ($q) => $q->where('users.id', $recipient->id))
                ->withCount('participants')
                ->get()
                ->first(fn (Conversation $conversation) => $conversation->participants_count === 2);

            if ($existing) {
                $existing->participantStates()->whereIn('user_id', [$actor->id, $recipient->id])->update(['archived_at' => null]);
                return $existing->fresh();
            }

            $conversation = Conversation::query()->create(['type' => 'direct']);
            $conversation->participants()->attach([$actor->id, $recipient->id]);
            return $conversation;
        });
    }
}
