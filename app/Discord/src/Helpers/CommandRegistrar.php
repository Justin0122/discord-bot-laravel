<?php

namespace App\Discord\src\Helpers;

use App\Discord\Bot;
use App\Models\CommandCooldown;
use Discord\Parts\Interactions\Command\Command;
use Discord\Discord;

class CommandRegistrar
{
    public static function register(Discord $discord): void
    {
        $phpFiles = self::scanDirectory(__DIR__ . '/../Commands');

        foreach ($phpFiles as $phpFile) {
            require_once $phpFile;

            $className = 'App\\Discord\\src\\Commands\\' . self::getClassNameFromFilename($phpFile);
            if (class_exists($className)) {
                $commandInstance = new $className();

                $name = strtolower((new \ReflectionClass($className))->getShortName());
                $description = $commandInstance->getDescription();
                $options = $commandInstance->getOptions();
                $guildId = $commandInstance->getGuildId();

                $commandData = compact('name', 'description', 'options') + ['default_permission' => true];

                if ($guildId) {
                    $guild = Bot::getDiscord()?->guilds?->offsetGet($guildId);

                    if ($guild) {
                        $command = new Command(Bot::getDiscord(), $commandData);
                        $guild->commands->save($command);
                        echo "Registered command: {$name} to guild: {$guildId}\n";
                    }
                } else {
                    $command = new Command(Bot::getDiscord(), $commandData);
                    Bot::getDiscord()?->application?->commands->save($command);
                    echo "Registered command: {$name}\n";
                }
            }

            $commandCooldown = $commandInstance->getCooldown();
            if ($commandCooldown) {
                CommandCooldown::updateOrCreate([
                    'command_name' => $name,
                ], [
                    'cooldown' => $commandCooldown,
                ]);
            }

        }
    }

    private static $commandCache = null;

    /**
     * @throws \ReflectionException
     */
    public static function getCommandByName($command)
    {
        if (self::$commandCache === null) {
            $commandFiles = self::scanDirectory(__DIR__ . '/../Commands');
            $commandClasses = [];

            foreach ($commandFiles as $filename) {
                require_once $filename;
                $className = 'App\\Discord\\src\\Commands\\' . self::getClassNameFromFilename($filename);
                $commandClasses[strtolower((new \ReflectionClass($className))->getShortName())] = new $className();
                $commandClass = $commandClasses[strtolower((new \ReflectionClass($className))->getShortName())];
                $commandCooldown = $commandClass->getCooldown() ?? 5;
            }

            self::$commandCache = $commandClasses;
        }

        return self::$commandCache[$command] ?? null;
    }

    private static function scanDirectory($directory): array
    {
        $files = [];

        foreach (scandir($directory) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $directory . '/' . $item;
            $files = array_merge($files, is_dir($path) ? self::scanDirectory($path) : (pathinfo($path, PATHINFO_EXTENSION) === 'php' ? [$path] : []));
        }

        return $files;
    }

    private static function getClassNameFromFilename($filename): string
    {
        return str_replace('/', '\\', substr($filename, strpos($filename, 'Commands') + 9, -4));
    }
}
