<?php
use App\Discord\src\Models\Spotify;

include __DIR__ . '/vendor/autoload.php';
include 'Includes.php';


while (true) {
    $queue = json_decode(file_get_contents(__DIR__ . '/queue.json'), true);
    // Process user requests
    foreach ($queue as $userId => $userData) {

        echo 'Processing user ' . $userId . PHP_EOL;
        $amount = $userData['amount'];
        $genre = $userData['genre'];
        $mood = $userData['mood'];


        // Get song suggestions and create a playlist for the user
        getSongSuggestions($userId, $amount, $genre, $mood);
    }

    // Delay before the next iteration unless there are users in the queue
    if (count($queue) === 0) {
        echo 'No users in the queue, sleeping for 60 seconds' . PHP_EOL;
        sleep(60);
    }
    else{
        sleep(1);
    }
}

function getSongSuggestions($user_id, $amount, $genre, $mood): void
{
    $spotify = new Spotify();
    try {
        $songSuggestions = $spotify->getSongSuggestions($user_id, $amount, $genre, $mood);
        echo 'Creating playlist for user ' . $user_id . PHP_EOL;

        $spotify->createPlaylist($user_id, $songSuggestions);
        echo 'Playlist created for user ' . $user_id . PHP_EOL;

        //remove the user from the queue
        $queue = json_decode(file_get_contents(__DIR__ . '/queue.json'), true);
        unset($queue[$user_id]);
        file_put_contents(__DIR__ . '/queue.json', json_encode($queue, JSON_PRETTY_PRINT));

    } catch (Exception $e) {
        echo "Something went wrong: " . $e->getMessage() . "\n" . "Retrying in 10 seconds..." . PHP_EOL;
        sleep(10);

    }
}
