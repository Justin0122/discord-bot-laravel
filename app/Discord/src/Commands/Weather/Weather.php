<?php

namespace App\Discord\src\Commands\Weather;

use Discord\Parts\Interactions\Interaction;
use App\Discord\src\Builders\MessageBuilder;
use App\Discord\src\Events\Success;
use App\Discord\src\Models\Weather as WeatherModel;
use App\Discord\src\Events\Error;
use Discord\Discord;

class Weather
{
    public function getDescription(): string
    {
        return 'Get the current weather';
    }

    public function getOptions(): array
    {
        return [
            [
                'name' => 'country',
                'description' => 'The country you want to get the weather of',
                'type' => 3,
                'required' => true
            ],
            [
                'name' => 'city',
                'description' => 'The city you want to get the weather of',
                'type' => 3,
                'required' => false
            ],
            [
                'name' => 'country2',
                'description' => 'The country you want to get the weather of',
                'type' => 3,
                'required' => false
            ],
            [
                'name' => 'city2',
                'description' => 'The city you want to get the weather of',
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
        $country = isset($optionRepository['country']) ? ucfirst($optionRepository['country']->value) : null;
        $city = isset($optionRepository['city']) ? ucfirst($optionRepository['city']->value) : null;
        $country2 = isset($optionRepository['country2']) ? ucfirst($optionRepository['country2']->value) : null;
        $city2 = isset($optionRepository['city2']) ? ucfirst($optionRepository['city2']->value) : null;


        $weather = new WeatherModel();
        $currentWeather = $weather->getWeather($country, $city);

        if (!$currentWeather) {
            Error::sendError($interaction, $discord, 'Something went wrong while getting the weather.' . PHP_EOL . 'Please try again later.');
            return;
        }


        if ($country2 && $city2) {
            $currentWeather2 = $weather->getWeather($country2, $city2);
        }

        $message = 'Current weather for ' . ucfirst($currentWeather['location']['name']) . ', ' . ucfirst($currentWeather['location']['country']);


        $currentWeather = $currentWeather['current'];
        if ($country2 && $city2) {
            $currentWeather2 = $currentWeather2['current'];
        }

        $builder = Success::sendSuccess($discord, 'Current weather', $message, $interaction);
        $builder->addField('Temperature', $currentWeather['temp_c'] . '°C', true);
        $builder->addField('Condition', $currentWeather['condition']['text'], true);
        $builder->addField('Wind', $currentWeather['wind_kph'] . 'km/h', true);
        $builder->addField('Feels like', $currentWeather['feelslike_c'] . '°C', true);
        $builder->addField('Humidity', $currentWeather['humidity'] . '%', true);
        $builder->addField('Precipitation', $currentWeather['precip_mm'] . 'mm', true);
        if ($country2 && $city2) {
            $builder->addField($city2 . ', ' . $country2, '------------------', false);
            $builder->addField('Temperature', $currentWeather2['temp_c'] . '°C', true);
            $builder->addField('Condition', $currentWeather2['condition']['text'], true);
            $builder->addField('Wind', $currentWeather2['wind_kph'] . 'km/h', true);
            $builder->addField('Feels like', $currentWeather2['feelslike_c'] . '°C', true);
            $builder->addField('Humidity', $currentWeather2['humidity'] . '%', true);
            $builder->addField('Precipitation', $currentWeather2['precip_mm'] . 'mm', true);
        }

        $image = $currentWeather['condition']['icon'];
        $builder->setThumbnail('https:' . $image);

        $messageBuilder = MessageBuilder::buildMessage($builder);

        $interaction->respondWithMessage($messageBuilder, $ephemeral);


    }

}
