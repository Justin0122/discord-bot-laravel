<?php

namespace App\Discord\src\Commands\Spotify;

use App\Discord\src\Builders\ButtonBuilder;
use Discord\Builders\Components\ActionRow;
use Discord\Parts\Interactions\Interaction;
use App\Discord\src\Events\EphemeralResponse;
use App\Discord\src\Builders\MessageBuilder;
use App\Discord\src\Builders\InitialEmbed;
use App\Discord\src\Events\Success;
use App\Discord\src\Models\Spotify;
use App\Discord\src\Events\Error;
use Discord\Discord;

class Currentsong
{
    public function getDescription(): string
    {
        return 'Share the song you are currently listening to';
    }

    public function getOptions(): array
    {
        return [
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
        return 30;
    }

    public function handle(Interaction $interaction, Discord $discord, $user_id): void
    {
        InitialEmbed::Send($interaction, $discord, 'Please wait while we are fetching your current song', true);

        $pid = pcntl_fork();
        if ($pid == -1) {
            die('could not fork');
        } else if ($pid) {
            //parent
        } else {
            //child
            $this->getCurrentSong($user_id, $discord, $interaction);
        }
    }

    private function getCurrentSong($user_id, $discord, Interaction $interaction): void
    {
        $optionRepository = $interaction->data->options;
        $ephemeral = $optionRepository['ephemeral']->value ?? false;
        $spotify = new Spotify();
        $tracks = $spotify->getCurrentSong($user_id);
        $me = $spotify->getMe($user_id);

        if (!isset($tracks->item)) {
            Error::sendError($interaction, $discord, 'You are not listening to any song', true, true);
            return;
        }

        $builder = Success::sendSuccess($discord, $me->display_name . ' is listening to:', '', $interaction);

        $builder->addField('Song', $tracks->item->name, true);
        $builder->addField('Artist', $tracks->item->artists[0]->name, true);
        $builder->addField('Album', $tracks->item->album->name, true);
        $builder->addField('Duration', gmdate("i:s", $tracks->item->duration_ms / 1000), true);
        $actionRow = ActionRow::new();
        ButtonBuilder::addLinkButton($actionRow, 'Listen on Spotify', $tracks->item->external_urls->spotify);

        $builder->setThumbnail($tracks->item->album->images[0]->url);

        $builder->setUrl($tracks->item->external_urls->spotify);

        $messageBuilder = new \Discord\Builders\MessageBuilder();
        $messageBuilder->addEmbed($builder->build());
        $messageBuilder = MessageBuilder::buildMessage($builder, [$actionRow]);

        EphemeralResponse::send($interaction, $messageBuilder, $ephemeral, true);

    }

}
