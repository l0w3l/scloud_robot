<?php

namespace App\Jobs\Telegram\Soundcloud;

use App\Data\Soundcloud\EventData;
use App\Exceptions\TooLargeFileForDownloadException;
use App\Models\SoundcloudTrack;
use App\Services\Redis\RedisServiceInterface;
use App\Services\Telegram\TelegramServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Lowel\Telepath\Facades\Extrasense;
use Lowel\Telepath\Facades\SpiritBox;
use Phptg\BotApi\Type\Update\Update;

class UploadTrackJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly Update $context,
        public readonly string $trackUrl
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Extrasense::imaginate($this->context, function () {
            app()->setLocale(Auth::guard('telegram')->user()->language_code);

            $telegramService = app()->make(TelegramServiceInterface::class);
            $message = SpiritBox::replyMessage(__('telegram.soundcloud.handlers.link.metadata'));

            try {
                $metadata = $telegramService->collectSoundcloudMetadata($this->trackUrl);
            } catch (TooLargeFileForDownloadException) {
                SpiritBox::deleteMessage($message->chat->id, $message->messageId);
                SpiritBox::replyMessage(__('telegram.soundcloud.handlers.link.error_large'));

                return;
            }

            if ($trackInfo = SoundcloudTrack::whereSoundcloudId($metadata->soundcloud_id)->first()) {
                $trackInfo->send();

                return;
            }

            DB::transaction(function () use ($metadata, $telegramService, $message): void {
                SpiritBox::editMessageText(__('telegram.soundcloud.handlers.link.download'), chatId: $message->chat->id, messageId: $message->messageId);

                $downloadThrottle = app()->make(RedisServiceInterface::class)->simpleThrottle('download_progress_'.$this->trackUrl, 2);

                $telegramService->downloadSoundcloudTrack($this->trackUrl, $metadata, function (EventData $eventData) use ($downloadThrottle, $message) {
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
        });
    }
}
