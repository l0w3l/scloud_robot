<?php

declare(strict_types=1);

namespace App\Telegram\Keyboards\Inline\Soundcloud\Track\Buttons;

use Lowel\Telepath\Core\Router\Keyboard\Buttons\Inline\AbstractUrlButton;

class CoverUrlInlineButton extends AbstractUrlButton
{
    /**
     * @param  array{cover_url?: string}  $args
     */
    public function url(array $args = []): int|string|callable
    {
        return $args['cover_url'] ?? 'https://soundcloud.com';
    }

    public function text(array $args = []): int|string|callable
    {
        return '🖼';
    }
}
