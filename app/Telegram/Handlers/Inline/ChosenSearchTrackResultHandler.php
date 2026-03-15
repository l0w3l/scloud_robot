<?php

declare(strict_types=1);

namespace App\Telegram\Handlers\Inline;

use App\Jobs\Telegram\Soundcloud\UploadTrackAsInlineJob;
use App\Models\SoundcloudSearchTrack;
use App\Models\SoundcloudTrack;
use App\Telegram\Keyboards\Inline\Soundcloud\Track\TrackInlineKeyboardFactory;
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
        return static function () {
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

            UploadTrackAsInlineJob::dispatch(Extrasense::update(), $trackUrl);
        };
    }
}
