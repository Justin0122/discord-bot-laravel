<?php

namespace App\Discord\src\Helpers;

use Discord\Discord;

class RemoveAllCommands
{
    /**
     * @throws \Exception
     */
    public static function deleteAllCommands(Discord $discord): void
    {
        $discord->application->commands->freshen()->done(function ($commands) {
            foreach ($commands as $command) {
                echo "Deleting command: {$command->name}", PHP_EOL;
                $commands->delete($command);
            }
        });
    }
}
