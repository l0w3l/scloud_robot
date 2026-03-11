<?php

declare(strict_types=1);

namespace App\Telegram\Keyboards\Inline\Soundcloud\Track;

use App\Telegram\Keyboards\Inline\Soundcloud\Track\Buttons\CoverUrlInlineButton;
use App\Telegram\Keyboards\Inline\Soundcloud\Track\Buttons\PageUrlInlineButton;
use Lowel\Telepath\Core\Router\Keyboard\InlineKeyboardBuilder;
use Lowel\Telepath\Core\Router\Keyboard\KeyboardBuilderInterface;
use Lowel\Telepath\Core\Router\Keyboard\KeyboardFactoryInterface;

class TrackInlineKeyboardFactory implements KeyboardFactoryInterface
{
	public function make(): KeyboardBuilderInterface
	{
		$builder = new InlineKeyboardBuilder;

		return $builder->row(new CoverUrlInlineButton, new PageUrlInlineButton);
	}
}
