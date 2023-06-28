<?php

namespace App\Jobs;

use App\Discord\src\Builders\EmbedBuilder;
use App\Discord\src\Models\Spotify;
use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Intents;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PlaylistGenerator implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $amount;
    public string $user_id;
    private $startDate;
    private $endDate;
    private bool $public;
    private string $function;
    private Interaction $interaction;
    protected string $channel_id;
    protected string $avatarUrl;



    public function __construct($user_id, $interaction, $startDate, $endDate, $public, $function)
    {
        $this->user_id = $user_id;
        $this->interaction = $interaction;
        $this->channel_id = $this->interaction->channel_id;
        $this->public = $public;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->function = $function;
        $this->avatarUrl = $this->interaction->member->user->avatar;

    }

    /**
     * @throws IntentException
     * @throws \Exception
     */
    public function handle(): void
    {
        $this->discord = new Discord([
            'token' => $_ENV['DISCORD_BOT_TOKEN'],
            'intents' => Intents::getDefaultIntents(),
        ]);
        $spotify = new Spotify();
        $user_id = $this->user_id;
        $public = $this->public;
        $startDate = $this->startDate;
        $endDate = $this->endDate;

        $playlistTitle = 'Liked Songs of ' . $startDate->format('M Y') . '.';

        $playlist = $spotify->generatePlaylist($user_id, $startDate, $endDate, $public);
        $url = $playlist[0];
        $channel_id = $this->channel_id;
        $me = Spotify::connect($user_id);
        $this->discord->on('ready', function (Discord $discord) use ($user_id, $playlistTitle, $url, $channel_id, $me) {
            $builder = new EmbedBuilder($discord);
            $builder->setTitle($playlistTitle . ' - ' . $me->display_name);
            $builder->setDescription('Your playlist has been generated!');
            $builder->setThumbnail($me->images[0]->url);
            $builder->setURL($url);
            $builder->setSuccess();
            $builder->setFooterWithAvatar($me->display_name, $me->images[0]->url);

            $embed = $builder->build();
            $channel = $discord->getChannel($channel_id);
            $channel?->sendMessage('<@' . $this->user_id . '>', false, $embed)->done(function() {
                $this->discord->close();
            });
        });
        $this->discord->run();
    }
}
