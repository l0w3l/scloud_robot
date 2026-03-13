<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Data\Soundcloud\TrackInfoData;
use App\Exceptions\TooLargeFileForDownloadException;
use App\Models\SoundcloudSearchTrack;
use App\Models\SoundcloudTrack;
use App\Services\YtDlp\YtDlpServiceFactory;
use Illuminate\Support\Facades\Http;
use Lowel\LaravelServiceMaker\Services\AbstractService;
use Str;

class TelegramService extends AbstractService implements TelegramServiceInterface
{
    public function __construct(
        public YtDlpServiceFactory $ytDlpServiceFactory,
    ) {}

    public function resolveSoundcloudLink(string $rawText): null|string|SoundcloudTrack
    {
        // short link
        $shortUrl = Str::of($rawText)->match('/https:\/\/on\.soundcloud\.com\/[A-Za-z0-9_-]+/')->value();

        if (! empty($shortUrl)) {
            if ($existedTrack = SoundcloudTrack::whereShortUrl($shortUrl)->first()) {
                return $existedTrack;
            } else {
                $response = Http::withOptions([
                    'allow_redirects' => false,
                ])->get($shortUrl);

                $directLink = $response->header('Location');

                $trackUrl = Str::of($directLink)
                    ->match('/https:\/\/soundcloud\.com\/[A-Za-z0-9_-]+\/[A-Za-z0-9_-]+(?=[?#\s]|$)/')
                    ->value();

                if ($existedTrack = SoundcloudTrack::wherePageUrl($trackUrl)->first()) {
                    $existedTrack->update(['short_url' => $shortUrl]);

                    return $existedTrack;
                }
            }
        } else {
            $trackUrl = Str::of($rawText)->match('/https:\/\/soundcloud\.com\/[A-Za-z0-9_-]+\/[A-Za-z0-9_-]+(?=[?#\s]|$)/')->value();

            if ($existedTrack = SoundcloudTrack::wherePageUrl($trackUrl)->first()) {
                return $existedTrack;
            }
        }
        if (! empty($trackUrl)) {
            return $trackUrl;
        }

        return null;
    }

    /**
     * @throws TooLargeFileForDownloadException
     */
    public function collectSoundcloudMetadata(string $trackUrl): TrackInfoData
    {
        $metadata = $this->ytDlpServiceFactory
            ->soundcloud()
            ->getInfo($trackUrl);

        $metadata->page_url = $trackUrl;

        foreach ($metadata->formats as $format) {
            if ($format->filesize_approx / 1024 / 1024 > 45) {
                throw new TooLargeFileForDownloadException;
            }
        }

        return $metadata;
    }

    public function downloadSoundcloudTrack(string $trackUrl, TrackInfoData $metadata, ?callable $eventHandler = null): SoundcloudTrack
    {
        $storagePath = $this->ytDlpServiceFactory
            ->soundcloud()
            ->download($trackUrl, $eventHandler);

        return SoundcloudTrack::createFrom($storagePath, $metadata);
    }

    public function smartSoundcloudSearch(string $rawText, int $offset, int $limit): array
    {
        /** @var TrackInfoData[] */
        $tracksFromSearch = $this->ytDlpServiceFactory->soundcloud()->search($rawText, $offset, $limit);

        $tracks = [];
        foreach ($tracksFromSearch as $track) {
            $searchTrack = SoundcloudSearchTrack::createOrFirst([
                'soundcloud_id' => $track->soundcloud_id,
            ], [
                'webpage_url' => $track->webpage_url,
            ]);

            if ($searchTrack->soundcloudTrack()->exists()) {
                $tracks[] = $searchTrack->soundcloudTrack;
            } else {
                $tracks[] = $track;
            }
        }

        return $tracks;
    }
}
