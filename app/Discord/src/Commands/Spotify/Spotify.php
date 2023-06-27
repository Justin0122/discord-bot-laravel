<?php

namespace App\Discord\src\Commands\Spotify;

use App\Jobs\SpotifyUser;
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
        $me = SpotifyModel::connect($user_id);


        switch (true) {
            case $login:
                $this->login($interaction, $discord, $user_id, $me);
                break;
            case $logout:
                $this->logout($interaction, $discord, $user_id, $me);
                break;
            case $me:
                InitialEmbed::Send($interaction, $discord, 'Please wait while we are fetching your profile', true);

                SpotifyUser::dispatch($user_id, $interaction, $ephemeral);

                break;
        }
    }

    public function me(Interaction $interaction, $user_id, $channel_id): void
    {
    }

    private function login(Interaction $interaction, Discord $discord, $user_id, $me): void
    {
        if ($me){
            Error::sendError($interaction, $discord, 'You are already connected to Spotify');
            return;
        }

        $url = "https://accounts.spotify.com/authorize?client_id={$_ENV['SPOTIFY_CLIENT_ID']}&response_type=code&redirect_uri={$_ENV['SPOTIFY_REDIRECT_URI']}&scope=user-read-email%20user-read-private%20user-library-read%20user-top-read%20user-read-recently-played%20user-read-playback-state%20user-read-currently-playing%20user-follow-read%20user-read-playback-position%20playlist-read-private%20playlist-modify-public%20playlist-modify-private%20playlist-read-collaborative%20user-library-modify%20user-follow-modify%20user-modify-playback-state%20user-read-recently-played%20user-read-playback-state%20user-modify-playback-state%20user-read-currently-playing%20user-read-playback-position%20user-read-recently-played%20user-read-playback-state%20user-modify-playback-state%20user-read-currently-playing%20user-read-playback-position%20user-read-recently-played%20user-read-playback-state%20user-modify-playback-state%20user-read-currently-playing%20user-read-playback-position%20user-read-recently-played%20user-read-playback-state%20user-modify-playback-state&state={$user_id}";

        $builder = Success::sendSuccess($discord, 'Spotify', '[Click here to login](' . $url . ')', $interaction);

        $messageBuilder = MessageBuilder::buildMessage($builder);

        $interaction->respondWithMessage($messageBuilder, true);
    }

    private function logout(Interaction $interaction, Discord $discord): void
    {
        Error::sendError($interaction, $discord, 'Not implemented yet');
    }

}
