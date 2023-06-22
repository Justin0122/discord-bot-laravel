<?php

namespace App\Discord\src\Commands;

use Discord\Parts\Interactions\Interaction;
use App\Discord\src\Builders\MessageBuilder;
use App\Discord\src\Events\Success;
use Discord\Discord;

class Ping
{
    public function getDescription(): string
    {
        return 'Ping the bot to check if it is online';
    }

    public function getOptions(): array
    {
        return [
            [
                'name' => 'ephemeral',
                'description' => 'Send the message only to you',
                'type' => 5,
                'required' => false
            ]
        ];
    }

    public function getGuildId(): ?string
    {
        return null;
    }

    public function getCooldown(): ?int
    {
        return 5;
    }

    public function handle(Interaction $interaction, Discord $discord): void
    {
        $options = $interaction->data->options;
        $ephemeral = $options['ephemeral']?->value ?? false;

        $messageBuilder = MessageBuilder::buildMessage(Success::sendSuccess($discord, 'Pong!', 'Pong!'));
        $interaction->respondWithMessage($messageBuilder, $ephemeral);
    }
}
