<?php

declare(strict_types=1);

namespace App\Telegram\Keyboards\Inline\Youtube\ChoseFormat\Buttons;

use App\Data\YtDlp\Youtube\YoutubeVideoData;
use App\Data\YtDlp\Youtube\YoutubeVideoFormatData;
use App\Jobs\Telegram\Youtube\UploadVideoJob;
use App\Models\YoutubeVideo;
use App\Models\YoutubeVideoFormat;
use App\Services\YtDlp\YtDlpServiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Lowel\Telepath\Core\Router\Keyboard\Buttons\Inline\AbstractCallbackButton;
use Lowel\Telepath\Facades\Extrasense;
use Lowel\Telepath\Facades\SpiritBox;

class UploadChosenFormatButton extends AbstractCallbackButton
{
    public function __construct(
        public null|YoutubeVideoData|YoutubeVideo $video = null,
        public null|YoutubeVideoFormatData|YoutubeVideoFormat $format = null,
    ) {}

    public function handle(): callable
    {
        $callbackDataId = $this->callbackDataId();

        return static function (YtDlpServiceFactory $ytDlpServiceFactory) use ($callbackDataId) {
            $data = Extrasense::update()->callbackQuery->data;

            [$videoId, $formatId] = explode('|||', (str_replace($callbackDataId, '', $data)));

            $format = YoutubeVideoFormat::whereFormatId($formatId)
                ->whereHas(
                    'video',
                    fn (Builder $builder) => $builder->where('youtube_id', $videoId)
                )->first();

            if ($format) {
                if ($format->file_id) {
                    $format->send();

                    SpiritBox::deleteMessage();
                } else {
                    SpiritBox::editMessageText(text: __('telegram.youtube.handlers.link.download'));

                    UploadVideoJob::dispatchSync(Extrasense::update(), $format);
                }
            } else {
                SpiritBox::answerCallbackQuery(Extrasense::update()->inlineQuery->id, __('telegram.youtube.handlers.link.error'));
            }
        };
    }

    public function callbackData(array $args = []): int|string|callable
    {
        return $this->video->youtube_id.'|||'.$this->format->format_id;
    }

    public function text(array $args = []): int|string|callable
    {
        $filesize = $this->format->filesize;
        $resolution = $this->format->resolution;

        if ($resolution !== 'audio only') {
            $resolution = explode('x', $resolution)[0].'p';
        }

        return "{$resolution} (~{$this->humanFileSize($filesize)})";
    }

    public function humanFileSize($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
