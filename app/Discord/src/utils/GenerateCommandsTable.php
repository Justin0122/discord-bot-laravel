<?php
function generateCommandsTable(): void
{
    $jsonFile = __DIR__ . '/../../commands.json';
    $jsonData = file_get_contents($jsonFile);
    $commands = json_decode($jsonData, true);

    $startTag = '## Slash Commands';
    $endTag = '## Notes';

    $tableHeader = '## Slash Commands' . "\n\n" .
        '| Category         | Command                                        | Description                                                                |
|------------------|------------------------------------------------|----------------------------------------------------------------------------|';
    $tableRows = '';

    foreach ($commands as $command) {
        $commandName = $command['name'];
        $commandOptions = [];
        if (isset($command['options'])) {
            foreach ($command['options'] as $option) {
                $commandOptions[] = "[" .$option['name'] . "]";
            }
        }
        $commandName .= ' ' . implode(' ', $commandOptions);
        $commandDescription = $command['description'];
        $commandCategory = $command['category'];

        $tableRows .= "| {$commandCategory}   | `/{$commandName}`                       | {$commandDescription}                                 |\n";
    }

    $markdownTable = "$tableHeader\n$tableRows";

    $readMeFile = __DIR__ . '/../../README.md';
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
