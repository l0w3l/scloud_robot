<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Support\Facades\App;
use Lowel\LaravelServiceMaker\Services\ServiceFactoryInterface;

class TelegramServiceFactory implements ServiceFactoryInterface
{
    public function get(array $params = []): TelegramServiceInterface
    {
        return App::make(TelegramService::class, $params);
    }
}
