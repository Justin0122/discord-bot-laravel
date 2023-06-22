<?php

namespace App\Discord\src\Builders;

use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;

class ButtonBuilder
{
    public static function addLinkButton(ActionRow $actionRow, string $label, string $url): void
    {
        $button = Button::new(Button::STYLE_LINK)
            ->setLabel($label)
            ->setURL($url);

        $actionRow->addComponent($button);
    }

    public static function addPrimaryButton(string $label, string $custom_id): array
    {
        $button = Button::new(Button::STYLE_PRIMARY)
            ->setLabel($label)
            ->setCustomID($custom_id);

        $actionRow = ActionRow::new()->addComponent($button);

        return [$actionRow, $button];
    }

    public static function addSecondaryButton(string $label, string $custom_id): array
    {
        $button = Button::new(Button::STYLE_SECONDARY)
            ->setLabel($label)
            ->setCustomID($custom_id);

        $actionRow = ActionRow::new()->addComponent($button);

        return [$actionRow, $button];
    }


    public static function addSuccessButton(string $label, string $custom_id): array
    {
        $button = Button::new(Button::STYLE_SUCCESS)
            ->setLabel($label)
            ->setCustomID($custom_id);

        $actionRow = ActionRow::new()->addComponent($button);

        return [$actionRow, $button];
    }

    public static function addDangerButton(string $label, string $custom_id): array
    {
        $button = Button::new(Button::STYLE_DANGER)
            ->setLabel($label)
            ->setCustomID($custom_id);

        $actionRow = ActionRow::new()->addComponent($button);

        return [$actionRow, $button];
    }

    public static function getButton(Button $button)
    {
        return $button[0];
    }


}
