<?php

namespace App\Discord\src\Utils;

use App\Models\CommandCooldown;

class GenerateCommandsTable
{
    public static function generateCommandsTable(): void
    {
        $startTag = '## Slash Commands';
        $endTag = '## Notes';

        $tableHeader = '## Slash Commands' . "\n\n" .
            '| Category         | Command                                        | Description                                                                |
|------------------|------------------------------------------------|----------------------------------------------------------------------------|';
        $tableRows = '';

        $commands = CommandCooldown::with('options')->where('public', 1)->orderBy('category')->get();

        foreach ($commands as $command) {
            $commandName = $command->command_name;
            $commandOptions = $command->options->pluck('name')->toArray();
            $modifiedCommandOptions = [];
            if (isset($commandOptions)) {
                foreach ($commandOptions as $option) {
                    $modifiedCommandOptions[] = "[" . $option . "]";
                }
            }
            $commandName .= ' ' . implode(' ', $modifiedCommandOptions);
            $commandDescription = $command->command_description;
            $commandCategory = $command->category;

            $tableRows .= "| {$commandCategory}   | `/{$commandName}`                       | {$commandDescription}                                 |\n";
        }

        $markdownTable = "$tableHeader\n$tableRows";

        $readMeFile = __DIR__ . '/../../../../README.md';
        $readMeContent = file_get_contents($readMeFile);

        $startPos = strpos($readMeContent, $startTag);
        $endPos = strpos($readMeContent, $endTag);

        // Check if the start and end positions are found
        if ($startPos !== false && $endPos !== false && $startPos < $endPos) {
            $existingTable = substr($readMeContent, $startPos, $endPos - $startPos);
            $readMeContent = str_replace($existingTable, $markdownTable, $readMeContent);

            file_put_contents($readMeFile, $readMeContent);
        }
    }
}
