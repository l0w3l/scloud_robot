<?php

declare(strict_types=1);

namespace App\Telegram\Handlers\Inline;

use App\Data\YtDlp\Soundcloud\SoundcloudTrackInfoData;
use App\Exceptions\TooLargeFileForDownloadException;
use App\Models\SoundcloudTrack;
use App\Models\YoutubeVideo;
use App\Models\YoutubeVideoFormat;
use App\Services\Telegram\TelegramServiceInterface;
use App\Telegram\Keyboards\Inline\Soundcloud\Track\TrackInlineKeyboardFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Lowel\Telepath\Core\Router\Handler\AbstractTelegramHandler;
use Lowel\Telepath\Facades\Extrasense;
use Lowel\Telepath\Facades\SpiritBox;
use Phptg\BotApi\FailResult;
use Phptg\BotApi\Type\Inline\InlineQueryResult;
use Phptg\BotApi\Type\Inline\InlineQueryResultArticle;
use Phptg\BotApi\Type\Inline\InlineQueryResultAudio;
use Phptg\BotApi\Type\Inline\InlineQueryResultCachedAudio;
use Phptg\BotApi\Type\Inline\InlineQueryResultCachedVideo;
use Phptg\BotApi\Type\Inline\InlineQueryResultsButton;
use Phptg\BotApi\Type\Inline\InputTextMessageContent;

class SearchTracksHandler extends AbstractTelegramHandler
{
    const LIMIT = 20;

    public function handler(): callable
    {
        return static function (TelegramServiceInterface $telegramService): void {
            $inlineQuery = Extrasense::update()->inlineQuery;

            $existedTrack = $telegramService->resolveSoundcloudLink($inlineQuery->query);

            dump($existedTrack);
            if ($existedTrack !== null) {
                self::handleSoundcloudSearchQuery($telegramService, $existedTrack);

                return;
            }

            $youtubeVideo = $telegramService->resolveYoutubeLink($inlineQuery->query);

            if ($youtubeVideo !== null) {
                self::handleYoutubeSearchQuery($telegramService, $youtubeVideo);

                return;
            }

            $rawText = strtolower($inlineQuery->query);

            if (empty($rawText)) {
                $user = Auth::guard('telegram')->user();

                $tracksByUser = $user->soundcloudTracks()
                    ->offset((int) $inlineQuery->offset)
                    ->limit(self::LIMIT)
                    ->latest('user_soundcloud_tracks.updated_at')
                    ->get();

                self::answerInlineQuerySoundcloud($tracksByUser->all());
            } else {
                $tracksFromSearch = $telegramService->smartSoundcloudSearch($rawText, (int) $inlineQuery->offset, self::LIMIT);

                self::answerInlineQuerySoundcloud($tracksFromSearch);
            }
        };
    }

    private static function handleSoundcloudSearchQuery(TelegramServiceInterface $telegramService, SoundcloudTrack|string $existedTrack): void
    {
        if ($existedTrack instanceof SoundcloudTrack) {
            self::answerInlineQuerySoundcloud($existedTrack);
        } elseif (is_string($trackUrl = $existedTrack)) {
            try {
                $metadata = $telegramService->collectSoundcloudMetadata($trackUrl);
            } catch (TooLargeFileForDownloadException) {
                return;
            }

            if ($existedTrack = SoundcloudTrack::whereSoundcloudId($metadata->soundcloud_id)->first()) {
                self::answerInlineQuerySoundcloud($existedTrack);
            }

            DB::transaction(function () use ($metadata, $telegramService, $trackUrl): void {
                $track = $telegramService->downloadSoundcloudTrack($trackUrl, $metadata);

                self::answerInlineQuerySoundcloud($track);
            });
        }
    }

    public static function answerInlineQuerySoundcloud(array|SoundcloudTrack|SoundcloudTrackInfoData $trackInfo): bool|FailResult
    {
        if ($trackInfo instanceof SoundcloudTrack || $trackInfo instanceof SoundcloudTrackInfoData) {
            return SpiritBox::answerInlineQuery(
                inlineQueryId: Extrasense::update()->inlineQuery->id,
                results: [
                    self::resolveInlineQueryResultSoundcloud($trackInfo),
                ],
                cacheTime: 300,
                isPersonal: true,
                button: new InlineQueryResultsButton('@'.Extrasense::profile()->username, startParameter: 'add'),
            );
        } else {
            $trackInfoCollection = $trackInfo;

            return SpiritBox::answerInlineQuery(
                inlineQueryId: Extrasense::update()->inlineQuery->id,
                results: [
                    ...array_map(fn (SoundcloudTrack|SoundcloudTrackInfoData $trackInfo) => self::resolveInlineQueryResultSoundcloud($trackInfo), $trackInfoCollection),
                ],
                cacheTime: 5,
                isPersonal: true,
                nextOffset: (string) ((int) Extrasense::update()->inlineQuery->offset + self::LIMIT),
                button: new InlineQueryResultsButton('@'.Extrasense::profile()->username, startParameter: 'add'),
            );
        }
    }

    public static function resolveInlineQueryResultSoundcloud(SoundcloudTrack|SoundcloudTrackInfoData $trackInfo): InlineQueryResult
    {
        if ($trackInfo instanceof SoundcloudTrack) {
            return new InlineQueryResultCachedAudio(
                id: 'soundcloud_tracks_'.$trackInfo->soundcloud_id,
                audioFileId: $trackInfo->file_id,
                caption: '@'.Extrasense::profile()->username,
                replyMarkup: (new TrackInlineKeyboardFactory)->make()->build([
                    'song_url' => $trackInfo->page_url,
                    'cover_url' => $trackInfo->thumbnails()->latest()->first()->url,
                ]),
            );
        } else {
            return new InlineQueryResultAudio(
                id: 'soundcloud_search_tracks_'.$trackInfo->soundcloud_id,
                audioUrl: route('soundcloud.stream', ['hash' => base64_encode($trackInfo->webpage_url)]).'.mp3',
                title: $trackInfo->track,
                performer: $trackInfo->uploader,
                audioDuration: 10,
                replyMarkup: (new TrackInlineKeyboardFactory)->make()->build([
                    'song_url' => $trackInfo->webpage_url,
                    'cover_url' => $trackInfo->thumbnails->toCollection()->last()->url,
                ]), // Telegram поймет, что это короткий фрагмент

                inputMessageContent: new InputTextMessageContent(
                    __('telegram.soundcloud.inline.chosen.initial')
                )
            );
        }
    }

    private static function handleYoutubeSearchQuery(TelegramServiceInterface $telegramService, YoutubeVideo|string $youtubeVideo): void
    {
        if (is_string($youtubeVideo)) {
            $youtubeVideoData = $telegramService->collectYoutubeMetadata($youtubeVideo);

            $youtubeVideo = YoutubeVideo::createFor($youtubeVideoData);
        }

        self::answerInlineQueryYoutube($youtubeVideo);
    }

    public static function answerInlineQueryYoutube(YoutubeVideo $youtubeVideo): bool|FailResult
    {
        return SpiritBox::answerInlineQuery(
            inlineQueryId: Extrasense::update()->inlineQuery->id,
            results: [
                ...$youtubeVideo->formats->map(fn (YoutubeVideoFormat $format) => self::resolveInlineQueryResultYoutube($format)),
            ],
            cacheTime: 5,
            isPersonal: true,
            button: new InlineQueryResultsButton('@'.Extrasense::profile()->username, startParameter: 'add'),
        );
    }

    public static function resolveInlineQueryResultYoutube(YoutubeVideoFormat $videoFormat): InlineQueryResult
    {
        if ($videoFormat->file_id) {
            if ($videoFormat->resolution === 'audio only') {
                return new InlineQueryResultCachedAudio(
                    id: 'youtube_link_format_'.$videoFormat->id,
                    audioFileId: $videoFormat->file_id,
                    caption: '@'.Extrasense::profile()->username,
                    replyMarkup: (new TrackInlineKeyboardFactory)->make()->build([
                        'song_url' => Extrasense::update()->inlineQuery->query,
                        'cover_url' => $videoFormat->video->thumbnail,
                    ]),
                );
            } else {
                return new InlineQueryResultCachedVideo(
                    id: 'youtube_link_format_'.$videoFormat->id,
                    videoFileId: $videoFormat->file_id,
                    title: $videoFormat->video->title,
                    caption: '@'.Extrasense::profile()->username,
                    replyMarkup: (new TrackInlineKeyboardFactory)->make()->build([
                        'song_url' => Extrasense::update()->inlineQuery->query,
                        'cover_url' => $videoFormat->video->thumbnail,
                    ]),
                );
            }
        } else {
            $resolution = $videoFormat->resolution;

            if ($resolution !== 'audio only') {
                $resolution = explode('x', $resolution)[0].'p';
            }

            $title = "{$resolution}";

            return new InlineQueryResultArticle(
                id: 'youtube_link_format_'.$videoFormat->id,
                title: $title,
                inputMessageContent: new InputTextMessageContent(
                    __('telegram.soundcloud.inline.chosen.initial')
                ),
                replyMarkup: (new TrackInlineKeyboardFactory)->make()->build([
                    'song_url' => $videoFormat->video->url(),
                    'cover_url' => $videoFormat->video->thumbnail,
                ]),
            );
        }
    }
}
