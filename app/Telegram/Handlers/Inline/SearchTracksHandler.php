<?php

declare(strict_types=1);

namespace App\Telegram\Handlers\Inline;

use App\Data\Soundcloud\EventData;
use App\Models\SoundcloudTrack;
use App\Services\YtDlp\YtDlpServiceFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Lowel\Telepath\Core\Router\Handler\AbstractTelegramHandler;
use Lowel\Telepath\Facades\Extrasense;
use Lowel\Telepath\Facades\SpiritBox;
use Phptg\BotApi\Type\Inline\InlineQueryResultCachedAudio;
use Str;

class SearchTracksHandler extends AbstractTelegramHandler
{
    public function handler(): callable
    {
        return static function (YtDlpServiceFactory $ytDlpServiceFactory) {
            $update = Extrasense::update();
            $inlineQuery = $update->inlineQuery;

            $soundcloudService = $ytDlpServiceFactory->soundcloud();

            $rawText = $inlineQuery->query;

            // short link
            $shortUrl = Str::of($rawText)->match('/https:\/\/on\.soundcloud\.com\/[A-Za-z0-9_-]+/')->value();

            if (! empty($shortUrl)) {
                if ($trackInfo = SoundcloudTrack::whereShortUrl($shortUrl)->first()) {
                    SpiritBox::answerInlineQuery($inlineQuery->id, [
                        new InlineQueryResultCachedAudio((string) $trackInfo->id, $trackInfo->file_id),
                    ]);

                    return;
                } else {
                    $response = Http::withOptions([
                        'allow_redirects' => false,
                    ])->get($shortUrl);

                    $directLink = $response->header('Location');
                    $trackUrl = Str::of($directLink)->match('/https:\/\/soundcloud\.com\/[A-Za-z0-9_-]+\/[A-Za-z0-9_-]+/')->value();

                    if ($trackInfo = SoundcloudTrack::wherePageUrl($trackUrl)->first()) {
                        $trackInfo->update(['short_url' => $shortUrl]);

                        SpiritBox::answerInlineQuery($inlineQuery->id, [
                            new InlineQueryResultCachedAudio((string) $trackInfo->id, $trackInfo->file_id),
                        ]);

                        return;
                    }
                }
            } else {
                $trackUrl = Str::of($rawText)->match('/https:\/\/soundcloud\.com\/[A-Za-z0-9_-]+\/[A-Za-z0-9_-]+/')->value();

                if ($trackInfo = SoundcloudTrack::wherePageUrl($trackUrl)->first()) {
                    SpiritBox::answerInlineQuery($inlineQuery->id, [
                        new InlineQueryResultCachedAudio('soundcloud_tracks_'.$trackInfo->id, $trackInfo->file_id),
                    ]);

                    return;
                }
            }

            if (empty($trackUrl)) {
                $rawText = strtolower($rawText);

                $tracks = SoundcloudTrack::whereRaw('LOWER(title) LIKE ?', ["%{$rawText}%"])
                    ->orWhereRaw('LOWER(track) LIKE ?', ["%{$rawText}%"])
                    ->orWhereRaw('LOWER(artists) LIKE ?', ["%{$rawText}%"])
                    ->orWhereRaw('LOWER(uploader) LIKE ?', ["%{$rawText}%"])
                    ->offset((int) $inlineQuery->offset)
                    ->limit(20)
                    ->get();

                $inlineResults = [];

                foreach ($tracks as $track) {
                    $inlineResults[] = new InlineQueryResultCachedAudio('soundcloud_tracks_'.$track->id, $track->file_id);
                }

                SpiritBox::answerInlineQuery($inlineQuery->id, $inlineResults, nextOffset: (string) ((int) $inlineQuery->offset + 20));

                return;
            }

            $metadata = $soundcloudService->getInfo($trackUrl);
            $metadata->page_url = $trackUrl;
            $metadata->short_url = $shortUrl;

            foreach ($metadata->formats as $format) {
                if ($format->filesize_approx / 1024 / 1024 > 45) {
                    return;
                }
            }

            if ($trackInfo = SoundcloudTrack::whereSoundcloudId($metadata->soundcloud_id)->first()) {
                SpiritBox::answerInlineQuery($inlineQuery->id, [
                    new InlineQueryResultCachedAudio('soundcloud_tracks_'.$trackInfo->id, $trackInfo->file_id),
                ]);

                return;
            }

            DB::transaction(function () use ($metadata, $soundcloudService, $trackUrl, $inlineQuery): void {
                $filePath = $soundcloudService->download($trackUrl, function (EventData $event) {});

                $trackInfo = SoundcloudTrack::createFrom($filePath, $metadata);

                SpiritBox::answerInlineQuery($inlineQuery->id, [
                    new InlineQueryResultCachedAudio('soundcloud_tracks_'.$trackInfo->id, $trackInfo->file_id),
                ]);
            });
        };
    }
}
