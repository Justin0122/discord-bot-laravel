<?php

namespace App\Discord\src\Commands;

use Discord\Parts\Interactions\Interaction;
use Discord\Builders\MessageBuilder;
use App\Discord\src\Events\Success;
use Discord\Discord;
use DOMDocument;
use DOMXPath;


class Search
{
    public function getDescription(): string
    {
        return 'Search google for a query';
    }

    public function getOptions(): array
    {
        return [
            [
                'name' => 'query',
                'description' => 'The query to search for',
                'type' => 3,
                'required' => true
            ],
            [
                'name' => 'safe',
                'description' => 'Whether to enable safe search',
                'type' => 3,
                'required' => false,
                'choices' => [
                    [
                        'name' => 'on',
                        'value' => 'on'
                    ],
                    [
                        'name' => 'off',
                        'value' => 'off'
                    ]
                ]

            ],
            [
                'name' => 'ephemeral',
                'description' => 'Send the message only to you',
                'type' => 5,
                'required' => false
            ]
        ];
    }

    public function getGuildId(): ?string
    {
        return null;
    }
    public function getCooldown(): ?int
    {
        return 5;
    }

    public function handle(Interaction $interaction, Discord $discord): void
    {
        $optionRepository = $interaction->data->options;
        $ephemeral = $optionRepository['ephemeral']->value ?? false;
        $query = $optionRepository['query']->value;
        $safe = $optionRepository['safe']->value ?? 'on';

        $link = "https://www.google.com/search?q=" . urlencode($query) . "&safe=on&lr=lang_en&hl=en&safe=" . $safe;
        $html = file_get_contents($link);
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $hrefs = $xpath->evaluate("/html/body//a");
        $links = [];
        for ($i = 0; $i < $hrefs->length; $i++) {
            $href = $hrefs->item($i);
            $url = $href->getAttribute('href');
            if (str_contains($url, "/url?q=")) {
                $url = substr($url, 7);
                $url = explode('&', $url, 2);
                $url = $url[0];
                $links[] = $url;
            }
        }
        $messageBuilder = MessageBuilder::new()->setContent($links[0]);
        $interaction->respondWithMessage($messageBuilder, $ephemeral);
    }


}
