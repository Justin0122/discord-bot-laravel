<?php

namespace App\Discord\src\Commands\Weather;

use Discord\Parts\Interactions\Interaction;
use App\Discord\src\Builders\MessageBuilder;
use App\Discord\src\Events\Success;
use App\Discord\src\Models\Weather;
use App\Discord\src\Events\Error;
use Discord\Discord;
use App\Discord\src\SlashIndex;

class Forecast
{
    public function getDescription(): string
    {
        return 'Get the forecast for the next 3 days';
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
        return 20;
    }

    public function handle(Interaction $interaction, Discord $discord): void
    {
        $optionRepository = $interaction->data->options;
        $country = ucfirst($optionRepository['country']->value);
        $city = ucfirst($optionRepository['city']->value) ?? null;
        $ephemeral = $optionRepository['ephemeral']->value ?? false;

        $weather = new Weather();
        $forecast = $weather->getForecast($country, $city);
        if (!$forecast) {
            Error::sendError($interaction, $discord, 'Something went wrong while getting the forecast.' . PHP_EOL . 'Please try again later.');
            return;
        }
        $location = $weather->getLocation($forecast);

        $description = 'Here is the forecast for the next 3 days.';
        $title = 'Forecast for ' . $location['city'] . ', ' . $location['country'];
        $builder = Success::sendSuccess($discord, $title, $description , $interaction);
        $forecast = $forecast['forecast']['forecastday'];

        $embedFields = [];
        $avgTemp = 0;
        $avgHumidity = 0;

        foreach ($forecast as $day) {
            // Check if there are any alerts for the day
            if (isset($day['alerts'])) {
                $alert = $day['alerts'][0];
                $alert['date'] = date('l \t\h\e jS \o\f F', strtotime($alert['date']));
            }

            $alertField = isset($day['alerts']) ? '- ' : '+ ';
            $alertMessage = isset($day['alerts']) ? $alert['event'] : 'No alerts';

            $embedFields[] = [
                'name' =>  $day['date'] = date('l \t\h\e jS \o\f F', strtotime($day['date'])),
                'value' => "```diff
Max temp: {$day['day']['maxtemp_c']}°C
Min temp: {$day['day']['mintemp_c']}°C
Average temp: {$day['day']['avgtemp_c']}°C
Max wind speed: {$day['day']['maxwind_kph']}kph
Total precipitation: {$day['day']['totalprecip_mm']}mm
Average humidity: {$day['day']['avghumidity']}%
Condition: {$day['day']['condition']['text']}
{$alertField}Alert: {$alertMessage}
```",
                'inline' => false
            ];


            $avgTemp += $day['day']['avgtemp_c'];
            $avgHumidity += $day['day']['avghumidity'];
        }

        $builder->addField('Average temp (3 days)', round($avgTemp / 3, 2) . '°C', true);
        $builder->addField('Average humidity (3 days)', round($avgHumidity / 3, 2) . '%', true);
        $builder->addLineBreak();

        foreach ($forecast as $day) {
            $day['date'] = date('l \t\h\e jS \o\f F', strtotime($day['date']));
            $builder->addField(
                'Condition for: ' . $day['date'],
                isset($day['alerts']) ? $day['alerts'][0]['event'] : "```
Condition: {$day['day']['condition']['text']}
Average temp: {$day['day']['avgtemp_c']}°C
Total precipitation: {$day['day']['totalprecip_mm']}mm rain
```",
                false
            );
        }

        $messageBuilder = MessageBuilder::buildMessage($builder);
        if ($ephemeral) {
            $interaction->respondWithMessage($messageBuilder, true);
            return;
        }
        $slashIndex = new SlashIndex($embedFields);
        $slashIndex->setTotalPerPage(1);
        $slashIndex->handlePagination(count($embedFields), $messageBuilder, $discord, $interaction, $builder, $title, '', '', true);

    }
}
