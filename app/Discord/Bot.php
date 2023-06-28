<?php

namespace App\Discord;

use App\Discord\src\Events\Error;
use App\Discord\src\Helpers\CommandCooldownManager;
use App\Discord\src\Helpers\CommandRegistrar;
use App\Discord\src\Helpers\RemoveAllCommands;
use App\Models\User;
use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\User\Activity;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;
use App\Discord\src\Utils\GenerateCommandsTable;

class Bot
{
    private static Bot $instance;
    private Discord $discord;

    /**
     * @throws IntentException
     */
    public function __construct()
    {
        $this->discord = new Discord([
            'token' => $_ENV['DISCORD_BOT_TOKEN'],
            'intents' => Intents::getDefaultIntents(),
        ]);

        $this->discord->on('ready', function (Discord $discord) {
            $activity = new Activity($discord, [
                'type' => Activity::TYPE_PLAYING,
                'name' => 'PHP'
            ]);
            $discord->updatePresence($activity);

            CommandRegistrar::register($discord);
//            RemoveAllCommands::deleteAllCommands($discord);
            GenerateCommandsTable::generateCommandsTable();

        });

        $this->discord->on(Event::INTERACTION_CREATE, function (Interaction $interaction, Discord $discord) {
            $command = CommandRegistrar::getCommandByName($interaction->data->name);
            if ($command) {
                $userId = $interaction->member->user->id;

                // Check if the user exists in the database
                $user = User::where('discord_id', $userId)->first();
                if (!$user) {
                    // User doesn't exist, create a new user
                    User::create([
                        'name' => $interaction->member->user->username,
                        'discord_id' => $userId,
                    ]);
                }

                $cooldownDuration = $command->getCooldown();
                $remainingTime = CommandCooldownManager::checkCooldown($userId, $interaction->data->name, $cooldownDuration);

                if ($remainingTime > 0 && $userId != $_ENV['DISCORD_BOT_OWNER_ID']) {
                    Error::sendError($interaction, $discord, 'Please wait ' . $remainingTime . ' seconds before using this command again');
                    return;
                }

                // Execute the command
                $command->handle($interaction, $discord, $userId);
            }
        });

        self::$instance = $this;
    }

    public static function getDiscord(): Discord
    {
        return self::$instance->discord;
    }

    public function discord(): Discord
    {
        return $this->discord;
    }
}
