<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Data\Soundcloud\EventData;
use App\Data\Soundcloud\TrackInfoData;
use App\Exceptions\TooLargeFileForDownloadException;
use App\Models\SoundcloudTrack;
use App\Services\YtDlp\YtDlpServiceFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Lowel\LaravelServiceMaker\Services\AbstractService;
use Lowel\Telepath\Facades\Extrasense;
use Lowel\Telepath\Facades\SpiritBox;
use Phptg\BotApi\Type\Inline\InlineQueryResultCachedAudio;
use Str;

class TelegramService extends AbstractService implements TelegramServiceInterface
{
    public function __construct(
        public YtDlpServiceFactory $ytDlpServiceFactory,
    ) {}

    public function resolveSoundcloudLinkInInlineQuery(): void
    {
        $inlineQuery = Extrasense::update()->inlineQuery;

        $existedTrack = $this->resolveSoundcloudLink($inlineQuery->query);

        if ($existedTrack instanceof SoundcloudTrack) {
            SpiritBox::answerInlineQuery($inlineQuery->id, [
                new InlineQueryResultCachedAudio((string) $existedTrack->id, $existedTrack->file_id),
            ]);
        } elseif (is_string($trackUrl = $existedTrack)) {
            try {
                $metadata = $this->collectSoundcloudMetadata($trackUrl);
            } catch (TooLargeFileForDownloadException) {
                return;
            }

            if ($trackInfo = SoundcloudTrack::whereSoundcloudId($metadata->soundcloud_id)->first()) {
                SpiritBox::answerInlineQuery($inlineQuery->id, [
                    new InlineQueryResultCachedAudio('soundcloud_tracks_'.$trackInfo->id, $trackInfo->file_id),
                ]);

                return;
            }

            DB::transaction(function () use ($metadata, $trackUrl, $inlineQuery): void {
                $filePath = $this->ytDlpServiceFactory
                    ->soundcloud()
                    ->download($trackUrl, function (EventData $event) {});

                $trackInfo = SoundcloudTrack::createFrom($filePath, $metadata);

                SpiritBox::answerInlineQuery($inlineQuery->id, [
                    new InlineQueryResultCachedAudio('soundcloud_tracks_'.$trackInfo->id, $trackInfo->file_id),
                ]);
            });
        } else {
            $rawText = strtolower($inlineQuery->query);

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
        }
    }

    public function resolveSoundcloudLinkInMessage(): void
    {
        $message = Extrasense::message();

        $existedTrack = $this->resolveSoundcloudLink($message->text ?? '');

        if ($existedTrack instanceof SoundcloudTrack) {
            $existedTrack->send();
        } elseif (is_string($trackUrl = $existedTrack)) {
            $message = SpiritBox::replyMessage('Metadata');

            try {
                $metadata = $this->collectSoundcloudMetadata($trackUrl);
            } catch (TooLargeFileForDownloadException) {
                SpiritBox::deleteMessage($message->chat->id, $message->messageId);
                SpiritBox::replyMessage('File too heavy and cannot be downloaded');

                return;
            }

            if ($trackInfo = SoundcloudTrack::whereSoundcloudId($metadata->soundcloud_id)->first()) {
                $trackInfo->send();

                return;
            }

            DB::transaction(function () use ($metadata, $trackUrl, $message): void {
                SpiritBox::editMessageText('Preparations', chatId: $message->chat->id, messageId: $message->messageId);

                $filePath = $this->ytDlpServiceFactory
                    ->soundcloud()
                    ->download($trackUrl, function (EventData $event) {});

                SoundcloudTrack::createFrom($filePath, $metadata)
                    ->send();

                SpiritBox::deleteMessage(chatId: $message->chat->id, messageId: $message->messageId);
            });
        }
    }

    private function resolveSoundcloudLink(string $rawText): null|string|SoundcloudTrack
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
                $trackUrl = Str::of($directLink)->match('/https:\/\/soundcloud\.com\/[A-Za-z0-9_-]+\/[A-Za-z0-9_-]+/')->value();

                if ($existedTrack = SoundcloudTrack::wherePageUrl($trackUrl)->first()) {
                    $existedTrack->update(['short_url' => $shortUrl]);

                    return $existedTrack;
                }
            }
        } else {
            $trackUrl = Str::of($rawText)->match('/https:\/\/soundcloud\.com\/[A-Za-z0-9_-]+\/[A-Za-z0-9_-]+/')->value();

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
    private function collectSoundcloudMetadata(string $trackUrl): TrackInfoData
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
}
