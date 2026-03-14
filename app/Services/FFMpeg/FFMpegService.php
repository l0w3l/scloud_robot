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
}
