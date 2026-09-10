<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    protected $fillable = [
        'conversation_id', 'user_id', 'last_read_message_id',
        'last_notified_message_id', 'last_notified_at', 'archived_at',
    ];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime', 'last_notified_at' => 'datetime'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lastReadMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_read_message_id');
    }

    public function lastNotifiedMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_notified_message_id');
    }
}
