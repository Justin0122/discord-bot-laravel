<?php

namespace App\Discord\src\Helpers;

use GuzzleHttp\Exception\ClientException;
use SpotifyWebAPI\SpotifyWebAPI;
use GuzzleHttp\Client;

class TokenHandler
{
    private $apiUrl;
    private $secureToken;
    private $client;

    public function __construct($apiUrl, $secureToken)
    {
        $this->apiUrl = $apiUrl;
        $this->secureToken = $secureToken;
        $this->client = new Client();
    }

    public function getTokens($discordId): bool|array
    {
        $apiUrl = $_ENV['API_URL'];
        $secure_token = $_ENV['SECURE_TOKEN'];
        $discord_id = $discordId;

        $link = "$apiUrl$discord_id?secure_token=$secure_token&discord_id=$discord_id";

        $client = new Client();

        try {
            $response = $client->request('GET', $link);
        } catch (ClientException $e) {
            return false;
        }
        $response = json_decode($response->getBody(), true);

        $users = $response['data']['attributes'];
        $discord_id = $users['discord_id'];
        $accessToken = $users['spotify_access_token'];
        $refreshToken = $users['spotify_refresh_token'];

        //request a new access token if the current one is expired
        $api = new SpotifyWebAPI();
        $api->setAccessToken($accessToken);
        try {
            $api->me();
        } catch (\SpotifyWebAPI\SpotifyWebAPIException $e) {
            $accessToken = $this->refreshAccessToken($discord_id, $refreshToken);
        }

        return [
            'discord_id' => $discord_id,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken
        ];
    }

    public function refreshAccessToken($discordId, $refreshToken)
    {
        $link = $this->apiUrl . '?discord_id=' . $discordId . '&secure_token=' . $this->secureToken . '&spotify_refresh_token=' . $refreshToken;
        $response = $this->client->request('GET', $link);
        $response = json_decode($response->getBody(), true);
        if (!isset($response['data']['attributes']['spotify_access_token'])) {
            return false;
        }
        return $response['data']['attributes']['spotify_access_token'];
    }
}
