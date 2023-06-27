<?php

namespace App\Discord\src\Helpers;

use App\Models\Command;
use App\Models\UserCooldown;

class CommandCooldownManager
{
    public static function checkCooldown($userId, $commandName, $cooldownDuration)
    {
        $commandCooldown = Command::where('command_name', $commandName)->first();
        if ($commandCooldown) {
            $userCooldown = UserCooldown::where('discord_id', $userId)->where('command_id', $commandCooldown->id)->first();
            $currentTimestamp = time();

            if ($userCooldown) {
                $createdAtTimestamp = $userCooldown->created_at->getTimestamp();
                $timeElapsed = $currentTimestamp - $createdAtTimestamp;

                // Check if the cooldown period has elapsed
                if ($timeElapsed < $cooldownDuration) {
                    return $cooldownDuration - $timeElapsed;
                } else {
                    // Cooldown period has elapsed, delete the user cooldown
                    $userCooldown->delete();
                }
            }

            // Create a new cooldown for the user with the current timestamp
            UserCooldown::create([
                'discord_id' => $userId,
                'command_id' => $commandCooldown->id,
                'created_at' => $currentTimestamp,
            ]);
        }

        return 0;
    }
}
