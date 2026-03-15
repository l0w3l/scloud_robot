<?php

declare(strict_types=1);

namespace App\Telegram\Handlers;

use Lowel\Telepath\Core\Router\Handler\AbstractTelegramHandler;
use Lowel\Telepath\Facades\SpiritBox;

class StartCommandHandler extends AbstractTelegramHandler
{
    public function handler(): callable
    {
        return static function () {
            SpiritBox::sendMessage(__('telegram.commands.start'));
        };
    }
}
