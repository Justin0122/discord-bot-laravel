<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommandCooldown extends Model
{
    protected $fillable = ['command_name', 'category', 'cooldown', 'command_description', 'public'];

    public function userCooldowns(): HasMany
    {
        return $this->hasMany(UserCooldown::class, 'command_name');
    }

    public function options()
    {
        return $this->hasMany(CommandOption::class, 'command_id');
    }
}
