<?php

declare(strict_types=1);

namespace App\Telegram\Handlers;

use App\Jobs\Telegram\Youtube\PullMetadataJob;
use App\Models\YoutubeVideo;
use App\Services\Telegram\TelegramServiceInterface;
use App\Telegram\Keyboards\Inline\Youtube\ChoseFormat\ChoseFormatInlineKeyboardFactory;
use Lowel\Telepath\Core\Router\Handler\AbstractTelegramHandler;
use Lowel\Telepath\Facades\Extrasense;
use Lowel\Telepath\Facades\SpiritBox;

class DownloadYoutubeHandler extends AbstractTelegramHandler
{
    public function handler(): callable
    {
        return static function (TelegramServiceInterface $telegramService) {
            $message = Extrasense::message();

            $videoUrl = $telegramService->resolveYoutubeLink($message->text ?? '');

            if (($existedVideo = $videoUrl) instanceof YoutubeVideo) {
                SpiritBox::replyMessage(
                    text: $videoUrl->title,
                    replyMarkup: (new ChoseFormatInlineKeyboardFactory)->fromVideo($existedVideo)->build()
                );

                return;
            } elseif (is_string($videoUrl)) {
                PullMetadataJob::dispatch(Extrasense::update(), $videoUrl);
            }
        };
    }
}
