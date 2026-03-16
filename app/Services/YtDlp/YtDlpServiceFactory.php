<?php

declare(strict_types=1);

namespace App\Services\YtDlp;

use App\Data\YtDlp\Soundcloud\SoundcloudTrackInfoData;
use App\Data\YtDlp\Youtube\YoutubeVideoData;
use App\Services\YtDlp\Soundcloud\SoundcloudService;
use App\Services\YtDlp\Youtube\YoutubeMusicService;
use App\Services\YtDlp\Youtube\YoutubeService;
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
     * @return YtDlpServiceInterface<SoundcloudTrackInfoData>
     */
    public function soundcloud(): YtDlpServiceInterface
    {
        return App::make(SoundcloudService::class);
    }

    /**
     * @return YtDlpServiceInterface<YoutubeVideoData>
     */
    public function youtube(): YtDlpServiceInterface
    {
        return App::make(YoutubeService::class);
    }

    /**
     * @return YtDlpServiceInterface<YoutubeVideoData>
     */
    public function youtubeMusic(): YtDlpServiceInterface
    {
        return App::make(YoutubeMusicService::class);
    }
}
