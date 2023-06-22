<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommandOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'command_id',
        'name',
        'description',
        'required',
        'type',
        'choices',
        'options',
        'channel_types',
        'min_value',
        'max_value',
        'min_length',
        'max_length',
        'autocomplete',
    ];

}
