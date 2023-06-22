<?php

namespace App\Discord\src\Events;

use Discord\Parts\Interactions\Interaction;
use Discord\Builders\MessageBuilder;
use App\Discord\src\Builders\EmbedBuilder;
use Discord\Discord;

class Error
{
    public static function sendError(Interaction $interaction, Discord $discord, $message, $isEdit = false, $isOriginalEpemeral = false): void
    {
        $builder = new EmbedBuilder($discord);
        $builder->setTitle('Error');
        $builder->setDescription($message ?? 'Something went wrong');
        $builder->setError();

        $messageBuilder = new MessageBuilder();
        $messageBuilder->addEmbed($builder->build());

        if ($isOriginalEpemeral) {
            $interaction->updateOriginalResponse($messageBuilder);
            return;
        }

        if ($isEdit) {
            $interaction->sendFollowUpMessage($messageBuilder, true);
            $interaction->deleteOriginalResponse();
            return;
        }

        $interaction->respondWithMessage($messageBuilder, true);
    }
}
