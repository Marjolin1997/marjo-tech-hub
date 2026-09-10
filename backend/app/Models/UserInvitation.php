<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInvitation extends Model
{
    protected $fillable = ['email', 'name', 'token_hash', 'roles', 'invited_by', 'expires_at', 'accepted_at', 'revoked_at'];
    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'roles' => 'array',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return !$this->accepted_at && !$this->revoked_at && $this->expires_at->isFuture();
    }
}
