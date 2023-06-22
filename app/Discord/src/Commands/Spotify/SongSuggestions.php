<?php

namespace App\Discord\src\Commands\Spotify;

use App\Discord\src\Builders\ButtonBuilder;
use Discord\Builders\Components\ActionRow;
use Discord\Parts\Interactions\Interaction;
use App\Discord\src\Builders\MessageBuilder;
use App\Discord\src\Builders\EmbedBuilder;
use App\Discord\src\Builders\InitialEmbed;
use App\Discord\src\Events\Success;
use App\Discord\src\Events\Error;
use Discord\Discord;
use App\Jobs\PlaylistGenerator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SongSuggestions
{
    public function getDescription(): string
    {
        return 'Get song suggestions based on your top songs';
    }

    public function getOptions(): array
    {
        return [
            [
                'name' => 'amount',
                'description' => 'amount of songs (default 100)',
                'type' => 4,
                'required' => false
            ],
            [
                'name' => 'genre',
                'description' => 'Filter the suggestions by genre (default none) (This does very little)',
                'type' => 3,
                'required' => false
            ],
            [
                'name' => 'ephemeral',
                'description' => 'Send the message only to you',
                'type' => 5,
                'required' => false
            ],
            [
                'name' => 'mood',
                'description' => 'Select an option',
                'type' => 3,
                'required' => false,
                'choices' => [
                    [
                        'name' => 'Happy',
                        'value' => 'happy'
                    ],
                    [
                        'name' => 'Sad',
                        'value' => 'sad'
                    ],
                    [
                        'name' => 'Dance',
                        'value' => 'dance'
                    ]
                ]
            ],
            [
                'name' => 'queue',
                'description' => 'Get the current queue position or remove yourself from the queue',
                'type' => 3,
                'required' => false,
                'choices' => [
                    [
                        'name' => 'Get your position',
                        'value' => 'get'
                    ],
                    [
                        'name' => 'Remove me from queue',
                        'value' => 'remove'
                    ]
                ]
            ]
        ];
    }

    public function getGuildId(): ?string
    {
        return null;
    }

    public function getCooldown(): ?int
    {
        return 120;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(Interaction $interaction, Discord $discord, $user_id): void
    {
        InitialEmbed::send($interaction, $discord, 'Please wait while we are fetching your song suggestions.', true);

        $optionRepository = $interaction->data->options;
        $amount = $optionRepository['amount']->value ?? 100;
        $genre = $optionRepository['genre']->value ?? false;
        $ephemeral = $optionRepository['ephemeral']->value ?? false;
        $mood = $optionRepository['mood']->value ?? false;


        $pid = pcntl_fork();
        if ($pid == -1) {
            die('could not fork');
        } else if ($pid) {
            // Parent
        } else {
            // Child
            $job = new PlaylistGenerator($amount, $genre, $mood, $user_id);
            dispatch($job);

            //when the job is finished, send a message to the user
            $job->onQueue('default')->onConnection('database')->onSuccess(function ($job) use ($ephemeral, $interaction, $discord) {
                $this->sendFinishedMessage($interaction, $discord, $job, $ephemeral);
            });

            exit(0);
        }
    }

    private function sendFinishedMessage(Interaction $interaction, Discord $discord, $job, $ephemeral): void
    {
        if ($job == null){
            Error::sendError($interaction, $discord, 'Something went wrong while creating your playlist', true, true);
            return;
        }
        $builder = Success::sendSuccess($discord, 'Playlist created', 'Your playlist has been created', $interaction);
        $actionRow = ActionRow::new();
        ButtonBuilder::addLinkButton($actionRow, 'Open playlist', $job);
        $messageBuilder = MessageBuilder::buildMessage($builder, [$actionRow]);
        $interaction->sendFollowUpMessage($messageBuilder, $ephemeral);
    }

}
