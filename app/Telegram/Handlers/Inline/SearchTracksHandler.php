<?php

declare(strict_types=1);

namespace App\Telegram\Handlers\Inline;

use App\Services\Telegram\TelegramServiceInterface;
use Lowel\Telepath\Core\Router\Handler\AbstractTelegramHandler;

class SearchTracksHandler extends AbstractTelegramHandler
{
    public function handler(): callable
    {
        return static function (TelegramServiceInterface $telegramService) {
            $telegramService->resolveSoundcloudLinkInInlineQuery();
        };
    }
}
