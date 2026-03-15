<?php

declare(strict_types=1);

namespace App\Services\FFMpeg;

use Lowel\LaravelServiceMaker\Services\ServiceFactoryInterface;

class FFMpegServiceFactory implements ServiceFactoryInterface
{
    public function get(array $params = []): FFMpegServiceInterface
    {
        return new FFMpegService;
    }
}
