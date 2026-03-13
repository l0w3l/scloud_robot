<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $soundcloud_id
 * @property string $webpage_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudSearchTrack newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudSearchTrack newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudSearchTrack query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudSearchTrack whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudSearchTrack whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudSearchTrack whereSoundcloudId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudSearchTrack whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudSearchTrack whereWebpageUrl($value)
 *
 * @property-read SoundcloudTrack|null $soundcloudTrack
 *
 * @mixin \Eloquent
 */
class SoundcloudSearchTrack extends Model
{
    protected $fillable = [
        'soundcloud_id',
        'webpage_url',
    ];

    public function soundcloudTrack(): BelongsTo
    {
        return $this->belongsTo(SoundcloudTrack::class, 'soundcloud_id', 'soundcloud_id');
    }
}
