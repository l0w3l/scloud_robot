<?php

namespace App\Jobs\Telegram\Youtube;

use App\Data\YtDlp\EventData;
use App\Models\YoutubeVideoFormat;
use App\Services\Redis\RedisServiceInterface;
use App\Services\YtDlp\YtDlpServiceFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Lowel\Telepath\Facades\Extrasense;
use Lowel\Telepath\Facades\SpiritBox;
use Phptg\BotApi\Type\InputFile;
use Phptg\BotApi\Type\Update\Update;

class UploadVideoJob implements ShouldQueue
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
            $message = Extrasense::message();
            $ytDlpServiceFactory = app()->make(YtDlpServiceFactory::class);

            $downloadThrottle = app()->make(RedisServiceInterface::class)->simpleThrottle('download_progress_'.$this->youtubeVideoFormat->video->youtube_id, 2);

            if ($this->youtubeVideoFormat->resolution === 'audio only') {
                $youtubeService = $ytDlpServiceFactory->youtubeMusic();
            } else {
                $youtubeService = $ytDlpServiceFactory->youtube();
            }

            $youtubeService->download(
                "https://youtu.be/{$this->youtubeVideoFormat->video->youtube_id}",
                format: $this->youtubeVideoFormat->format_id,
                callback: function (EventData $eventData) use ($downloadThrottle, $message) {
                    if ($eventData->type === 'download_progress') {
                        $downloadThrottle(fn () => SpiritBox::editMessageText(
                            messageId: $message->messageId,
                            text: __('telegram.youtube.handlers.link.download').$eventData->meta['percent_str'],
                        ));
                    } elseif ($eventData->service === 'Merger') {
                        $downloadThrottle(fn () => SpiritBox::editMessageText(
                            messageId: $message->messageId,
                            text: __('telegram.youtube.handlers.link.extract'),
                        ));
                    }
                }
            );

            if ($this->youtubeVideoFormat->resolution === 'audio only') {
                $message = SpiritBox::sendAudio(
                    audio: InputFile::fromLocalFile($this->youtubeVideoFormat->path()),
                    caption: '@'.Extrasense::profile()->username,
                    duration: $this->youtubeVideoFormat->video->duration,
                    thumbnail: InputFile::fromLocalFile($this->youtubeVideoFormat->video->thumbnail),
                );
            } else {
                $message = SpiritBox::sendVideo(
                    video: InputFile::fromLocalFile($this->youtubeVideoFormat->path()),
                    caption: '@'.Extrasense::profile()->username,
                    duration: $this->youtubeVideoFormat->video->duration,
                    width: $this->youtubeVideoFormat->width,
                    height: $this->youtubeVideoFormat->height,
                    thumbnail: InputFile::fromLocalFile($this->youtubeVideoFormat->video->thumbnail),
                );
            }

            SpiritBox::deleteMessage();

            $this->youtubeVideoFormat->update(['file_id' => $message->video->fileId ?? $message->audio->fileId]);

            File::delete($this->youtubeVideoFormat->path());
        });
    }
}
