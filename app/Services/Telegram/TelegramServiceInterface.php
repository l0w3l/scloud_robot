<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Lowel\LaravelServiceMaker\Services\ServiceInterface;

interface TelegramServiceInterface extends ServiceInterface
{
    public function resolveSoundcloudLinkInMessage(): void;

    public function resolveSoundcloudLinkInInlineQuery(): void;
}
