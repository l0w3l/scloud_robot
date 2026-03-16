<?php

declare(strict_types=1);

use App\Telegram\Handlers\DownloadSoundcloudTrackHandler;
use App\Telegram\Handlers\DownloadYoutubeHandler;
use App\Telegram\Handlers\Inline\ChosenSearchTrackResultHandler;
use App\Telegram\Handlers\Inline\ChosenTrackResultHandler;
use App\Telegram\Handlers\Inline\SearchTracksHandler;
use App\Telegram\Handlers\StartCommandHandler;
use App\Telegram\Keyboards\Inline\Youtube\ChoseFormat\ChoseFormatInlineKeyboardFactory;
use App\Telegram\Middlewares\AuthMiddleware;
use Lowel\Telepath\Facades\Telepath;

Telepath::middleware(AuthMiddleware::class)->group(function () {
    Telepath::onCommand('start', StartCommandHandler::class);

    Telepath::onMessage(DownloadSoundcloudTrackHandler::class);
    Telepath::onMessage(DownloadYoutubeHandler::class);

    Telepath::onInlineQuery(SearchTracksHandler::class);

    Telepath::onInlineQueryChosenResult(ChosenTrackResultHandler::class);
    Telepath::onInlineQueryChosenResult(ChosenSearchTrackResultHandler::class);

    Telepath::keyboard(ChoseFormatInlineKeyboardFactory::class);
});
