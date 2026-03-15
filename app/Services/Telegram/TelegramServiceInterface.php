<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Data\Soundcloud\TrackInfoData;
use App\Models\SoundcloudTrack;
use Lowel\LaravelServiceMaker\Services\ServiceInterface;

interface TelegramServiceInterface extends ServiceInterface
{
    public function resolveSoundcloudLink(string $rawText): null|string|SoundcloudTrack;

    public function collectSoundcloudMetadata(string $trackUrl): TrackInfoData;

    public function downloadSoundcloudTrack(string $trackUrl, TrackInfoData $metadata, ?callable $eventHadnler = null): SoundcloudTrack;

    /**
     * @return array<int, SoundcloudTrack|TrackInfoData>
     */
    public function smartSoundcloudSearch(string $rawText, int $offset, int $limit): array;
}
