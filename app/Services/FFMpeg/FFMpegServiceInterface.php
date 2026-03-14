<?php

declare(strict_types=1);

namespace App\Services\FFMpeg;

use Lowel\LaravelServiceMaker\Services\ServiceInterface;

interface FFMpegServiceInterface extends ServiceInterface
{
    public function streamMp3(string $streamUrl): void;

    public function getFragmentPath(string $streamUrl, int $duration = 10): string;
}
