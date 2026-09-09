<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected string $guard_name = 'web';

    protected $fillable = ['name', 'first_name', 'last_name', 'username', 'job_title', 'bio', 'avatar_disk', 'avatar_path', 'email', 'password'];
    protected $hidden = ['password', 'remember_token', 'avatar_disk', 'avatar_path'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function identityPayload(): array
    {
        return $this->only(['id', 'name', 'first_name', 'last_name', 'username', 'job_title', 'bio', 'email']) + [
            'has_avatar' => (bool) ($this->avatar_disk && $this->avatar_path),
            'roles' => $this->getRoleNames()->values()->all(),
            'permissions' => $this->getAllPermissions()->pluck('name')->sort()->values()->all(),
        ];
    }

    public function categories(): HasMany { return $this->hasMany(Category::class); }
    public function entries(): HasMany { return $this->hasMany(Entry::class); }
    public function documents(): HasMany { return $this->hasMany(Document::class); }
    public function tags(): HasMany { return $this->hasMany(Tag::class); }
    public function favorites(): HasMany { return $this->hasMany(Favorite::class); }
}
