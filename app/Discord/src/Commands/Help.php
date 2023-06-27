<?php

namespace App\Discord\src\Commands;

use Discord\Parts\Interactions\Interaction;
use App\Discord\src\Events\EphemeralResponse;
use App\Discord\src\Builders\MessageBuilder;
use App\Discord\src\Events\Error;
use App\Discord\src\Events\Info;
use Discord\Discord;
use App\Discord\src\SlashIndex;
use App\Models\Command;
use App\Models\CommandOption;

class Help
{
    public function getDescription(): string
    {
        return 'Show all commands';
    }

    public function getOptions(): array
    {
        $commands = Command::where('public', 1)->get(['command_name'])->toArray();
        $choices = [];

        foreach ($commands as $command) {
            $choices[] = [
                'name' => $command['command_name'],
                'value' => $command['command_name']
            ];
        }

        return [
            [
                'name' => 'command',
                'description' => 'command to show help for',
                'type' => 3,
                'required' => false,
                'choices' => $choices
            ],
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
        return 10;
    }

    public function handle(Interaction $interaction, Discord $discord): void
    {
        $commands = Command::where('public', 1)->get()->sortBy('category')->toArray();

        $options = $interaction->data->options;
        $command = $options['command']?->value;
        $ephemeral = $options['ephemeral']?->value ?? false;

        if (!$command && $ephemeral) {
            Error::sendError($interaction, $discord, "You can't use the ephemeral option without a command");
            return;
        }

        $embedFields = [];

        $perPage = 4;

        if ($command === null) {
            foreach ($commands as $command) {
                $command['options'] = CommandOption::where('command_id', $command['id'])->get()->toArray();
                $embedFields = $this->getFields($command, $embedFields);
            }
            $title = 'Help';
        } else {
            $count = 0;
            $title = 'Help for: **' . $command . '**' . PHP_EOL . PHP_EOL;
            foreach ($commands as $command) {
                $command['options'] = CommandOption::where('command_id', $command['id'])->get()->toArray();
                if ($command['command_name'] === $options['command']->value) {
                    $embedFields = $this->getFields($command, $embedFields, $count);
                    $count++;
                }
            }
        }

        $builder = Info::sendInfo($discord, $title, '', $interaction);

        if (count($embedFields) <= $perPage) {
            $builder->addFirstPage($embedFields, $perPage);
        }

        $messageBuilder = MessageBuilder::buildMessage($builder);

        if ($ephemeral) {
            EphemeralResponse::send($interaction, $messageBuilder, $ephemeral);
            return;
        }

        $slashIndex = new SlashIndex($embedFields);
        $slashIndex->setTotalPerPage($perPage);
        $slashIndex->handlePagination(count($embedFields), $messageBuilder, $discord, $interaction, $builder, $title,'');
    }

    public function getFields(array $command, array $embedFields, ?int $count = null): array
    {
        $options = '';
        if (!empty($command['options'])) {
            foreach ($command['options'] as $option) {
                $prefix = $option['required'] ? '-' : '+';
                $options .= "{$prefix} {$option['name']}\n";
            }
        } else {
            $options = 'No options';
        }

        $embedFields[] = [
            'name' => '/' . $command['command_name'],
            'value' => "```{$command['command_description']}```\n```diff\n{$options}```\n```fix\nCooldown: {$command['cooldown']} seconds```",
            'inline' => false
        ];

        return $embedFields;
    }
}
