<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entry extends Model
{
    use HasFactory;

    public const TYPES = ['command', 'snippet', 'note'];

    protected $fillable = [
        'category_id', 'title', 'slug', 'type', 'language', 'description',
        'content', 'is_sensitive', 'sort_order',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
