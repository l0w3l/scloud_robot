<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Data\YtDlp\Soundcloud\SoundcloudTrackInfoData;
use App\Data\YtDlp\Youtube\YoutubeVideoData;
use App\Models\SoundcloudTrack;
use App\Models\YoutubeVideo;
use Lowel\LaravelServiceMaker\Services\ServiceInterface;

interface TelegramServiceInterface extends ServiceInterface
{
    /**
     * @return null|string|YoutubeVideo - video url, existed video record in db or nothing
     */
    public function resolveYoutubeLink(string $rawText): null|string|YoutubeVideo;

    public function collectYoutubeMetadata(string $videoUrl): ?YoutubeVideoData;

    public function resolveSoundcloudLink(string $rawText): null|string|SoundcloudTrack;

    public function collectSoundcloudMetadata(string $trackUrl): SoundcloudTrackInfoData;

    public function downloadSoundcloudTrack(string $trackUrl, SoundcloudTrackInfoData $metadata, ?callable $eventHadnler = null): SoundcloudTrack;

    /**
     * @return array<int, SoundcloudTrack|SoundcloudTrackInfoData>
     */
    public function smartSoundcloudSearch(string $rawText, int $offset, int $limit): array;
}
