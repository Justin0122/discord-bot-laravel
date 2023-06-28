<?php

namespace App\Jobs;

use App\Discord\src\Builders\EmbedBuilder;
use App\Discord\src\Models\Spotify as SpotifyModel;
use Discord\Exceptions\IntentException;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Intents;
use Illuminate\Bus\Queueable;
use Discord\Discord;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SpotifyUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $user_id;
    protected string $channel_id;
    private Interaction $interaction;
    protected string $avatarUrl;

    /**
     * Create a new job instance.
     */
    public function __construct($user_id, $interaction)
    {
        $this->user_id = $user_id;
        $this->channel_id = $interaction->channel_id;
        $this->avatarUrl = $interaction->member->user->avatar;
    }

    /**
     * @throws IntentException
     */
    public function handle(): void
    {
        $this->discord = new Discord([
            'token' => $_ENV['DISCORD_BOT_TOKEN'],
            'intents' => Intents::getDefaultIntents(),
        ]);

        $me = SpotifyModel::connect($this->user_id);
        $channel_id = $this->channel_id;

            $this->discord->on('ready', function (Discord $discord) use ($channel_id, $me) {

                $builder = new EmbedBuilder($discord);
                $builder->setTitle($me->display_name);
                $builder->setUrl($me->external_urls->spotify);
                $builder->setDescription('');
                $builder->setSuccess();
                $builder->setFooterWithAvatar($me->display_name, $me->images[0]->url);
                $builder->addField('Followers', $me->followers->total, true);
                $builder->addField('Country', $me->country, true);
                $builder->addField('Product', $me->product, true);

                $spotify = new SpotifyModel();
                $topSongs = $spotify->getTopSongs($this->user_id, 3);

                if ($topSongs !== null && count($topSongs->items) > 0) {
                    $topSongsField = "";
                    foreach ($topSongs->items as $song) {
                        $songName = $song->name;
                        $artistName = $song->artists[0]->name;
                        $songLink = $song->external_urls->spotify;
                        $artist = $song->artists[0]->external_urls->spotify;
                        $topSongsField .= "> [{$songName}]({$songLink}) - [{$artistName}]({$artist})\n";
                    }
                    $builder->addField('Top Songs', $topSongsField, true);
                } else {
                    $builder->addField('Top Songs', 'No songs found', true);
                }

                if (isset($me->images[0]->url)) {
                    $builder->setThumbnail($me->images[0]->url);
                }

                $currentSong = $spotify->getCurrentSong($this->user_id) ?? null;
                if ($currentSong) {
                    $builder->addField('Currently listening to', "> " . "[{$currentSong->item->name}]({$currentSong->item->external_urls->spotify}) - [{$currentSong->item->artists[0]->name}]({$currentSong->item->artists[0]->external_urls->spotify})", false);
                }

                $embed = $builder->build();
                $channel = $discord->getChannel($channel_id);
                $channel?->sendMessage('<@' . $this->user_id . '>', false, $embed)->done(function() {
                    $this->discord->close();
                });
            });
        $this->discord->run();
    }
}
