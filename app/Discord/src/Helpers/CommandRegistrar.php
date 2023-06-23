<?php

namespace App\Discord\src\Helpers;

use App\Discord\Bot;
use App\Models\CommandCooldown;
use App\Models\CommandOption;
use Discord\Parts\Interactions\Command\Command;
use Discord\Discord;

class CommandRegistrar
{
    /**
     * @throws \Exception
     */
    private static function registerCommand(Command $command, ?string $guildId): void
    {
        if ($guildId) {
            $guild = Bot::getDiscord()?->guilds?->offsetGet($guildId);

            if ($guild) {
                $guild->commands->save($command);
                echo "Registered command: {$command->name} to guild: {$guildId}\n";
            }
        } else {
            Bot::getDiscord()?->application?->commands->save($command);
            echo "Registered command: {$command->name}\n";
        }
    }

    private static function registerCommandOptions(int $commandId, array $options): void
    {
        foreach ($options as $option) {
            $optionData = [
                'command_id' => $commandId,
                'name' => $option['name'],
                'description' => $option['description'],
                'required' => $option['required'],
                'type' => $option['type'],
                'choices' => isset($option['choices']) ? json_encode($option['choices']) : null,
                'options' => isset($option['options']) ? json_encode($option['options']) : null,
                'channel_types' => isset($option['channel_types']) ? json_encode($option['channel_types']) : null,
                'min_value' => $option['min_value'] ?? null,
                'max_value' => $option['max_value'] ?? null,
                'min_length' => $option['min_length'] ?? null,
                'max_length' => $option['max_length'] ?? null,
                'autocomplete' => $option['autocomplete'] ?? null,
            ];

            CommandOption::updateOrCreate(
                ['command_id' => $commandId, 'name' => $option['name']],
                $optionData
            );
        }
    }

    /**
     * @throws \Exception
     */
    public static function register(Discord $discord): void
    {
        $phpFiles = self::scanDirectory(__DIR__ . '/../Commands');

        foreach ($phpFiles as $phpFile) {
            require_once $phpFile;

            $className = 'App\\Discord\\src\\Commands\\' . self::getClassNameFromFilename($phpFile);
            if (class_exists($className)) {
                $commandInstance = new $className();
                $folderName = explode('/', $phpFile)[count(explode('/', $phpFile)) - 2];

                $name = self::getShortName($className);
                $description = $commandInstance->getDescription();
                $options = $commandInstance->getOptions();
                $guildId = $commandInstance->getGuildId();

                $commandData = compact('name', 'description', 'options') + ['default_permission' => true];
                $command = new Command(Bot::getDiscord(), $commandData);

                self::registerCommand($command, $guildId);

                $commandCooldown = $commandInstance->getCooldown();
                if ($commandCooldown) {
                    $public = $guildId ? 0 : 1;

                    $commandCooldown = CommandCooldown::updateOrCreate(
                        ['command_name' => $name],
                        [
                            'category' => $folderName ?? 'Uncategorized',
                            'command_description' => $description,
                            'cooldown' => $commandCooldown,
                            'public' => $public
                        ]
                    );

                    $commandId = $commandCooldown->id;

                    if ($options) {
                        self::registerCommandOptions($commandId, $options);
                    }
                }
            }
        }
    }

    private static function getShortName(string $className): string
    {
        $className = trim($className, '\\');
        $lastBackslashPos = strrpos($className, '\\');
        $className = ($lastBackslashPos === false) ? $className : substr($className, $lastBackslashPos + 1);
        $commandName = strtolower($className);

        return $commandName;
    }

    private static $commandCache = null;
    private static $commandOptionsCache = [];

    /**
     * @throws \Exception
     */
    public static function getCommandByName($command)
    {
        if (self::$commandCache === null) {
            $commandFiles = self::scanDirectory(__DIR__ . '/../Commands');
            $commandClasses = [];

            foreach ($commandFiles as $filename) {
                require_once $filename;
                $className = 'App\\Discord\\src\\Commands\\' . self::getClassNameFromFilename($filename);
                $commandClasses[strtolower(self::getShortName($className))] = new $className();
            }

            self::$commandCache = $commandClasses;
        }

        return self::$commandCache[$command] ?? null;
    }

    /**
     * @throws \Exception
     */
    public static function getCommandOptionsByName($command)
    {
        if (!isset(self::$commandOptionsCache[$command])) {
            $commandInstance = self::getCommandByName($command);

            if ($commandInstance) {
                $commandOptions = $commandInstance->getOptions();
                self::$commandOptionsCache[$command] = $commandOptions;
            } else {
                self::$commandOptionsCache[$command] = null;
            }
        }

        return self::$commandOptionsCache[$command];
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
