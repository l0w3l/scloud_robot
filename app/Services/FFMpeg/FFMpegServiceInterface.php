<?php

declare(strict_types=1);

namespace App\Services\FFMpeg;

use Lowel\LaravelServiceMaker\Services\ServiceInterface;

interface FFMpegServiceInterface extends ServiceInterface
{
    public function getFragmentPath(string $originalTrackUrl, string $streamUrl, int $duration = 10): string;
}
