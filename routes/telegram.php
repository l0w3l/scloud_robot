<?php

declare(strict_types=1);

use App\Telegram\Handlers\DownloadSoundcloudTrackHandler;
use Lowel\Telepath\Facades\Telepath;

Telepath::onMessage(DownloadSoundcloudTrackHandler::class);
