<?php

declare(strict_types=1);

namespace App\Services\FFMpeg;

use Illuminate\Support\Facades\Process;
use Lowel\LaravelServiceMaker\Services\AbstractService;

class FFMpegService extends AbstractService implements FFMpegServiceInterface
{
    public function getFragmentPath(string $originalTrackUrl, string $streamUrl, int $duration = 10): string
    {
        // Используем оригинальный URL для кэша, так как $streamUrl всегда разный
        $hash = md5($originalTrackUrl.$duration);
        $path = storage_path("app/tmp/{$hash}.mp3");

        if (file_exists($path)) {
            return $path;
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $command = [
            'ffmpeg',
            '-y',
            '-probesize',
            '32',
            '-analyzeduration',
            '0',
            '-ss',
            '0',
            '-i',
            $streamUrl,
            '-t',
            $duration,
            '-acodec',
            'libmp3lame',
            '-b:a',
            '96k', // Экономим CPU и трафик
            '-map_metadata',
            '-1',
            $path,
        ];

        // Запускаем процесс. Ограничим время выполнения 15 секундами
        $process = Process::run($command)->throw();

        if ($process->failed() || ! file_exists($path) || filesize($path) === 0) {
            throw new \RuntimeException('FFmpeg failed to create fragment');
        }

        return $path;
    }
}
