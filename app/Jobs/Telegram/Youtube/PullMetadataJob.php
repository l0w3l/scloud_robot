<?php

namespace App\Jobs\Telegram\Youtube;

use App\Models\YoutubeVideo;
use App\Services\Telegram\TelegramServiceInterface;
use App\Telegram\Keyboards\Inline\Youtube\ChoseFormat\ChoseFormatInlineKeyboardFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Lowel\Telepath\Facades\Extrasense;
use Lowel\Telepath\Facades\SpiritBox;
use Phptg\BotApi\Type\Update\Update;

class PullMetadataJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Update $context,
        public string $videoUrl,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Extrasense::imaginate($this->context, function () {
            $telegramService = app()->make(TelegramServiceInterface::class);
            $message = SpiritBox::replyMessage(__('telegram.youtube.inline.chosen.metadata'));

            $youtubeVideoData = $telegramService->collectYoutubeMetadata($this->videoUrl);

            try {
                YoutubeVideo::createFor($youtubeVideoData);
            } catch (\Exception $e) {
                SpiritBox::editMessageText(messageId: $message->messageId, text: __('telegram.youtube.handlers.link.error'));

                throw $e;
            }

            SpiritBox::editMessageText(
                messageId: $message->messageId,
                text: $youtubeVideoData->title,
                replyMarkup: (new ChoseFormatInlineKeyboardFactory)->fromVideo($youtubeVideoData)->build()
            );
        });
    }
}
