<?php

declare(strict_types=1);

namespace App\Telegram\Handlers;

use App\Services\Telegram\TelegramServiceInterface;
use Lowel\Telepath\Core\Router\Handler\AbstractTelegramHandler;

class DownloadSoundcloudTrackHandler extends AbstractTelegramHandler
{
    public function handler(): callable
    {
        return static function (TelegramServiceInterface $telegramService): void {
            $telegramService->resolveSoundcloudLinkInMessage();
        };
    }
}
