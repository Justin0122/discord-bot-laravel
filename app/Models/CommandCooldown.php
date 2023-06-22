<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommandCooldown extends Model
{
    protected $fillable = ['command_name', 'cooldown'];

    public function userCooldowns(): HasMany
    {
        return $this->hasMany(UserCooldown::class, 'command_name');
    }
}
