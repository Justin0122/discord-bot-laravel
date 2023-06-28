<?php

namespace App\Jobs;

use App\Discord\src\Builders\ButtonBuilder;
use App\Discord\src\Builders\EmbedBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use App\Discord\src\Events\Error;
use App\Discord\src\Models\Spotify as SpotifyModel;
use Discord\Builders\Components\ActionRow;
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
    protected bool $ephemeral;
    public Discord $discord;
    protected string $channel_id;
    public Interaction $interaction;

    /**
     * Create a new job instance.
     */
    public function __construct($user_id, $ephemeral = false, $interaction = null)
    {
        $this->user_id = $user_id;
        $this->interaction = $interaction;
        $this->channel_id = $this->interaction->channel_id;
        $this->ephemeral = $ephemeral;
        print_r($this->interaction);
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

                $channel = $discord->getChannel($channel_id);
                $builder = new EmbedBuilder($discord);
                $builder->setTitle($me->display_name);
                $builder->setDescription('');
                $builder->setSuccess();

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

                //find the interaction in the channel
                echo "Finding interaction: {$this->interaction->id}\n";
                echo "Channel: {$this->interaction->channel_id}\n";
                echo "Guild: {$this->interaction->guild_id}\n";
                echo "User: {$this->interaction->user->id}\n";

                $channel->messages->fetch($this->interaction->id)->done(function ($message) use ($discord, $embed) {
                    $message->delete();
                    $message->channel->sendMessage(MessageBuilder::new()->addEmbed($embed))->done(function ($message) use ($discord) {
                        $message->delete();
                        $discord->close();
                    });
                });
            });
        $this->discord->run();
    }
}
