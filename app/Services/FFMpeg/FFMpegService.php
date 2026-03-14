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
            '-y', // Перезаписывать если файл существует (на случай битых попыток)
            '-reconnect',
            '1',
            '-reconnect_streamed',
            '1',
            '-reconnect_delay_max',
            '5',
            // 'ss' перед '-i' заставляет ffmpeg искать начало потока мгновенно
            '-ss',
            '0',
            '-i',
            $streamUrl,
            '-t',
            (string) $duration,
            '-vn',
            '-acodec',
            'libmp3lame',
            '-b:a',
            '128k', // 128кбит достаточно для превью и быстрее жмется
            '-map_metadata',
            '-1', // Удаляем метаданные для уменьшения размера и исключения ошибок
            '-f',
            'mp3',
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
