<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $soundcloud_track_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\SoundcloudTrack $soundcloudTrack
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSoundcloudTrack newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSoundcloudTrack newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSoundcloudTrack query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSoundcloudTrack whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSoundcloudTrack whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSoundcloudTrack whereSoundcloudTrackId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSoundcloudTrack whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSoundcloudTrack whereUserId($value)
 *
 * @mixin \Eloquent
 */
class UserSoundcloudTrack extends Model
{
    protected $table = 'user_soundcloud_tracks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'soundcloud_track_id',
    ];

    /**
     * Get the user that owns the track.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the soundcloud track.
     */
    public function soundcloudTrack(): BelongsTo
    {
        return $this->belongsTo(SoundcloudTrack::class, 'soundcloud_track_id');
    }
}
