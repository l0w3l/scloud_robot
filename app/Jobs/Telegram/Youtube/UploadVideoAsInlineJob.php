<?php

namespace App\Jobs\Telegram\Youtube;

use App\Data\YtDlp\EventData;
use App\Models\YoutubeVideoFormat;
use App\Services\Redis\RedisServiceInterface;
use App\Services\YtDlp\YtDlpServiceFactory;
use App\Telegram\Keyboards\Inline\Soundcloud\Track\TrackInlineKeyboardFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Lowel\Telepath\Facades\Extrasense;
use Lowel\Telepath\Facades\SpiritBox;
use Phptg\BotApi\Type\InputFile;
use Phptg\BotApi\Type\InputMediaAudio;
use Phptg\BotApi\Type\InputMediaVideo;
use Phptg\BotApi\Type\Update\Update;

class UploadVideoAsInlineJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Update $context,
        public YoutubeVideoFormat $youtubeVideoFormat,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Extrasense::imaginate($this->context, function () {
            $chosenResult = Extrasense::update()->chosenInlineResult;
            $ytDlpServiceFactory = app()->make(YtDlpServiceFactory::class);

            $downloadThrottle = app()->make(RedisServiceInterface::class)->simpleThrottle('download_progress_'.$this->youtubeVideoFormat->video->youtube_id, 2);

            if ($this->youtubeVideoFormat->resolution === 'audio only') {
                $youtubeService = $ytDlpServiceFactory->youtubeMusic();
            } else {
                $youtubeService = $ytDlpServiceFactory->youtube();
            }

            $youtubeService->download(
                "https://youtu.be/{$this->youtubeVideoFormat->video->youtube_id}",
                callback: function (EventData $eventData) use ($downloadThrottle, $chosenResult) {
                    if ($eventData->type === 'download_progress') {
                        $downloadThrottle(fn () => SpiritBox::editMessageCaption(
                            inlineMessageId: $chosenResult->inlineMessageId,
                            caption: __('telegram.youtube.handlers.link.download').$eventData->meta['percent_str'],
                        ));
                    } elseif ($eventData->service === 'Merger') {
                        $downloadThrottle(fn () => SpiritBox::editMessageCaption(
                            inlineMessageId: $chosenResult->inlineMessageId,
                            caption: __('telegram.youtube.handlers.link.extract'),
                        ));
                    }
                },
                format: $this->youtubeVideoFormat->format_id
            );

            if ($this->youtubeVideoFormat->resolution === 'audio only') {
                $message = SpiritBox::sendAudio(
                    chatId: config('services.telegram.storage_chat_id'),
                    audio: InputFile::fromLocalFile($this->youtubeVideoFormat->path()),
                    caption: '@'.Extrasense::profile()->username,
                    duration: $this->youtubeVideoFormat->video->duration,
                    thumbnail: InputFile::fromLocalFile($this->youtubeVideoFormat->video->thumbnail),
                );

                $this->youtubeVideoFormat->update(['file_id' => $message->audio->fileId]);

                SpiritBox::editMessageMedia(
                    media: new InputMediaAudio($message->audio->fileId),
                    inlineMessageId: $chosenResult->inlineMessageId,
                    replyMarkup: (new TrackInlineKeyboardFactory)->make()->build([
                        'song_url' => $this->youtubeVideoFormat->video->url(),
                        'cover_url' => $this->youtubeVideoFormat->video->thumbnail,
                    ]),
                );
            } else {
                $message = SpiritBox::sendVideo(
                    chatId: config('services.telegram.storage_chat_id'),
                    video: InputFile::fromLocalFile($this->youtubeVideoFormat->path()),
                    duration: $this->youtubeVideoFormat->video->duration,
                    width: $this->youtubeVideoFormat->width,
                    height: $this->youtubeVideoFormat->height,
                    thumbnail: InputFile::fromLocalFile($this->youtubeVideoFormat->video->thumbnail),
                    caption: '@'.Extrasense::profile()->username,
                );

                $this->youtubeVideoFormat->update(['file_id' => $message->video->fileId]);

                SpiritBox::editMessageMedia(
                    media: new InputMediaVideo($message->video->fileId),
                    inlineMessageId: $chosenResult->inlineMessageId,
                    replyMarkup: (new TrackInlineKeyboardFactory)->make()->build([
                        'song_url' => $this->youtubeVideoFormat->video->url(),
                        'cover_url' => $this->youtubeVideoFormat->video->thumbnail,
                    ]),
                );
            }

            File::delete($this->youtubeVideoFormat->path());
        });
    }
}
