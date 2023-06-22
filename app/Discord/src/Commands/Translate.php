<?php

namespace App\Discord\src\Commands;

use Discord\Parts\Interactions\Interaction;
use App\Discord\src\Builders\MessageBuilder;
use App\Discord\src\Events\Success;
use Discord\Discord;

class Translate
{
    public function getDescription(): string
    {
        return 'translate text';
    }

    public function getOptions(): array
    {
        return [
            [
                'name' => 'text',
                'description' => 'text to translate',
                'type' => 3,
                'required' => true
            ],
            [
                'name' => 'to',
                'description' => 'language to translate to',
                'type' => 3,
                'required' => true
            ],
            [
                'name' => 'from',
                'description' => 'language to translate from (default: auto. Might result in wrong translation!)',
                'type' => 3,
                'required' => false
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
        return 5;
    }

    public function handle(Interaction $interaction, Discord $discord): void
    {
        $optionRepository = $interaction->data->options;
        $ephemeral = $optionRepository['ephemeral']->value ?? false;
        $text = $optionRepository['text']->value;
        $to = $optionRepository['to']->value;
        $from = $optionRepository['from']->value ?? 'auto';

        exec('trans -b -t ' . $to . ' "' . $text . '" -s ' . $from, $output);
        $translation = implode(' ', $output);

        $builder = Success::sendSuccess($discord, 'Translating: "' . $text . '" to "' . $to . '" from "' . $from . '"', "```". $translation . "```");
        $messageBuilder = MessageBuilder::buildMessage($builder);
        $interaction->respondWithMessage($messageBuilder, $ephemeral);

    }

}
