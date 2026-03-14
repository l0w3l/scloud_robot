<?php

declare(strict_types=1);

namespace App\Services\FFMpeg;

use Illuminate\Support\Facades\Process;
use Lowel\LaravelServiceMaker\Services\AbstractService;

class FFMpegService extends AbstractService implements FFMpegServiceInterface
{
    public function streamMp3(string $streamUrl): void
    {
        $command = [
            'ffmpeg',
            '-i',
            $streamUrl,
            '-vn',
            '-acodec',
            'libmp3lame',
            '-f',
            'mp3',
            '-',
        ];

        Process::start($command)
            ->wait(function (string $_, string $data) {
                echo $data;
                flush();
            });
    }

    public function getFragmentPath(string $streamUrl, int $duration = 10): string
    {
        $hash = md5($streamUrl.$duration);
        $path = storage_path("app/tmp/{$hash}.mp3");

        if (! file_exists($path)) {
            $command = [
                'ffmpeg',
                '-loglevel',
                'error',
                '-ss',
                '0',
                '-i',
                $streamUrl,
                '-t',
                (string) $duration,
                '-vn',
                '-acodec',
                'libmp3lame',
                '-ab',
                '192k',
                '-y', // перезаписать если есть
                $path,
            ];

            Process::run($command);
        }

        return $path;
    }
}
