<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Document extends Model
{
    protected $fillable = [
        'category_id', 'title', 'slug', 'description', 'original_name', 'stored_name',
        'disk', 'path', 'mime_type', 'extension', 'size', 'checksum', 'is_sensitive',
    ];

    protected $casts = ['size' => 'integer', 'is_sensitive' => 'boolean'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function tags(): BelongsToMany { return $this->belongsToMany(Tag::class); }
}
