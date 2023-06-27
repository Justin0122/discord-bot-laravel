<?php

namespace App\Jobs;

use App\Discord\src\Models\Spotify;
use App\Discord\src\Models\Spotify as SpotifyModel;
use Discord\Exceptions\IntentException;
use Discord\Parts\Interactions\Interaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class PlaylistGenerator extends DiscordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $amount;
    public string $genre;
    public string $mood;
    public string $user_id;
    public string $link;
    protected Interaction $interaction;
    protected bool $ephemeral;

    /**
     * Create a new job instance.
     *
     * @return void
     * @throws IntentException
     */
    public function __construct($user_id, $interaction, $ephemeral = false, $amount = 10, $genre = '', $mood = '')
    {
        parent::__construct();
        $this->user_id = $user_id;
        $this->interaction = $interaction;
        $this->ephemeral = $ephemeral;
        $this->amount = $amount;
        $this->genre = $genre;
        $this->mood = $mood;

        $this->handle();
    }

    /**
     * Execute the job.
     */
    public function handle(): array|object|null|string
    {
        $lock = cache()->lock('playlist_generation_lock', 10);

        try {
            if ($lock->get()) {
                $me = new SpotifyModel();
                $me = $me->getMe($this->user_id);
                if (!$me) {
                    echo 'No Spotify account linked' . PHP_EOL;
                    return null;
                }
                $spotify = new Spotify();

                $songSuggestions = $spotify->getSongSuggestions($this->user_id, $this->amount, $this->genre, $this->mood);
                $spotify = $spotify->createPlaylist($this->user_id, $songSuggestions);

                $this->storeLink($spotify);

                return $spotify;
            } else {
                // Queue the job to be processed later
                self::dispatch($this->amount, $this->genre, $this->mood, $this->user_id)->delay(now()->addSeconds(10));
            }
        } catch (LockTimeoutException $e) {
            // Another instance of the job is already being processed
            // Queue the job to be processed later
            self::dispatch($this->amount, $this->genre, $this->mood, $this->user_id)->delay(now()->addSeconds(10));
        } finally {
            $lock->release();
        }

        return null;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onSuccess(\Closure $param)
    {
        $link = $this->retrieveLink();
        cache()->forget('playlist_link_' . $this->user_id);
        return $param($link);
    }

    private function storeLink($link): void
    {
        // Store the link in a persistent storage mechanism like a database or cache
        // Example using Laravel's Cache:
        cache()->put('playlist_link_' . $this->user_id, $link, now()->addHour());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function retrieveLink()
    {
        return cache()->get('playlist_link_' . $this->user_id);
    }
}
