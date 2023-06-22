<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    protected $fillable = ['name', 'discord_id'];

    public function cooldowns(): HasMany
    {
        return $this->hasMany(UserCooldown::class, 'discord_id');
    }
}

