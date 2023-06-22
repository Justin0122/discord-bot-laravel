<?php

namespace App\Discord\src\Commands\Spotify;

use Discord\Parts\Interactions\Interaction;
use Discord\Builders\Components\ActionRow;
use App\Discord\src\Models\Spotify as SpotifyModel;
use App\Discord\src\Events\EphemeralResponse;
use App\Discord\src\Builders\MessageBuilder;
use App\Discord\src\Builders\ButtonBuilder;
use App\Discord\src\Builders\InitialEmbed;
use App\Discord\src\Events\Success;
use App\Discord\src\Events\Error;
use Discord\Discord;

class Spotify
{
    public function getDescription(): string
    {
        return 'Allow the bot to access your spotify account';
    }

    public function getOptions(): array
    {
        return [
            [
                'name' => 'select',
                'description' => 'Select an option',
                'type' => 3,
                'required' => true,
                'choices' => [
                    [
                        'name' => 'Login',
                        'value' => 'login'
                    ],
                    [
                        'name' => 'Logout',
                        'value' => 'logout'
                    ],
                    [
                        'name' => 'Me',
                        'value' => 'me'
                    ]
                ]
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

    public function handle(Interaction $interaction, Discord $discord, $user_id)
    {
        $optionRepository = $interaction->data->options;
        $login = $optionRepository['select']->value === 'login';
        $logout = $optionRepository['select']->value === 'logout';
        $me = $optionRepository['select']->value === 'me';
        $ephemeral = $optionRepository['ephemeral']->value ?? false;
        $guildId = $_ENV['DISCORD_GUILD_ID'];

        if ($guildId !== $interaction->guild_id) {
            Error::sendError($interaction, $discord, 'This command is not available in this server (yet)');
            return;
        }

        InitialEmbed::Send($interaction, $discord,'Fetching your data', true);

        $pid = pcntl_fork();
        if ($pid == -1) {
            die('could not fork');
        } else if ($pid) {
            //parent
        } else {
            $me = $this->connect($user_id);
            //child
            if ($login) {
                $this->login($interaction, $discord, $user_id, $me);
            } elseif ($logout) {
                $this->logout($interaction, $discord, $user_id, $me);
            } elseif ($me) {
                $this->me($interaction, $discord, $user_id, $ephemeral, $me);
            }
        }

    }

    private function login(Interaction $interaction, Discord $discord, $user_id, $me): void
    {
        if ($me){
            Error::sendError($interaction, $discord, 'You are already connected to Spotify', true, true);
            return;
        }

        $url = "https://accounts.spotify.com/authorize?client_id={$_ENV['SPOTIFY_CLIENT_ID']}&response_type=code&redirect_uri={$_ENV['SPOTIFY_REDIRECT_URI']}&scope=user-read-email%20user-read-private%20user-library-read%20user-top-read%20user-read-recently-played%20user-read-playback-state%20user-read-currently-playing%20user-follow-read%20user-read-playback-position%20playlist-read-private%20playlist-modify-public%20playlist-modify-private%20playlist-read-collaborative%20user-library-modify%20user-follow-modify%20user-modify-playback-state%20user-read-recently-played%20user-read-playback-state%20user-modify-playback-state%20user-read-currently-playing%20user-read-playback-position%20user-read-recently-played%20user-read-playback-state%20user-modify-playback-state%20user-read-currently-playing%20user-read-playback-position%20user-read-recently-played%20user-read-playback-state%20user-modify-playback-state%20user-read-currently-playing%20user-read-playback-position%20user-read-recently-played%20user-read-playback-state%20user-modify-playback-state&state={$user_id}";

        $builder = Success::sendSuccess($discord, 'Spotify', '[Click here to login](' . $url . ')', $interaction);

        $messageBuilder = MessageBuilder::buildMessage($builder);

        $interaction->sendFollowUpMessage($messageBuilder, true);
        $interaction->deleteOriginalResponse();
    }

    private function logout(Interaction $interaction, Discord $discord, $user_id): void
    {
        $this->connect($user_id);

        Error::sendError($interaction, $discord, 'Not implemented yet', true, true);
    }

    private function me(Interaction $interaction, Discord $discord, $user_id, $ephemeral, $me): void
    {
        if (!$me){
            Error::sendError($interaction, $discord, 'You are not connected to Spotify. Please use /spotify [Login] first', true);
        }
        $builder = Success::sendSuccess($discord, $me->display_name, '', $interaction);
        $builder->addField('Followers', $me->followers->total, true);
        $builder->addField('Country', $me->country, true);
        $builder->addField('Plan', $me->product, true);

        $spotify = new SpotifyModel();
        $topSongs = $spotify->getTopSongs($user_id, 3);

        if ($topSongs !== null && count($topSongs->items) > 0) {
            $topSongsField = "";
            foreach ($topSongs->items as $song) {
                $songName = $song->name;
                $artistName = $song->artists[0]->name;
                $songLink = $song->external_urls->spotify;
                $topSongsField .= "> [{$songName}]({$songLink}) - {$artistName}\n";
            }
            $builder->addField('Top Songs', $topSongsField, true);
        } else {
            $builder->addField('Top Songs', 'No songs found', true);
        }
        if (isset($me->images[0]->url)) {
            $builder->setThumbnail($me->images[0]->url);
        }

        $currentSong = $spotify->getCurrentSong($user_id);
        $builder->addField('Currently listening to',"> " . $currentSong->item->name . ' - ' . $currentSong->item->artists[0]->name, false ?? 'No song playing');

        $actionRow = ActionRow::new();
        ButtonBuilder::addLinkButton($actionRow, 'Open profile', $me->external_urls->spotify);

        if ($currentSong->item->external_urls->spotify) {
            ButtonBuilder::addLinkButton($actionRow, 'Listen along', $currentSong->item->external_urls->spotify);
        }

        $messageBuilder = MessageBuilder::buildMessage($builder, [$actionRow]);
        EphemeralResponse::send($interaction, $messageBuilder, $ephemeral, true);


    }

    private function connect($user_id): ?object
    {
        try {
            $me = new SpotifyModel();
            $me = $me->getMe($user_id);
            if (!$me) {
                return null;
            }
            return $me;
        } catch (\Exception $e) {

        }
        return null;
    }
}
