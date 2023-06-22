<?php

namespace App\Discord\src\Events;

use App\Discord\src\Builders\EmbedBuilder;
use Discord\Discord;

class Info
{
    public static function sendInfo(Discord $discord, $title, $description = null, $interaction = null): EmbedBuilder
    {
        $builder = new EmbedBuilder($discord);
        $builder->setTitle($title);
        $builder->setDescription($description ?? '');
        $builder->setInfo();
        if ($interaction){
            $builder->setFooter($interaction);
        }

        return $builder;
    }
}
