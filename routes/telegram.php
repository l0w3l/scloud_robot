<?php

declare(strict_types=1);

use App\Telegram\Handlers\DownloadSoundcloudTrackHandler;
use App\Telegram\Handlers\Inline\SearchTracksHandler;
use Lowel\Telepath\Facades\Telepath;

Telepath::onMessage(DownloadSoundcloudTrackHandler::class);

Telepath::onInlineQuery(SearchTracksHandler::class);
