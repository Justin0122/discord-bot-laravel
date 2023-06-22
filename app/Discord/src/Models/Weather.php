<?php

namespace App\Discord\src\Models;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class Weather
{

    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->apiUrl = $_ENV['WEATHER_API_URL'];
        $this->apiKey = $_ENV['WEATHER_API_KEY'];
    }

    public function getWeather($country, $city = null)
    {
        $link = "$this->apiUrl/current.json?key=$this->apiKey&q=$city,$country";

        $client = new Client();

        try {
            $response = $client->request('GET', $link);
            $response = json_decode($response->getBody(), true);
        } catch (ClientException|GuzzleException $e) {
            return null;
        }
        return $response;
    }

    public function getForecast($country, $city = null)
    {
        $link = "$this->apiUrl/forecast.json?key=$this->apiKey&q=$city,$country&days=3&alerts=yes";

        $client = new Client();

        try {
            $response = $client->request('GET', $link);
            $response = json_decode($response->getBody(), true);
        } catch (ClientException|GuzzleException $e) {
            return null;
        }
        return $response;
    }

    public function getAstro(string $country, ?string $city)
    {
        $link = "$this->apiUrl/astronomy.json?key=$this->apiKey&q=$city,$country";

        $client = new Client();

        try {
            $response = $client->request('GET', $link);
            $response = json_decode($response->getBody(), true);
        } catch (ClientException|GuzzleException $e) {
            return null;
        }
        return $response;
    }

    public function getLocation(array $response): array | null
    {
        try{
        $location = $response['location'];
        $country = $location['country'];
        $city = $location['name'];
        $region = $location['region'];
        } catch (\Exception $e) {
            return null;
        }

        return [
            'country' => $country,
            'city' => $city,
            'region' => $region
        ];

    }


}
