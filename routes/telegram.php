<?php

declare(strict_types=1);

use App\Telegram\Handlers\DownloadSoundcloudTrackHandler;
use App\Telegram\Handlers\Inline\SearchTracksHandler;
use App\Telegram\Middlewares\AuthMiddleware;
use Lowel\Telepath\Facades\Telepath;

Telepath::middleware(AuthMiddleware::class)->group(function () {
    Telepath::onMessage(DownloadSoundcloudTrackHandler::class);

    Telepath::onInlineQuery(SearchTracksHandler::class);
});
