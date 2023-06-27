<?php

namespace App\Jobs;

use App\Discord\src\Builders\ButtonBuilder;
use App\Discord\src\Builders\MessageBuilder;
use App\Discord\src\Events\Error;
use App\Discord\src\Events\Success;
use App\Discord\src\Models\Spotify as SpotifyModel;
use Discord\Builders\Components\ActionRow;
use Discord\Exceptions\IntentException;
use Discord\Parts\Interactions\Interaction;
use Illuminate\Bus\Queueable;
use Discord\Discord;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SpotifyUser extends DiscordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $user_id;
    protected Interaction $interaction;
    protected bool $ephemeral;

    /**
     * Create a new job instance.
     * @throws IntentException
     */
    public function __construct($user_id, $interaction, $ephemeral = false)
    {
        parent::__construct();
        $this->user_id = $user_id;
        $this->interaction = $interaction;
        $this->ephemeral = $ephemeral;
    }

    /**
     */
    public function handle(): void
    {
        sleep(5);

        $interaction = $this->interaction;
        $me = SpotifyModel::connect($this->user_id);

        if (!$me) {
            Error::sendError($interaction, $this->discord, 'You are not connected to Spotify. Please use /spotify [Login] first', true);
            return;
        }

        $user_id = $this->user_id;

        $handler = function (Discord $discord) use ($interaction, $me, $user_id) {
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

            $currentSong = $spotify->getCurrentSong($user_id) ?? null;
            $actionRow = ActionRow::new();

            ButtonBuilder::addLinkButton($actionRow, 'Open profile', $me->external_urls->spotify);

            if ($currentSong) {
                $builder->addField('Currently listening to', "> " . $currentSong->item->name . ' - ' . $currentSong->item->artists[0]->name, false ?? 'No song playing');
                ButtonBuilder::addLinkButton($actionRow, 'Listen along', $currentSong->item->external_urls->spotify);
            }

            $messageBuilder = MessageBuilder::buildMessage($builder, [$actionRow]);

            $this->ephemeralHandler($interaction, $messageBuilder, $this->ephemeral);
        };

        $this->connectAndHandle($interaction, $handler);
    }

}
