<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    protected $fillable = [
        'message_id', 'uploaded_by', 'kind', 'disk', 'path', 'original_name',
        'mime_type', 'size_bytes', 'duration_ms', 'checksum_sha256',
    ];

    protected $hidden = ['disk', 'path', 'checksum_sha256'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
