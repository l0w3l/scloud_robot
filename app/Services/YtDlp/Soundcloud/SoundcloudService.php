<?php

declare(strict_types=1);

namespace App\Services\YtDlp\Soundcloud;

use App\Data\Soundcloud\TrackInfoData;
use App\Exceptions\Services\Soundcloud\BadFormatsException;
use App\Services\YtDlp\YtDlpServiceInterface;
use Illuminate\Support\Facades\Cache;
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

                    if ($callback !== null) {
                        $callback($event);
                    }
                }
            });

        if ($outputPath === null) {
            throw new RuntimeException("Failed save file (output path is null). Reason: {$url}");
        }

        return $outputPath;
    }

    public function search(string $searchText, int $offset, int $limit = 20): array
    {
        $limit = $offset + $limit;

        $cmd = [
            'yt-dlp',
            '-J',
            '--match-filter',
            '"duration > 31 & availability != premium_only"',
            '--playlist-start',
            $offset + 1,
            '--flat-playlist',
            "\"scsearch{$limit}:{$searchText}\"",
        ];

        $processResult = Process::run(implode(' ', $cmd));

        if ($processResult->failed()) {
            throw new BadFormatsException($processResult->errorOutput());
        }

        $metadataCollection = [];

        foreach (json_decode($processResult->output(), true)['entries'] as $metadata) {
            $metadataCollection[] = TrackInfoData::from($metadata);
        }

        return $metadataCollection;
    }

    public function streamUrl(string $trackUrl): string
    {
        return Cache::remember('stream:'.md5($trackUrl), 3600, function () use ($trackUrl) {

            $result = Process::run([
                'yt-dlp',
                '-g',
                $trackUrl,
            ]);

            if (! $result->successful()) {
                return null;
            }

            return trim($result->output());
        });
    }

    public function downloadSection(string $trackUrl, int $duration = 10): string
    {
        $hash = md5($trackUrl.$duration);
        $path = storage_path("app/tmp/{$hash}.mp3");

        if (file_exists($path)) {
            return $path;
        }

        // Создаем директорию если нет
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        // Оптимизированная команда:
        // --no-playlist: не тратить время на парсинг плейлиста
        // --format: выбираем только mp3 или m4a (быстрее обрабатывается)
        $command = [
            'yt-dlp',
            '--no-playlist',
            '--extract-audio',
            '--audio-format',
            'mp3',
            '--download-sections',
            "*0-$duration",
            '--force-keyframes-at-cuts', // Улучшает точность для аудио
            '-o',
            $path,
            $trackUrl,
        ];

        $process = Process::run($command);

        if (! $process->successful() || ! file_exists($path)) {
            throw new RuntimeException('yt-dlp failed: '.$process->errorOutput());
        }

        return $path;
    }
}
