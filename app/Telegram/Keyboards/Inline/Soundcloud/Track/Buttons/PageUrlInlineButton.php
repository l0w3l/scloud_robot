<?php

declare(strict_types=1);

namespace App\Telegram\Keyboards\Inline\Soundcloud\Track\Buttons;

use App\Data\Soundcloud\TrackInfoData;
use App\Models\SoundcloudTrack;
use Lowel\Telepath\Core\Router\Keyboard\Buttons\Inline\AbstractUrlButton;

class PageUrlInlineButton extends AbstractUrlButton
{
    /**
     * @param  array{soundcloudTrack?: TrackInfoData|SoundcloudTrack}  $args
     */
    public function url(array $args = []): int|string|callable
    {
        return $args['soundcloudTrack']->page_url;
    }

    public function text(array $args = []): int|string|callable
    {
        return '🎧';
    }
}
