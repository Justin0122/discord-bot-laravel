<?php

namespace App\Discord\src\Models;

use App\Discord\src\Helpers\SessionHandler;
use App\Discord\src\Helpers\TokenHandler;
use DateTime;
use Dotenv\Dotenv;

class Spotify
{
    private TokenHandler $tokenHandler;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../../');
        $dotenv->load();
        $this->tokenHandler = new TokenHandler($_ENV['API_URL'], $_ENV['SECURE_TOKEN']);
    }

    public function checkLimit($amount)
    {
        if ($amount > 50) {
            $amount = 50;
        } else if (!$amount) {
            $amount = 24;
        }
        return $amount;
    }

    private function getTokens($user_id): bool|array
    {
        return $this->tokenHandler->getTokens($user_id);
    }

    public function getLatestSongs($user_id, $amount): object|array|null
    {
        $amount = $this->checkLimit($amount);

        $api = (new SessionHandler())->setSession($user_id);
        return $api->getMySavedTracks([
            'limit' => $amount,
        ]);
    }

    public function getMe($user_id): object|array|null
    {
        $api = (new SessionHandler())->setSession($user_id);
        return $api->me();
    }

    public function getCurrentSong($user_id): object|array|null
    {
        $tokens = $this->getTokens($user_id);
        if (!$tokens) {
            return null;
        }
        $api = (new SessionHandler())->setSession($user_id);
        if ($api->getMyCurrentTrack() == null) {
            return null;
        }
        return $api->getMyCurrentTrack();
    }

    public function getTopSongs($user_id, $amount): object|array|null
    {
        $amount = $this->checkLimit($amount);
        $api = (new SessionHandler())->setSession($user_id);
        $topTracks = $api->getMyTop('tracks', [
            'limit' => $amount,
        ]);
        return $topTracks;
    }

    /**
     * @throws \Exception
     */
    public function generatePlaylist($user_id, $startDate, $endDate, $public): bool|array
    {
        $api = (new SessionHandler())->setSession($user_id);
        $totalTracks = 250; // Total number of tracks to fetch
        $limit = 50; // Number of tracks per request
        $offset = 0; // Initial offset

        $trackUris = []; // Array to store track URIs

        // Fetch tracks in batches until there are no more tracks available
        while (count($trackUris) < $totalTracks) {
            $tracks = $api->getMySavedTracks([
                'limit' => $limit,
                'offset' => $offset,
                'time_range' => 'short_term'
            ]);

            $addedAt = new DateTime($tracks->items[0]->added_at);
            if ($addedAt < $startDate || (empty($tracks->items))) {
                // We've gone past the start date or there are no more tracks, so stop fetching
                break;
            }


            $filteredTracks = array_filter($tracks->items, function ($item) use ($startDate, $endDate) {
                $addedAt = new DateTime($item->added_at);
                return $addedAt >= $startDate && $addedAt <= $endDate;
            });

            $trackUris = array_merge($trackUris, array_map(function ($item) {
                return $item->track->uri;
            }, $filteredTracks));

            $offset += $limit; // Increment the offset for the next request
        }

        if (empty($trackUris)) {
            return false;
        }
        $playlistTitle = 'Liked Songs of ' . $startDate->format('M Y') . '.';

        $playlist = $api->createPlaylist([
            'name' => $playlistTitle,
            'public' => (bool)$public,
            'description' =>
                'This playlist was generated with your liked songs from ' .
                $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d') . '.'
        ]);

        $playlistUrl = $playlist->external_urls->spotify;
        $playlistId = $playlist->id;

        $trackUris = array_chunk($trackUris, 100);

        foreach ($trackUris as $trackUri) {
            $api->addPlaylistTracks($playlist->id, $trackUri);
        }

        return [$playlistUrl, $playlistId];
    }

    public function getPlaylists($user_id, $amount): array | bool
    {
        $amount = $this->checkLimit($amount);
        $api = (new SessionHandler())->setSession($user_id);
        $playlists = [];
        $offset = 0;
        $me = $api->me();

        while (count($playlists) < $amount) {
            $fetchedPlaylists = $api->getUserPlaylists($me->id, [
                'limit' => 1,
                'offset' => $offset
            ]);

            $playlists = array_merge($playlists, $fetchedPlaylists->items);

            $offset += 1;
        }

        if (empty($playlists)) {
            return false;
        }

        return $playlists;
    }

    public function getSongSuggestions($user_id, $amount, $genre, $mood): array | bool
    {
        $api = (new SessionHandler())->setSession($user_id);

        // Get the first 50 top songs of the user
        $topSongs = $this->getTopSongs($user_id, 50);
        if (!$topSongs) {
            return false;
        }
        $topSongs = $topSongs->items;

        // Get the first 50 latest songs of the user
        $latestSongs = $this->getLatestSongs($user_id, 50);
        if (!$latestSongs) {
            return false;
        }
        $latestSongs = $latestSongs->items;

        // Extract the track IDs from the top songs
        $topTrackIds = array_map(function ($track) {
            return $track->id;
        }, $topSongs);

        // Extract the track IDs from the latest songs
        $latestTrackIds = array_map(function ($track) {
            return $track->id ?? null;
        }, $latestSongs);

        // Merge the top track IDs and the latest track IDs
        $trackIds = array_merge($topTrackIds, $latestTrackIds);

        // Remove empty track IDs
        $trackIds = array_filter($trackIds);

        $audioFeatures = $this->getAudioFeatures($user_id, $trackIds);
        if (!$audioFeatures) {
            return false;
        }

        $highestValues = $this->getHighestValues($audioFeatures);
        $lowestValues = $this->getLowestValues($audioFeatures);
//        $moodValues = $this->moodBasedValues($audioFeatures, $mood);


        // Shuffle the trackIds array
        shuffle($trackIds);

        // Get a random selection of seed tracks
        if ($genre) {
            $seedTracks = array_slice($trackIds, 0, 4);
            $recommendations = $api->getRecommendations([
                'seed_tracks' => $seedTracks,
                'seed_genres' => $genre,
                'limit' => $amount,
            ]);
        }
        else{
            $seedTracks = array_slice($trackIds, 0, 5);
            $recommendations = $api->getRecommendations([
                'seed_tracks' => $seedTracks,
                'limit' => $amount,
                'min_acousticness' => $lowestValues['acousticness'],
                'max_acousticness' => $highestValues['acousticness'],
                'min_danceability' => $lowestValues['danceability'],
                'max_danceability' => $highestValues['danceability'],
                'min_energy' => $lowestValues['energy'],
                'max_energy' => $highestValues['energy'],
                'min_instrumentalness' => $lowestValues['instrumentalness'],
                'max_instrumentalness' => $highestValues['instrumentalness'],
                'min_liveness' => $lowestValues['liveness'],
                'max_liveness' => $highestValues['liveness'],
                'min_loudness' => $lowestValues['loudness'],
                'max_loudness' => $highestValues['loudness'],
                'min_speechiness' => $lowestValues['speechiness'],
                'max_speechiness' => $highestValues['speechiness'],
                'min_tempo' => $lowestValues['tempo'],
                'max_tempo' => $highestValues['tempo'],
                'min_valence' => $lowestValues['valence'],
                'max_valence' => $highestValues['valence'],

            ]);
        }


        if (empty($recommendations->tracks)) {
            return false;
        }

        return $recommendations->tracks;
    }

    public function createPlaylist($user_id, bool|array $songSuggestions)
    {
        $api = (new SessionHandler())->setSession($user_id);

            $playlistTitle = 'Song Suggestions';

            $playlist = $api->createPlaylist([
                'name' => $playlistTitle,
                'public' => false,
                'description' =>
                    'This playlist was generated based on the songs from your top songs and latest liked songs.'
                ]);

            //loop through the song suggestions and add them to the playlist
            foreach ($songSuggestions as $song) {
                $api->addPlaylistTracks($playlist->id, $song->uri);
            }

            return $playlist->external_urls->spotify;
    }

    private function getAudioFeatures($user_id, $trackIds): array | bool
    {
        $api = (new SessionHandler())->setSession($user_id);
        $audioFeatures = [];

        while (count($audioFeatures) < count($trackIds)) {
            foreach (array_chunk($trackIds, 100) as $trackIdsChunk) {
                $fetchedAudioFeatures = $api->getMultipleAudioFeatures($trackIdsChunk);
                $audioFeatures = array_merge($audioFeatures, $fetchedAudioFeatures->audio_features);
            }
        }

        if (empty($audioFeatures)) {
            return false;
        }

        return $audioFeatures;
    }

    private function getHighestValues($audioFeatures): array
    {
        $highestDanceability = max(array_column($audioFeatures, 'danceability'));
        $highestEnergy = max(array_column($audioFeatures, 'energy'));
        $highestSpeechiness = max(array_column($audioFeatures, 'speechiness'));
        $highestAcousticness = max(array_column($audioFeatures, 'acousticness'));
        $highestInstrumentalness = max(array_column($audioFeatures, 'instrumentalness'));
        $highestLiveness = max(array_column($audioFeatures, 'liveness'));
        $highestValence = max(array_column($audioFeatures, 'valence'));
        $highestTempo = max(array_column($audioFeatures, 'tempo'));
        $highestLoudness = max(array_column($audioFeatures, 'loudness'));
        $highestDuration = max(array_column($audioFeatures, 'duration_ms'));

        $highestValues = [
            'danceability' => $highestDanceability,
            'energy' => $highestEnergy,
            'speechiness' => $highestSpeechiness,
            'acousticness' => $highestAcousticness,
            'instrumentalness' => $highestInstrumentalness,
            'liveness' => $highestLiveness,
            'valence' => $highestValence,
            'tempo' => $highestTempo,
            'loudness' => $highestLoudness,
            'duration_ms' => $highestDuration,
        ];
        return $highestValues;
    }

    private function getLowestValues($audioFeatures): array
    {
        $lowestDanceability = min(array_column($audioFeatures, 'danceability'));
        $lowestEnergy = min(array_column($audioFeatures, 'energy'));
        $lowestSpeechiness = min(array_column($audioFeatures, 'speechiness'));
        $lowestAcousticness = min(array_column($audioFeatures, 'acousticness'));
        $lowestInstrumentalness = min(array_column($audioFeatures, 'instrumentalness'));
        $lowestLiveness = min(array_column($audioFeatures, 'liveness'));
        $lowestValence = min(array_column($audioFeatures, 'valence'));
        $lowestTempo = min(array_column($audioFeatures, 'tempo'));
        $lowestLoudness = min(array_column($audioFeatures, 'loudness'));
        $lowestDuration = min(array_column($audioFeatures, 'duration_ms'));

        $lowestValues = [
            'danceability' => $lowestDanceability,
            'energy' => $lowestEnergy,
            'speechiness' => $lowestSpeechiness,
            'acousticness' => $lowestAcousticness,
            'instrumentalness' => $lowestInstrumentalness,
            'liveness' => $lowestLiveness,
            'valence' => $lowestValence,
            'tempo' => $lowestTempo,
            'loudness' => $lowestLoudness,
            'duration_ms' => $lowestDuration,
        ];
        return $lowestValues;
    }

//    //make a function that, based on the user's mood selection (happy, sad, angry, etc), will return a list of songs that match that mood
//    private function moodBasedValues($audioFeatures, $mood): array
//    {
//        $values = [];
//        if ($mood = "happy") {
//            $values = [
//                'acousticness' => ['min' => max(0.2, $audioFeatures['acousticness']['min']), 'max' => min(0.8, $audioFeatures['acousticness']['max'])],
//                'danceability' => ['min' => max(0.6, $audioFeatures['danceability']['min']), 'max' => min(1.0, $audioFeatures['danceability']['max'])],
//                'energy' => ['min' => max(0.7, $audioFeatures['energy']['min']), 'max' => min(1.0, $audioFeatures['energy']['max'])],
//                'instrumentalness' => ['min' => max(0, $audioFeatures['instrumentalness']['min']), 'max' => min(0.4, $audioFeatures['instrumentalness']['max'])],
//                'liveness' => ['min' => max(0.2, $audioFeatures['liveness']['min']), 'max' => min(1.0, $audioFeatures['liveness']['max'])],
//                'loudness' => ['min' => max(-10, $audioFeatures['loudness']['min']), 'max' => min(0, $audioFeatures['loudness']['max'])],
//                'speechiness' => ['min' => max(0, $audioFeatures['speechiness']['min']), 'max' => min(0.3, $audioFeatures['speechiness']['max'])],
//                'tempo' => ['min' => max(100, $audioFeatures['tempo']['min']), 'max' => min(130, $audioFeatures['tempo']['max'])],
//                'valence' => ['min' => max(0.6, $audioFeatures['valence']['min']), 'max' => min(1.0, $audioFeatures['valence']['max'])],
//            ];
//        } elseif ($mood = "sad") {
//            $values = [
//                'acousticness' => ['min' => max(0.5, $audioFeatures['acousticness']['min']), 'max' => min(1.0, $audioFeatures['acousticness']['max'])],
//                'danceability' => ['min' => max(0, $audioFeatures['danceability']['min']), 'max' => min(0.4, $audioFeatures['danceability']['max'])],
//                'energy' => ['min' => max(0, $audioFeatures['energy']['min']), 'max' => min(0.4, $audioFeatures['energy']['max'])],
//                'instrumentalness' => ['min' => max(0, $audioFeatures['instrumentalness']['min']), 'max' => min(0.4, $audioFeatures['instrumentalness']['max'])],
//                'liveness' => ['min' => max(0, $audioFeatures['liveness']['min']), 'max' => min(0.4, $audioFeatures['liveness']['max'])],
//                'loudness' => ['min' => max(-15, $audioFeatures['loudness']['min']), 'max' => min(-5, $audioFeatures['loudness']['max'])],
//                'speechiness' => ['min' => max(0, $audioFeatures['speechiness']['min']), 'max' => min(0.3, $audioFeatures['speechiness']['max'])],
//                'tempo' => ['min' => max(50, $audioFeatures['tempo']['min']), 'max' => min(90, $audioFeatures['tempo']['max'])],
//                'valence' => ['min' => max(0, $audioFeatures['valence']['min']), 'max' => min(0.5, $audioFeatures['valence']['max'])],
//            ];
//        } elseif ($mood = "angry") {
//        } elseif ($mood = "chill") {
//        } elseif ($mood = "romantic") {
//        } elseif ($mood = "party") {
//        } elseif ($mood = "workout") {
//        } elseif ($mood = "focus") {
//        } elseif ($mood = "sleep") {
//        } elseif ($mood = "study") {
//        }
//        return $values;
//    }
}
