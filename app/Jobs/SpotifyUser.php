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
    public Interaction $interaction;
    protected bool $ephemeral;
    public Discord $discord;

    /**
     * Create a new job instance.
     * @throws IntentException
     */
    public function __construct($user_id, $interaction, $ephemeral = false)
    {
        $this->user_id = $user_id;
        $this->interaction = $interaction;
        $this->ephemeral = $ephemeral;
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

        $this->discord->on('ready', function (Discord $discord) use ($me) {
            echo "Bot is ready!", PHP_EOL;

            $builder = Success::sendSuccess($discord, 'You are logged in as ');
            $messageBuilder = MessageBuilder::buildMessage($builder);
            $interaction = $this->interaction;
            $interaction->updateOriginalResponse($messageBuilder)->done(function () use ($interaction) {
                $interaction->deleteOriginalResponse();
                $this->discord->close();
            });

        });
        $this->discord->run();
    }
}
