<?php

declare(strict_types=1);

namespace App\Telegram\Handlers\Inline;

use App\Data\Soundcloud\TrackInfoData;
use App\Exceptions\TooLargeFileForDownloadException;
use App\Models\SoundcloudTrack;
use App\Models\User;
use App\Services\Telegram\TelegramServiceInterface;
use App\Telegram\Keyboards\Inline\Soundcloud\Track\TrackInlineKeyboardFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Lowel\Telepath\Core\Router\Handler\AbstractTelegramHandler;
use Lowel\Telepath\Facades\Extrasense;
use Lowel\Telepath\Facades\SpiritBox;
use Phptg\BotApi\FailResult;
use Phptg\BotApi\Type\Inline\InlineQueryResult;
use Phptg\BotApi\Type\Inline\InlineQueryResultAudio;
use Phptg\BotApi\Type\Inline\InlineQueryResultCachedAudio;
use Phptg\BotApi\Type\Inline\InlineQueryResultsButton;
use Phptg\BotApi\Type\Inline\InputTextMessageContent;

class SearchTracksHandler extends AbstractTelegramHandler
{
    const LIMIT = 20;

    public function handler(): callable
    {
        return static function (TelegramServiceInterface $telegramService) {
            $inlineQuery = Extrasense::update()->inlineQuery;

            $existedTrack = $telegramService->resolveSoundcloudLink($inlineQuery->query);

            if ($existedTrack instanceof SoundcloudTrack) {
                return self::answerInlineQuery($existedTrack);
            } elseif (is_string($trackUrl = $existedTrack)) {
                try {
                    $metadata = $telegramService->collectSoundcloudMetadata($trackUrl);
                } catch (TooLargeFileForDownloadException) {
                    return;
                }

                if ($existedTrack = SoundcloudTrack::whereSoundcloudId($metadata->soundcloud_id)->first()) {
                    return self::answerInlineQuery($existedTrack);
                }

                DB::transaction(function () use ($metadata, $telegramService, $trackUrl): void {
                    $track = $telegramService->downloadSoundcloudTrack($trackUrl, $metadata);

                    self::answerInlineQuery($track);
                });
            } else {
                $rawText = strtolower($inlineQuery->query);

                if (empty($rawText)) {
                    /** @var User */
                    $user = Auth::guard('telegram')->user();

                    $tracksByUser = $user->soundcloudTracks()
                        ->offset((int) $inlineQuery->offset)
                        ->limit(self::LIMIT)
                        ->latest('user_soundcloud_tracks.updated_at')
                        ->get();

                    return self::answerInlineQuery($tracksByUser->all());
                } else {
                    $tracksFromSearch = $telegramService->smartSoundcloudSearch($rawText, (int) $inlineQuery->offset, self::LIMIT);

                    return self::answerInlineQuery($tracksFromSearch);
                }
            }
        };
    }

    public static function answerInlineQuery(array|SoundcloudTrack|TrackInfoData $trackInfo): bool|FailResult
    {
        if ($trackInfo instanceof SoundcloudTrack || $trackInfo instanceof TrackInfoData) {
            return SpiritBox::answerInlineQuery(
                inlineQueryId: Extrasense::update()->inlineQuery->id,
                results: [
                    self::resolveInlineQueryResult($trackInfo),
                ],
                isPersonal: true,
                cacheTime: 300,
                button: new InlineQueryResultsButton('@'.Extrasense::profile()->username, startParameter: 'add'),
            );
        } else {
            $trackInfoCollection = $trackInfo;

            return SpiritBox::answerInlineQuery(
                inlineQueryId: Extrasense::update()->inlineQuery->id,
                results: [
                    ...array_map(fn (SoundcloudTrack|TrackInfoData $trackInfo) => self::resolveInlineQueryResult($trackInfo), $trackInfoCollection),
                ],
                cacheTime: 5,
                isPersonal: true,
                nextOffset: (string) ((int) Extrasense::update()->inlineQuery->offset + self::LIMIT),
                button: new InlineQueryResultsButton('@'.Extrasense::profile()->username, startParameter: 'add'),
            );
        }
    }

    public static function resolveInlineQueryResult(SoundcloudTrack|TrackInfoData $trackInfo): InlineQueryResult
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
                replyMarkup: (new TrackInlineKeyboardFactory)->make()->build([
                    'song_url' => $trackInfo->webpage_url,
                    'cover_url' => $trackInfo->thumbnails->toCollection()->last()->url,
                ]),
                audioDuration: 10, // Telegram поймет, что это короткий фрагмент

                inputMessageContent: new InputTextMessageContent(
                    __('telegram.soundcloud.inline.chosen.initial')
                )
            );
        }
    }
}
