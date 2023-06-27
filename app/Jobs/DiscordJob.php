<?php

namespace App\Jobs;

use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Intents;

abstract class DiscordJob
{
    public Discord $discord;

    /**
     * @throws IntentException
     */
    public function __construct()
    {
        $this->discord = new Discord([
            'token' => $_ENV['DISCORD_BOT_TOKEN'],
            'intents' => Intents::getDefaultIntents(),
        ]);

    }

    protected function connectAndHandle(Interaction $interaction, callable $handler): void
    {
        $this->discord->on('ready', function (Discord $discord) use ($interaction, $handler) {
            $handler($discord);
        });

        $this->connect();
    }

    public function connect(): void
    {
        $this->discord->run();
    }

    public function ephemeralHandler(Interaction $interaction, $messageBuilder, $ephemeral): void
    {
        if ($ephemeral) {
            $interaction->updateOriginalResponse($messageBuilder);
            return;
        }

        $interaction->sendFollowupMessage($messageBuilder);
        $interaction->deleteOriginalResponse();
    }

}
