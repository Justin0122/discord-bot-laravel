<?php

namespace App\Discord\src\Commands\Spotify;

use App\Jobs\PlaylistGenerator;
use Discord\Builders\Components\ActionRow;
use Discord\Parts\Interactions\Interaction;
use App\Discord\src\Events\EphemeralResponse;
use App\Discord\src\Builders\MessageBuilder;
use App\Discord\src\Builders\ButtonBuilder;
use App\Discord\src\Builders\InitialEmbed;
use App\Discord\src\Models\Spotify;
use App\Discord\src\Events\Success;
use App\Discord\src\Events\Error;
use Discord\Discord;
use DateTime;

class PlaylistGen
{

    public function getDescription(): string
    {
        return 'Generate a playlist from within a time frame';
    }

    public function getOptions(): array
    {
        return [
            [
                'name' => 'startdate',
                'description' => 'Start date (YYYY-MM-DD)',
                'type' => 3,
                'required' => true,
            ],
            [
                'name' => 'public',
                'description' => 'Make playlist public',
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
        return 120;
    }

    /**
     * @throws \Exception
     */

    public function handle(Interaction $interaction, Discord $discord, $user_id): void
    {
        $optionRepository = $interaction->data->options;
        $startDate = $optionRepository['startdate']->value;
        $public = $optionRepository['public']->value ?? false;

        if ($startDate && new DateTime($startDate) > new DateTime()) {
            Error::sendError($interaction, $discord, 'Start date cannot be in the future');
            return;
        }

        if ($startDate && new DateTime($startDate) < new DateTime('2015-01-01')) {
            Error::sendError($interaction, $discord, 'Start date cannot be before 2015');
            return;
        }




        $startDateString = $startDate ?? null;
        $dates = $this->calculateMonthRange($startDateString);
        $startDate = $dates['startDate'];
        $endDate = $dates['endDate'];

        //if the month is the same as the current month, we can't generate a playlist
        if ($startDate->format('m') == (new DateTime())->format('m')) {
            Error::sendError($interaction, $discord, 'You cannot generate a playlist for the current month');
            return;
        }


        InitialEmbed::Send($interaction, $discord, 'Please wait', true)->done(function () use ($startDate, $endDate, $interaction, $discord, $user_id, $public) {
            PlaylistGenerator::dispatch($user_id, $interaction, $startDate, $endDate, $public, 'liked');
        });

    }

    /**
     * @throws \Exception
     */
    function calculateMonthRange($startDateString = null): array
    {
        $startDate = $startDateString ? new DateTime($startDateString) : new DateTime();
        $currentMonth = $startDate->format('m');
        $currentYear = $startDate->format('Y');
        $startDate->setDate($currentYear, $currentMonth, 1);
        $endDate = clone $startDate;
        $endDate->modify('+1 month');
        $endDate->modify('-1 day');

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

}
