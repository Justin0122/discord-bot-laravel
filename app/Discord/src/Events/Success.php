<?php

namespace App\Discord\src\Events;

use App\Discord\src\Builders\EmbedBuilder;
use Discord\Discord;

class Success
{
    public static function sendSuccess(Discord $discord, $title, $description = null, $interaction = null): EmbedBuilder
    {
        $builder = new EmbedBuilder($discord);
        $builder->setTitle($title);
        $builder->setDescription($description ?? '');
        $builder->setSuccess();
        if ($interaction){
        $builder->setFooter($interaction);
        }

        return $builder;
    }
}
