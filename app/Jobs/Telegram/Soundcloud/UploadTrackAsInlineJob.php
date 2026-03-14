<?php

namespace App\Jobs\Telegram\Soundcloud;

use App\Data\Soundcloud\EventData;
use App\Exceptions\TooLargeFileForDownloadException;
use App\Models\SoundcloudTrack;
use App\Services\Redis\RedisServiceInterface;
use App\Services\Telegram\TelegramServiceInterface;
use App\Telegram\Keyboards\Inline\Soundcloud\Track\TrackInlineKeyboardFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Lowel\Telepath\Facades\Extrasense;
use Lowel\Telepath\Facades\SpiritBox;
use Phptg\BotApi\Type\InputMediaAudio;
use Phptg\BotApi\Type\Update\Update;

class UploadTrackAsInlineJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Update $context,
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
            $telegramService = app()->make(TelegramServiceInterface::class);
            $chosenResult = Extrasense::update()->chosenInlineResult;

            try {
                SpiritBox::editMessageCaption(
                    inlineMessageId: $chosenResult->inlineMessageId,
                    caption: __('telegram.soundcloud.inline.chosen.metadata'),
                );
                $metadata = $telegramService->collectSoundcloudMetadata($this->trackUrl);
            } catch (TooLargeFileForDownloadException) {
                SpiritBox::editMessageCaption(
                    inlineMessageId: $chosenResult->inlineMessageId,
                    caption: __('telegram.soundcloud.inline.chosen.error'),
                    replyMarkup: (new TrackInlineKeyboardFactory)->make()->build([
                        'song_url' => $this->trackUrl,
                        'cover_url' => $this->trackUrl,
                    ]),
                );

                return;
            }

            if ($existedTrack = SoundcloudTrack::whereSoundcloudId($metadata->soundcloud_id)->first()) {
                SpiritBox::editMessageMedia(
                    inlineMessageId: $chosenResult->inlineMessageId,
                    media: new InputMediaAudio($existedTrack->file_id),
                    replyMarkup: (new TrackInlineKeyboardFactory)->make()->build([
                        'song_url' => $existedTrack->page_url,
                        'cover_url' => $existedTrack->thumbnails->last()->url,
                    ]),
                );

                return;
            }

            DB::transaction(function () use ($metadata, $telegramService, $chosenResult): void {
                SpiritBox::editMessageCaption(
                    inlineMessageId: $chosenResult->inlineMessageId,
                    caption: __('telegram.soundcloud.inline.chosen.download'),
                );

                $downloadThrottle = app()->make(RedisServiceInterface::class)->simpleThrottle('download_progress_'.$this->trackUrl, 2);
                $track = $telegramService->downloadSoundcloudTrack($this->trackUrl, $metadata, function (EventData $eventData) use ($chosenResult, $downloadThrottle) {
                    if ($eventData->type === 'download_progress') {
                        $downloadThrottle(fn () => SpiritBox::editMessageCaption(
                            inlineMessageId: $chosenResult->inlineMessageId,
                            caption: __('telegram.soundcloud.inline.chosen.download').$eventData->meta['percent_str'],
                        ));
                    } elseif ($eventData->service === 'ExtractAudio') {
                        $downloadThrottle(fn () => SpiritBox::editMessageCaption(
                            inlineMessageId: $chosenResult->inlineMessageId,
                            caption: __('telegram.soundcloud.inline.chosen.extract'),
                        ));
                    } elseif ($eventData->service === 'Metadata') {
                        $downloadThrottle(fn () => SpiritBox::editMessageCaption(
                            inlineMessageId: $chosenResult->inlineMessageId,
                            caption: __('telegram.soundcloud.inline.chosen.thumnail'),
                        ));
                    }
                });

                SpiritBox::editMessageMedia(
                    inlineMessageId: $chosenResult->inlineMessageId,
                    media: new InputMediaAudio($track->file_id),
                    replyMarkup: (new TrackInlineKeyboardFactory)->make()->build([
                        'song_url' => $this->trackUrl,
                        'cover_url' => $metadata->thumbnails->toCollection()->last()->url,
                    ]),
                );
            });
        });
    }
}
