<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $url
 * @property int $width
 * @property int $height
 * @property string $resolution
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $track_id
 * @property-read \App\Models\SoundcloudTrack $track
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudThumbnail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudThumbnail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudThumbnail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudThumbnail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudThumbnail whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudThumbnail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudThumbnail whereResolution($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudThumbnail whereTrackId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudThumbnail whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudThumbnail whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudThumbnail whereWidth($value)
 *
 * @mixin \Eloquent
 */
class SoundcloudThumbnail extends Model
{
    protected $table = 'soundcloud_thumbnails';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'track_id',
        'id',
        'url',
        'width',
        'height',
        'resolution',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'track_id' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the track that owns the thumbnail.
     */
    public function track(): BelongsTo
    {
        return $this->belongsTo(SoundcloudTrack::class, 'track_id');
    }
}
