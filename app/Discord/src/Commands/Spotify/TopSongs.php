<?php

namespace App\Discord\src\Commands\Spotify;

use Discord\Parts\Interactions\Interaction;
use App\Discord\src\Builders\MessageBuilder;
use App\Discord\src\Builders\InitialEmbed;
use App\Discord\src\Models\Spotify;
use App\Discord\src\Events\Success;
use App\Discord\src\Events\Error;
use Discord\Discord;
use App\Discord\src\SlashIndex;

class TopSongs
{
    public function getDescription(): string
    {
        return 'Get the top songs from your liked songs';
    }

    public function getOptions(): array
    {
        return [
            [
                'name' => 'amount',
                'description' => 'amount of songs (default 24 max 50)',
                'type' => 4,
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
        return 60;
    }

    public function handle(Interaction $interaction, Discord $discord, $user_id): void
    {
        InitialEmbed::send($interaction, $discord, 'Please wait while we are fetching your top songs');

        $pid = pcntl_fork();
        if ($pid == -1) {
            die('could not fork');
        } else if ($pid) {
            //parent
        } else {
            //child
            $this->getTopSongs($user_id, $discord, $interaction);
        }

    }

    private function getTopSongs($user_id, $discord, Interaction $interaction): void
    {

        $optionRepository = $interaction->data->options;
        $amount = $optionRepository['amount']->value ?? 24;

        $spotify = new Spotify();
        $tracks = $spotify->getTopSongs($user_id, $amount);
        $me = $spotify->getMe($user_id);

        if ($tracks === null) {
            Error::sendError($interaction, $discord, 'You have no liked songs');
        }

        $embedFields = [];
        foreach ($tracks->items as $item) {
            $track = $item;
            $embedFields[] = [
                'name' => $track->name,
                'value' => '[Song link](' . $track->external_urls->spotify . ') ' . PHP_EOL . 'Artist: ' . $track->artists[0]->name,
                'inline' => true,
            ];
        }

        $title = 'Your top songs';
        $description = 'Top songs from ' . $me->display_name . PHP_EOL . 'Amount: ' . $amount;
        $builder = Success::sendSuccess($discord, $title, $description, $interaction);
        $builder->addFirstPage($embedFields);

        $messageBuilder = MessageBuilder::buildMessage($builder);
        $slashIndex = new SlashIndex($embedFields);
        $slashIndex->handlePagination(count($embedFields), $messageBuilder, $discord, $interaction, $builder, $title, $description, true);

    }
}
