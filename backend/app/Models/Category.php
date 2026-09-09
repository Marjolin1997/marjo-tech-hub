<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;
    protected $fillable = ['parent_id', 'name', 'slug', 'icon', 'sort_order'];
    protected $casts = ['sort_order' => 'integer'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name'); }
    public function entries(): HasMany { return $this->hasMany(Entry::class)->orderBy('sort_order')->latest('updated_at'); }
    public function documents(): HasMany { return $this->hasMany(Document::class)->latest('updated_at'); }
}
