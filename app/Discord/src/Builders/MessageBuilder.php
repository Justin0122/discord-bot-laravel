<?php

namespace App\Discord\src\Builders;

class MessageBuilder
{
    public static function buildMessage($builder, $button = []): \Discord\Builders\MessageBuilder
    {
        $messageBuilder = new \Discord\Builders\MessageBuilder();
        $messageBuilder->addEmbed($builder->build());

        foreach ($button as $row) {
            $messageBuilder->addComponent($row);
        }
        return $messageBuilder;
    }

}
