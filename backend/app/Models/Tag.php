<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['name', 'slug'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function entries(): BelongsToMany { return $this->belongsToMany(Entry::class); }
    public function documents(): BelongsToMany { return $this->belongsToMany(Document::class); }
}
