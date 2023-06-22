<?php

namespace App\Discord\src\Builders;

use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Discord;

class EmbedBuilder extends MessageBuilder
{
    public $embed;
    public Discord $discord;

    public function __construct(Discord $discord)
    {
        $this->embed = new Embed($discord);
        $this->discord = $discord;
        $this->setTimestamp();
    }

    public function setTitle(string $title): self
    {
        $this->embed->title = $title;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->embed->description = $description;
        return $this;
    }

    public function addField(string $name, string $value, bool $inline): self
    {
        $this->embed->addFieldValues($name, $value, $inline);
        return $this;
    }

    public function setFooter($interaction = null): self
    {
        $this->embed->setFooter($interaction->member->username, $interaction->member->user->avatar);
        return $this;
    }

    public function setThumbnail(string $url): self
    {
        $this->embed->setThumbnail($url);
        return $this;
    }

    public function setTimestamp(): self
    {
        $time = time();
        $this->embed->setTimestamp($time);
        return $this;
    }

    public function setUrl(string $url): self
    {
        $this->embed->setUrl($url);
        return $this;
    }

    public function setSuccess(): self
    {
        $this->embed->setColor('00ff00');
        return $this;
    }

    public function setWarning(): self
    {
        $this->embed->setColor('ffff00');
        return $this;
    }

    public function setError(): self
    {
        $this->embed->setColor('ff0000');
        return $this;
    }

    public function setInfo(): self
    {
        $this->embed->setColor('0000ff');
        return $this;
    }

    public function addLineBreak(): self
    {
        $this->embed->addFieldValues('------------------', '');
        return $this;
    }

    public function addFirstPage($fields, $amount = 12): void
    {
        $fields = array_slice($fields, 0, $amount);
        foreach ($fields as $field) {
            $this->embed->addFieldValues($field['name'], $field['value'], $field['inline']);
        }
    }

    public function build(): Embed
    {
        return $this->embed;
    }
}
