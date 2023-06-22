<?php

namespace App\Discord\src\Helpers;

use SpotifyWebAPI\SpotifyWebAPI;
use SpotifyWebAPI\Session;

class SessionHandler
{
    public function setSession($user_id): SpotifyWebAPI | bool
    {
        $api = new SpotifyWebAPI();
        $session = new Session(
            $_ENV['SPOTIFY_CLIENT_ID'],
            $_ENV['SPOTIFY_CLIENT_SECRET'],
            $_ENV['SPOTIFY_REDIRECT_URI']
        );
        $api->setSession($session);

        //set the access token
        $tokenHandler = new TokenHandler($_ENV['API_URL'], $_ENV['SECURE_TOKEN']);
        $tokens = $tokenHandler->getTokens($user_id);
        if (!$tokens) {
            return false;
        }
        $session->refreshAccessToken($tokens['refresh_token']);
        $accessToken = $session->getAccessToken();
        $api->setAccessToken($accessToken);

        return $api;
    }
}
