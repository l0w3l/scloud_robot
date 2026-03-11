<?php

declare(strict_types=1);

namespace App\Services\YtDlp\Soundcloud;

use App\Data\Soundcloud\TrackInfoData;
use App\Exceptions\Services\Soundcloud\BadFormatsException;
use App\Services\YtDlp\YtDlpServiceInterface;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Lowel\LaravelServiceMaker\Services\AbstractService;
use RuntimeException;

/**
 * @implements YtDlpServiceInterface<TrackInfoData>
 */
class SoundcloudService extends AbstractService implements YtDlpServiceInterface
{
    public function __construct() {}

    public function getInfo(string $url)
    {
        $processResult = Process::run("yt-dlp -J '".htmlspecialchars($url)."'");

        if ($processResult->failed()) {
            throw new BadFormatsException($processResult->errorOutput());
        }

        return TrackInfoData::from($processResult->output());
    }

    public function download(string $url, ?callable $callback = null, ?string $format = null): string
    {
        $parser = new OutputParser;
        $feeder = new Feeder($parser);

        $cmd = [
            'yt-dlp',
            '--newline',

            '--progress-template',
            // "download:" — это scope, DLJSON — маркер, дальше валидный JSON
            'download:DLJSON:{"status":"%(progress.status)s","percent":"%(progress._percent_str)s","downloaded":"%(progress.downloaded_bytes)s","total":"%(progress.total_bytes)s","total_est":"%(progress.total_bytes_estimate)s","speed":"%(progress.speed)s","eta":"%(progress.eta)s","elapsed":"%(progress.elapsed)s"}',

            '-x',
            '--audio-format',
            'mp3',
            '--add-metadata',
            '-o',
            '%(id)s/%(uploader)s - %(title)s.%(ext)s',
            '-P',
            Storage::disk('public')->path('soundcloud'),
            $url,
        ];

        $outputPath = null;

        Process::start($cmd)
            ->wait(function (string $type, string $output) use ($feeder, $callback, &$outputPath): void {
                $lastCall = null;
                $throttleMs = 200;

                foreach ($feeder->feed($output, $type) as $event) {

                    if ($event->service === 'Metadata') {
                        $outputPath = Str::of($event->message)->match('/"(.+)"/')->value();
                    }

                    logger('yt-dlp event', $event->toArray());

                    $callback($event);
                }
            });

        if ($outputPath === null) {
            throw new RuntimeException("Failed save file (output path is null). Reason: {$url}");
        }

        return $outputPath;
    }
}
