<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LoginChallenge extends Model
{
    use HasUuids;
    protected $fillable = ['user_id', 'code_hash', 'remember', 'attempts', 'expires_at', 'consumed_at'];
    protected $hidden = ['code_hash'];
    protected function casts(): array { return ['remember'=>'boolean','expires_at'=>'datetime','consumed_at'=>'datetime']; }
}
