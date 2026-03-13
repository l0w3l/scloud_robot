<?php

declare(strict_types=1);

namespace App\Telegram\Handlers;

use App\Data\Soundcloud\EventData;
use App\Exceptions\TooLargeFileForDownloadException;
use App\Models\SoundcloudTrack;
use App\Services\Redis\RedisServiceInterface;
use App\Services\Telegram\TelegramServiceInterface;
use Illuminate\Support\Facades\DB;
use Lowel\Telepath\Core\Router\Handler\AbstractTelegramHandler;
use Lowel\Telepath\Facades\Extrasense;
use Lowel\Telepath\Facades\SpiritBox;

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
                $message = SpiritBox::replyMessage(__('telegram.soundcloud.handlers.link.metadata'));

                try {
                    $metadata = $telegramService->collectSoundcloudMetadata($trackUrl);
                } catch (TooLargeFileForDownloadException) {
                    SpiritBox::deleteMessage($message->chat->id, $message->messageId);
                    SpiritBox::replyMessage(__('telegram.soundcloud.handlers.link.error_large'));

                    return;
                }

                if ($trackInfo = SoundcloudTrack::whereSoundcloudId($metadata->soundcloud_id)->first()) {
                    $trackInfo->send();

                    return;
                }

                DB::transaction(function () use ($metadata, $telegramService, $trackUrl, $message): void {
                    SpiritBox::editMessageText(__('telegram.soundcloud.handlers.link.download'), chatId: $message->chat->id, messageId: $message->messageId);

                    $downloadThrottle = app()->make(RedisServiceInterface::class)->simpleThrottle('download_progress_'.$trackUrl, 2);

                    $telegramService->downloadSoundcloudTrack($trackUrl, $metadata, function (EventData $eventData) use ($downloadThrottle, $message) {
                        if ($eventData->type === 'download_progress') {
                            $downloadThrottle(fn () => SpiritBox::editMessageText(
                                messageId: $message->messageId,
                                chatId: $message->chat->id,
                                text: __('telegram.soundcloud.handlers.link.download').$eventData->meta['percent_str'],
                            ));
                        } elseif ($eventData->service === 'ExtractAudio') {
                            $downloadThrottle(fn () => SpiritBox::editMessageText(
                                messageId: $message->messageId,
                                chatId: $message->chat->id,
                                text: __('telegram.soundcloud.handlers.link.extract'),
                            ));
                        } elseif ($eventData->service === 'Metadata') {
                            $downloadThrottle(fn () => SpiritBox::editMessageText(
                                messageId: $message->messageId,
                                chatId: $message->chat->id,
                                text: __('telegram.soundcloud.handlers.link.thumnail'),
                            ));
                        }
                    })->send();

                    SpiritBox::deleteMessage(chatId: $message->chat->id, messageId: $message->messageId);
                });
            }
        };
    }
}
