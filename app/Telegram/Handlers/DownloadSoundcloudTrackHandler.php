<?php

declare(strict_types=1);

namespace App\Telegram\Handlers;

use App\Jobs\Telegram\Soundcloud\UploadTrackJob;
use App\Models\SoundcloudTrack;
use App\Services\Telegram\TelegramServiceInterface;
use Lowel\Telepath\Core\Router\Handler\AbstractTelegramHandler;
use Lowel\Telepath\Facades\Extrasense;

class DownloadSoundcloudTrackHandler extends AbstractTelegramHandler
{
    public function handler(): callable
    {
        return static function (TelegramServiceInterface $telegramService): void {
            $message = Extrasense::message();

            $existedTrack = $telegramService->resolveSoundcloudLink($message->text ?? '');

            if ($existedTrack instanceof SoundcloudTrack) {
                $existedTrack->send();
            } elseif (is_string($trackUrl = $existedTrack)) {
                UploadTrackJob::dispatch(Extrasense::update(), $trackUrl);
            }
        };
    }
}
