<?php

namespace App\Discord\src\Builders;

use Discord\Parts\Interactions\Interaction;
use Discord\Discord;

class InitialEmbed
{
    public static function Send(Interaction $interaction, Discord $discord, $message = "Please wait", $ephemeral = false): void
    {
        $builder = new EmbedBuilder($discord);
        $builder->setTitle('Loading...');
        $builder->setDescription($message);
        $builder->setInfo();

        $messageBuilder = MessageBuilder::buildMessage($builder);
        $interaction->respondWithMessage($messageBuilder, $ephemeral);
    }
}
