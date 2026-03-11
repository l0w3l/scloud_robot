<?php

declare(strict_types=1);

namespace App\Telegram\Keyboards\Inline\Soundcloud\Track\Buttons;

use App\Data\Soundcloud\ThumbnailData;
use App\Models\SoundcloudThumbnail;
use Lowel\Telepath\Core\Router\Keyboard\Buttons\Inline\AbstractUrlButton;

class CoverUrlInlineButton extends AbstractUrlButton
{
	/**
	 * @param  array{cover?: ThumbnailData|SoundcloudThumbnail}  $args
	 */
	public function url(array $args = []): int|string|callable
	{
		return $args['cover']->url;
	}

	public function text(array $args = []): int|string|callable
	{
		return '🖼';
	}
}
