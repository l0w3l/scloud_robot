<?php

declare(strict_types=1);

namespace App\Services\YtDlp\Youtube;

use App\Data\YtDlp\Youtube\YoutubeVideoData;
use App\Services\YtDlp\Utils\Feeder;
use App\Services\YtDlp\Utils\OutputParser;
use App\Services\YtDlp\YtDlpServiceInterface;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * @implements YtDlpServiceInterface<YoutubeVideoData>
 */
class YoutubeMusicService extends YoutubeService implements YtDlpServiceInterface
{
    public function __construct() {}

    public function download(string $url, ?callable $callback = null, ?string $format = null): string
    {
        $parser = new OutputParser;
        $feeder = new Feeder($parser);

        if ($format === null) {
            throw new RuntimeException('YouTube video format required!');
        }

        $cmd = [
            'yt-dlp',
            '--newline',
            '--progress-template',
            // "download:" — это scope, DLJSON — маркер, дальше валидный JSON
            'download:DLJSON:{"status":"%(progress.status)s","percent":"%(progress._percent_str)s","downloaded":"%(progress.downloaded_bytes)s","total":"%(progress.total_bytes)s","total_est":"%(progress.total_bytes_estimate)s","speed":"%(progress.speed)s","eta":"%(progress.eta)s","elapsed":"%(progress.elapsed)s"}',
            '-f',
            $format,
            '-x',
            '--audio-format',
            'mp3',
            '--add-metadata',
            '-o',
            '%(id)s/'.$format.'/file.%(ext)s',
            '-P',
            Storage::disk('public')->path('youtube'),
            $url,
        ];

        Process::start($cmd)
            ->wait(function (string $type, string $output) use ($feeder, $callback): void {
                foreach ($feeder->feed($output, $type) as $event) {
                    logger('yt-dlp event', $event->toArray());

                    if ($callback !== null) {
                        $callback($event);
                    }
                }
            });

        return '';
    }
}
