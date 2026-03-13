<?php

declare(strict_types=1);

namespace App\Telegram\Keyboards\Inline\Soundcloud\Track\Buttons;

use Lowel\Telepath\Core\Router\Keyboard\Buttons\Inline\AbstractUrlButton;

class PageUrlInlineButton extends AbstractUrlButton
{
    /**
     * @param  array{song_url?: string}  $args
     */
    public function url(array $args = []): int|string|callable
    {
        return $args['song_url'];
    }

    public function text(array $args = []): int|string|callable
    {
        return '🎧';
    }
}
