<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Data\YtDlp\Soundcloud\SoundcloudTrackInfoData;
use App\Data\YtDlp\Youtube\YoutubeVideoData;
use App\Exceptions\TooLargeFileForDownloadException;
use App\Models\SoundcloudSearchTrack;
use App\Models\SoundcloudTrack;
use App\Models\YoutubeVideo;
use App\Services\YtDlp\YtDlpServiceFactory;
use Illuminate\Support\Facades\Http;
use Lowel\LaravelServiceMaker\Services\AbstractService;
use Str;

class TelegramService extends AbstractService implements TelegramServiceInterface
{
    const YOUTUBE_FILESIZE_BYTES = 50_000_000;

    const SOUNDCLOUD_FILESIZE_KILOBYTES = 50_000;

    public function __construct(
        public YtDlpServiceFactory $ytDlpServiceFactory,
    ) {}

    public function resolveYoutubeLink(string $rawText): null|string|YoutubeVideo
    {
        // short link
        $videoId = Str::of($rawText)->match('/^(?:https?:\/\/)?(?:(?:www|m|music)\.)?(?:youtube\.com\/(?:watch\?v=|shorts\/|embed\/)|youtu\.be\/)([A-Za-z0-9_-]{11})(?:[?&].*)?$/')->value();

        if (! empty($videoId)) {
            if ($existedVideo = YoutubeVideo::whereYoutubeId($videoId)->first()) {
                return $existedVideo;
            } else {
                return $videoId;
            }
        }

        return null;
    }

    public function collectYoutubeMetadata(string $videoUrl): ?YoutubeVideoData
    {
        $videoData = $this->ytDlpServiceFactory->youtube()->getInfo($videoUrl);

        $formats = $videoData->formats;
        $videoData->formats = [];

        // sort by filesize
        usort($formats, function ($a, $b) {
            return $a->filesize <=> $b->filesize;
        });

        $audioCandidate = null; // best audio candidate
        $resolutionMap = []; // collect same resolutions
        foreach ($formats as $format) {
            if (($format->filesize ?? PHP_INT_MAX) < self::YOUTUBE_FILESIZE_BYTES) {
                if ($format->resolution == 'audio only') {
                    if ($format->filesize > ($audioCandidate->filesize ?? 0)) {
                        $audioCandidate = $format;
                    }
                } else {
                    if (($resolutionMap[$format->resolution] ?? null) === null) {
                        $videoData->formats[] = $format;
                        $resolutionMap[$format->resolution] = true;
                    }
                }
            }
        }

        // check sound compability
        if ($audioCandidate) {
            foreach ($videoData->formats as &$format) {
                if ($format->filesize + $audioCandidate->filesize > self::YOUTUBE_FILESIZE_BYTES) {
                    $format->resolution = 'no audio '.$format->resolution;
                } else {
                    $format->filesize += $audioCandidate->filesize;
                    $format->format_id .= '+'.$audioCandidate->format_id;
                }
            }

            $videoData->formats[] = $audioCandidate;
        } else {
            foreach ($videoData->formats as &$format) {
                $format->resolution = 'no audio '.$format->resolution;
            }
        }

        if (empty($videoData->formats)) {
            throw new TooLargeFileForDownloadException('File to large for download');
        }

        return $videoData;
    }

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
    public function collectSoundcloudMetadata(string $trackUrl): SoundcloudTrackInfoData
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

    public function downloadSoundcloudTrack(string $trackUrl, SoundcloudTrackInfoData $metadata, ?callable $eventHandler = null): SoundcloudTrack
    {
        $storagePath = $this->ytDlpServiceFactory
            ->soundcloud()
            ->download($trackUrl, $eventHandler);

        return SoundcloudTrack::createFrom($storagePath, $metadata);
    }

    public function smartSoundcloudSearch(string $rawText, int $offset, int $limit): array
    {
        /** @var SoundcloudTrackInfoData[] */
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
