<?php

declare(strict_types=1);

namespace App\Telegram\Handlers\Inline;

use App\Models\SoundcloudTrack;
use App\Models\User;
use App\Models\UserSoundcloudTrack;
use App\Services\Telegram\TelegramServiceInterface;
use Illuminate\Support\Facades\Auth;
use Lowel\Telepath\Core\Router\Handler\AbstractTelegramHandler;
use Lowel\Telepath\Facades\Extrasense;

class ChosenTrackResultHandler extends AbstractTelegramHandler
{
    const TAG = 'soundcloud_tracks_';

    public function pattern(): ?string
    {
        return self::TAG.'.+';
    }

    public function handler(): callable
    {
        return static function (TelegramServiceInterface $telegramService) {
            $chosenResult = Extrasense::update()->chosenInlineResult;
            $id = $chosenResult->resultId;

            $trackId = str_replace(self::TAG, '', $id);

            $track = SoundcloudTrack::whereSoundcloudId($trackId)
                ->first();
            /** @var User */
            $user = Auth::guard('telegram')->user();

            if ($user->soundcloudTracks()->where('soundcloud_tracks.id', $track->id)->doesntExist()) {
                $track->users()->attach($user);
            }

            UserSoundcloudTrack::where('user_id', $user->id)
                ->where('soundcloud_track_id', $track->id)
                ->touch();
        };
    }
}
