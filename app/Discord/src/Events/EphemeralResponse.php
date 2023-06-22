<?php

namespace App\Discord\src\Events;

use Discord\Parts\Interactions\Interaction;

class EphemeralResponse
{
    public static function send(Interaction $interaction, $messageBuilder, $ephemeral = false, $isInitialEphemeral = false): void
    {
        if ($ephemeral && $isInitialEphemeral){
            $interaction->updateOriginalResponse($messageBuilder);
            return;
        }

        if ($ephemeral && !$isInitialEphemeral)
        {
            $interaction->respondWithMessage($messageBuilder, $ephemeral);
            return;
        }

        if (!$ephemeral || $isInitialEphemeral) {
            $interaction->sendFollowUpMessage($messageBuilder, $ephemeral);
            $interaction->deleteOriginalResponse();
            return;
        }

        $interaction->updateOriginalResponse($messageBuilder);
    }
}
