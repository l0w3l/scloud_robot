<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 *
 * @property int $telegram_id
 * @property string $first_name
 * @property string|null $last_name
 * @property string|null $username
 * @property int $is_bot
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsBot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTelegramId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 *
 * @property-read Collection<int, SoundcloudTrack> $soundcloudTracks
 * @property-read int|null $soundcloud_tracks_count
 * @property string $language_code
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLanguageCode($value)
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'telegram_id',
        'username',
        'is_bot',
        'language_code',
        'first_name',
        'last_name',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get all soundcloud tracks through the user_soundcloud_tracks table.
     *
     * @return HasManyThrough<SoundcloudTrack, UserSoundcloudTrack, $this>
     */
    public function soundcloudTracks(): HasManyThrough
    {
        return $this->hasManyThrough(
            SoundcloudTrack::class,
            UserSoundcloudTrack::class,
            'user_id',           // FK на users в промежуточной таблице
            'id',                // PK в soundcloud_tracks
            'id',                // PK в users
            'soundcloud_track_id' // FK на soundcloud_tracks в промежуточной таблице
        );
    }

    /**
     * @return Collection<int, SoundcloudTrack>
     */
    public function searchTracks(string $rawText, int $offset, int $limit): Collection
    {
        return $this->soundcloudTracks()
            ->whereRaw('LOWER(title) LIKE ?', ["%{$rawText}%"])
            ->orWhereRaw('LOWER(track) LIKE ?', ["%{$rawText}%"])
            ->orWhereRaw('LOWER(artists) LIKE ?', ["%{$rawText}%"])
            ->orWhereRaw('LOWER(uploader) LIKE ?', ["%{$rawText}%"])
            ->latest()
            ->offset($offset)
            ->limit($limit)
            ->get();
    }
}
