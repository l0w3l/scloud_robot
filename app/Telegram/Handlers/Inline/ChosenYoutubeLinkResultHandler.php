<?php

declare(strict_types=1);

namespace App\Telegram\Handlers\Inline;

use App\Jobs\Telegram\Youtube\UploadVideoAsInlineJob;
use App\Models\YoutubeVideoFormat;
use App\Telegram\Keyboards\Inline\Soundcloud\Track\TrackInlineKeyboardFactory;
use Lowel\Telepath\Core\Router\Handler\AbstractTelegramHandler;
use Lowel\Telepath\Facades\Extrasense;
use Lowel\Telepath\Facades\SpiritBox;
use Phptg\BotApi\Type\InputMediaVideo;

class ChosenYoutubeLinkResultHandler extends AbstractTelegramHandler
{
    const TAG = 'youtube_link_format_';

    public function pattern(): ?string
    {
        return self::TAG.'.+';
    }

    public function handler(): callable
    {
        return static function () {

            $chosenResult = Extrasense::update()->chosenInlineResult;

            $id = $chosenResult->resultId;

            $videoFormatId = str_replace(self::TAG, '', $id);

            $videoFormat = YoutubeVideoFormat::find($videoFormatId);

            if ($videoFormat->file_id) {
                SpiritBox::editMessageMedia(
                    media: new InputMediaVideo($videoFormat->file_id),
                    inlineMessageId: $chosenResult->inlineMessageId,
                    replyMarkup: (new TrackInlineKeyboardFactory)->make()->build([
                        'song_url' => $videoFormat->video->url(),
                        'cover_url' => $videoFormat->video->thumbnail,
                    ]),
                );

                return;
            }

            UploadVideoAsInlineJob::dispatch(Extrasense::update(), $videoFormat);
        };
    }
}
