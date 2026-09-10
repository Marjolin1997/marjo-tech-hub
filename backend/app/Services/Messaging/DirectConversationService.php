<?php

namespace App\Services\Messaging;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DirectConversationService
{
    public function findOrCreate(User $actor, User $recipient): Conversation
    {
        if ($actor->is($recipient)) {
            throw ValidationException::withMessages(['recipient_id' => 'You cannot start a direct conversation with yourself.']);
        }
        if (! $recipient->can('messages.view') || ! $recipient->can('messages.send')) {
            throw ValidationException::withMessages(['recipient_id' => 'This workspace member is not available for messaging.']);
        }

        $ids = [$actor->id, $recipient->id];
        sort($ids, SORT_NUMERIC);
        $key = 'direct:'.$ids[0].':'.$ids[1];

        try {
            return DB::transaction(function () use ($actor, $recipient, $key): Conversation {
                $existing = Conversation::query()->where('direct_key', $key)->first();
                if ($existing) {
                    $existing->participantStates()->where('user_id', $actor->id)->update(['archived_at' => null]);
                    return $existing->fresh();
                }

                $conversation = Conversation::query()->create(['type' => 'direct', 'direct_key' => $key]);
                $conversation->participants()->attach([$actor->id, $recipient->id]);
                return $conversation;
            }, 3);
        } catch (QueryException $e) {
            $existing = Conversation::query()->where('direct_key', $key)->first();
            if (! $existing) throw $e;
            $existing->participantStates()->where('user_id', $actor->id)->update(['archived_at' => null]);
            return $existing->fresh();
        }
    }
}
