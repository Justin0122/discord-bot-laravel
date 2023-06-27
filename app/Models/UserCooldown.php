<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCooldown extends Model
{
    protected $fillable = ['discord_id', 'command_id', 'cooldown'];

    public function user()
    {
        return $this->belongsTo(User::class, 'discord_id');
    }

    public function commandCooldown(): BelongsTo
    {
        return $this->belongsTo(Command::class, 'command_id');
    }
}
