<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'type', 'lifecycle', 'description', 'repository_url',
        'production_url', 'api_base_url', 'technologies', 'owner_label',
    ];

    protected function casts(): array
    {
        return ['technologies' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}