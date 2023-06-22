<?php

namespace App\Discord\src\Commands\Weather;

use Discord\Parts\Interactions\Interaction;
use App\Discord\src\Builders\MessageBuilder;
use App\Discord\src\Events\Success;
use App\Discord\src\Models\Weather;
use App\Discord\src\Events\Error;
use Discord\Discord;

class Astronomy
{
    public function getDescription(): string
    {
        return 'Get the astronomical data for today';
    }

    public function getOptions(): array
    {
        return [
            [
                'name' => 'country',
                'description' => 'The country you want to get the astronomical data of',
                'type' => 3,
                'required' => true
            ],
            [
                'name' => 'city',
                'description' => 'The city you want to get the astronomical data of',
                'type' => 3,
                'required' => false
            ],
            [
                'name' => 'ephemeral',
                'description' => 'Send the message only to you (default: false)',
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
        return 30;
    }

    public function handle(Interaction $interaction, Discord $discord): void
    {
        $optionRepository = $interaction->data->options;
        $ephemeral = $optionRepository['ephemeral']->value ?? false;
        $country = ucfirst($optionRepository['country']->value);
        $city = ucfirst($optionRepository['city']->value) ?? null;

        $weather = new Weather();
        $astro = $weather->getAstro($country, $city);
        if (!$astro) {
            Error::sendError($interaction, $discord, 'Something went wrong while getting the astronomical data' . PHP_EOL . 'Please try again later.');
            return;
        }
        $location = $weather->getLocation($astro);

        $builder = Success::sendSuccess($discord, 'Astronomy', 'Here is the astronomical data for ' . $location['city'] . ', ' . $location['country'], $interaction);
        $builder->addField('Sunrise', $astro['astronomy']['astro']['sunrise'], true);
        $builder->addField('Sunset', $astro['astronomy']['astro']['sunset'], true);
        $builder->addField('Moonrise', $astro['astronomy']['astro']['moonrise'], true);
        $builder->addField('Moonset', $astro['astronomy']['astro']['moonset'], true);
        $builder->addField('Moon phase', $astro['astronomy']['astro']['moon_phase'], true);
        $builder->addField('Moon illumination', $astro['astronomy']['astro']['moon_illumination'] . '%', true);

        $messageBuilder = MessageBuilder::buildMessage($builder);
        $interaction->respondWithMessage($messageBuilder, $ephemeral);

    }
}
