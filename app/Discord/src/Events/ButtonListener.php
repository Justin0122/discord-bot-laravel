<?php

namespace App\Discord\src\Events;

use Discord\Parts\Interactions\Interaction;
use App\Discord\src\Builders\MessageBuilder;
use App\Discord\src\Builders\EmbedBuilder;
use Discord\Discord;

class ButtonListener
{
    public static function listener(Discord $discord, $button, $title = "No title set", $description = "No description set", $isFollowUp = false): void
    {
        $button->setListener(function (Interaction $interaction) use ($button, $description, $title, $discord, $isFollowUp) {
            if ($interaction->member->user->id !== $button->user->id) {
                Error::sendError($interaction, $discord, 'You cannot use this button');
            }
            $builder = new EmbedBuilder($discord);
            $builder->setTitle($title);
            $builder->setDescription($description);
            $builder->setSuccess();
            $messageBuilder = MessageBuilder::buildMessage($builder);


            if ($isFollowUp) {
                $interaction->sendFollowUpMessage($messageBuilder, true);
            }else {
                $interaction->updateMessage($messageBuilder);
            }
        }, $discord);
    }
}
