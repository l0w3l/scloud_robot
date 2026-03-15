<?php

declare(strict_types=1);

namespace App\Services\YtDlp;

use App\Data\Soundcloud\TrackInfoData;
use App\Services\YtDlp\Soundcloud\SoundcloudService;
use Illuminate\Support\Facades\App;
use Lowel\LaravelServiceMaker\Services\ServiceFactoryInterface;
use RuntimeException;

class YtDlpServiceFactory implements ServiceFactoryInterface
{
    public function get(array $params = []): YtDlpServiceInterface
    {
        throw new RuntimeException('unknown service for yt-dlp');
    }

    /**
     * @return YtDlpServiceInterface<TrackInfoData>
     */
    public function soundcloud(): YtDlpServiceInterface
    {
        return App::make(SoundcloudService::class);
    }
}
