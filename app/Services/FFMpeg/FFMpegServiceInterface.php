<?php

declare(strict_types=1);

namespace App\Services\FFMpeg;

use Lowel\LaravelServiceMaker\Services\ServiceInterface;

interface FFMpegServiceInterface extends ServiceInterface
{
    public function streamMp3(string $streamUrl): void;
}
