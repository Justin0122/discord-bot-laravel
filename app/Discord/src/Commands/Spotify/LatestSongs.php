<?php

namespace App\Discord\src\Commands\Spotify;

use App\Discord\src\Builders\InitialEmbed;
use Discord\Parts\Interactions\Interaction;
use App\Discord\src\Builders\MessageBuilder;
use App\Discord\src\Models\Spotify;
use App\Discord\src\Events\Success;
use App\Discord\src\Events\Error;
use Discord\Discord;
use App\Discord\src\SlashIndex;

class LatestSongs
{
    public function getName(): string
    {
        return 'latestsongs';
    }

    public function getDescription(): string
    {
        return 'Get the latest song from your liked songs';
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
        $optionRepository = $interaction->data->options;
        $amount = $optionRepository['amount']->value ?? 24;



        InitialEmbed::send($interaction, $discord, 'Please wait while we are fetching your last liked songs');

        $pid = pcntl_fork();
        if ($pid == -1) {
            die('could not fork');
        } else if ($pid) {
            //parent
        } else {
            //child
            $this->getLastLiked($user_id, $discord, $interaction, $amount);
        }
    }

    private function getLastLiked($user_id, $discord, Interaction $interaction, $amount): void
    {
        $spotify = new Spotify();
        $tracks = $spotify->getLatestSongs($user_id, $amount);

        if ($tracks === null) {
            Error::sendError($interaction, $discord, 'You have no liked songs', true, true);
        }

        $me = $spotify->getMe($user_id);

        $embedFields = [];
        foreach ($tracks->items as $item) {
            $track = $item->track;
            $embedFields[] = [
                'name' => $track->name,
                'value' => '[Song link](' . $track->external_urls->spotify . ') ' . PHP_EOL . 'Artist: ' . $track->artists[0]->name,
                'inline' => true,
            ];
        }

        $title = 'Your latest songs';
        $description = 'Your latest songs from ' . $me->display_name . PHP_EOL . 'Amount: ' . $amount;

        $builder = Success::sendSuccess($discord, $title, $description, $interaction);
        $builder->addFirstPage($embedFields);

        $messageBuilder = MessageBuilder::buildMessage($builder);
        $slashIndex = new SlashIndex($embedFields);
        $slashIndex->handlePagination(count($embedFields), $messageBuilder, $discord, $interaction, $builder, $title, $description, true);
    }


}
