<?php

declare(strict_types=1);

namespace App\Telegram\Handlers\Inline;

use App\Data\Soundcloud\EventData;
use App\Exceptions\TooLargeFileForDownloadException;
use App\Models\SoundcloudSearchTrack;
use App\Models\SoundcloudTrack;
use App\Services\Redis\RedisServiceInterface;
use App\Services\Telegram\TelegramServiceInterface;
use App\Telegram\Keyboards\Inline\Soundcloud\Track\TrackInlineKeyboardFactory;
use Illuminate\Support\Facades\DB;
use Lowel\Telepath\Core\Router\Handler\AbstractTelegramHandler;
use Lowel\Telepath\Facades\Extrasense;
use Lowel\Telepath\Facades\SpiritBox;
use Phptg\BotApi\Type\InputMediaAudio;

class ChosenSearchTrackResultHandler extends AbstractTelegramHandler
{
    const TAG = 'soundcloud_search_tracks_';

    public function pattern(): ?string
    {
        return self::TAG.'.+';
    }

    public function handler(): callable
    {
        return static function (TelegramServiceInterface $telegramService) {
            $chosenResult = Extrasense::update()->chosenInlineResult;

            $id = $chosenResult->resultId;

            $trackId = str_replace(self::TAG, '', $id);

            $searchedTrack = SoundcloudSearchTrack::where('soundcloud_id', $trackId)->first();
            $trackUrl = $searchedTrack->webpage_url;

            if ($existedTrack = SoundcloudTrack::wherePageUrl($trackUrl)->first()) {
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

            try {
                SpiritBox::editMessageCaption(
                    inlineMessageId: $chosenResult->inlineMessageId,
                    caption: __('telegram.soundcloud.inline.chosen.metadata'),
                );
                $metadata = $telegramService->collectSoundcloudMetadata($trackUrl);
            } catch (TooLargeFileForDownloadException) {
                SpiritBox::editMessageCaption(
                    inlineMessageId: $chosenResult->inlineMessageId,
                    caption: __('telegram.soundcloud.inline.chosen.error'),
                    replyMarkup: (new TrackInlineKeyboardFactory)->make()->build([
                        'song_url' => $trackUrl,
                        'cover_url' => $trackUrl,
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

            DB::transaction(function () use ($metadata, $telegramService, $trackUrl, $chosenResult): void {
                SpiritBox::editMessageCaption(
                    inlineMessageId: $chosenResult->inlineMessageId,
                    caption: __('telegram.soundcloud.inline.chosen.download'),
                );

                $downloadThrottle = app()->make(RedisServiceInterface::class)->simpleThrottle('download_progress_'.$trackUrl, 2);
                $track = $telegramService->downloadSoundcloudTrack($trackUrl, $metadata, function (EventData $eventData) use ($chosenResult, $downloadThrottle) {
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
                            caption: __('telegram.soundcloud.inline.chosen.metadata'),
                        ));
                    }
                });

                SpiritBox::editMessageMedia(
                    inlineMessageId: $chosenResult->inlineMessageId,
                    media: new InputMediaAudio($track->file_id),
                    replyMarkup: (new TrackInlineKeyboardFactory)->make()->build([
                        'song_url' => $trackUrl,
                        'cover_url' => $metadata->thumbnails->toCollection()->last()->url,
                    ]),
                );
            });
        };
    }
}
