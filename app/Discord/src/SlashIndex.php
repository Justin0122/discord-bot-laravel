<?php

namespace App\Discord\src;

use App\Discord\src\Events\Error;
use Discord\Parts\Interactions\Interaction;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Discord;

class SlashIndex
{
    public int $perPage = 12;
    public int $offset = 0;
    public int $total = 0;
    public int $startingPage = 2;
    public array $fields = [];

    public function __construct(array $fields)
    {
        $this->total = count($fields);
        $this->fields = $fields;
    }

    public function setTotalPerPage(int $total): void
    {
        $this->perPage = $total;
        $this->offset = -$total;
    }

    public function paginationButton(Discord $discord, bool $isNextButton, $title, $description): Button
    {
        $label = $isNextButton ? 'Next' : 'Previous';

        return Button::new(Button::STYLE_PRIMARY)
            ->setLabel($label)
            ->setListener(function (Interaction $interaction) use ($discord, $isNextButton, $title, $description) {
                if ($interaction->member->id !== $interaction->message->interaction->user->id) {
                    Error::sendError($interaction, $discord, 'You cannot use this button');
                }

                if ($isNextButton) {
                    $this->incOffset($this->perPage);
                } else {
                    $this->incOffset(-$this->perPage);
                }

                $next = $this->paginationButton($discord, true, $title, $description);
                $previous = $this->paginationButton($discord, false, $title, $description);

                if (($this->getOffset() + $this->perPage) >= $this->getTotal()) {
                    $next->setDisabled(true);
                }

                if ($this->getOffset() <= 0) {
                    $previous->setDisabled(true);
                }

                $actionRow = ActionRow::new()->addComponent($previous)->addComponent($next);
                $interaction->message->edit(MessageBuilder::new()->addEmbed($this->getEmbed($discord, $title, $description))->addComponent($actionRow));
            }, $discord, $title, $description);
    }

    public function incOffset(int $amount): void
    {
        $this->offset += $amount;
    }

    public function getEmbed(Discord $discord, $title = null, $description = null): Embed
    {
        $embed = new Embed($discord);
        $embed->setColor('00ff00');
        $embed->setTitle($title ?? '');
        $embed->setDescription($description ?? '');

        $fields = array_slice($this->fields, $this->getOffset(), $this->perPage);
        foreach ($fields as $field) {
            $embed->addFieldValues($field['name'], $field['value'], $field['inline']);
        }
        $page = $this->getOffset() / $this->perPage;
        $embed->setFooter('Page ' . ($page + 1) . ' of ' . ceil($this->getTotal() / $this->perPage));

        return $embed;
    }

    public function handlePagination(int $totalFields, $builder, Discord $discord, Interaction $interaction, $embed, $title = '', $description = '', $isEdit = false, $AddButtons = false): void
    {
        if ($totalFields > 4 || $AddButtons) {
            $button1 = $this->paginationButton($discord, true, $title, $description);
            $button2 = $this->paginationButton($discord, false, $title, $description);
            if (($this->getOffset() + 1) === $this->getTotal()) {
                $button1->setDisabled(true);
            }

            if ($this->getOffset() === 0) {
                $button2->setDisabled(true);
            }

            $fields = array_slice($this->fields, $this->getOffset(), $this->perPage);
            foreach ($fields as $field) {
                $embed->addField($field['name'], $field['value'], $field['inline']);
            }


            $row = ActionRow::new()
                ->addComponent($button2)
                ->addComponent($button1);

            $builder->addComponent($row);

            if ($isEdit) {
                $interaction->updateOriginalResponse($builder);
            } else {
                $interaction->respondWithMessage($builder);
            }
        }
        else{
            $interaction->respondWithMessage($builder);
        }

        $this->total = $totalFields;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

}
