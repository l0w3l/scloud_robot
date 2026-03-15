<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $format_id
 * @property string $url
 * @property string $protocol
 * @property int $quality
 * @property int $filesize_approx
 * @property string $format
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $track_id
 * @property-read SoundcloudTrack $track
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudFormat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudFormat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudFormat query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudFormat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudFormat whereFilesizeApprox($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudFormat whereFormat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudFormat whereFormatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudFormat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudFormat whereProtocol($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudFormat whereQuality($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudFormat whereTrackId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudFormat whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudFormat whereUrl($value)
 *
 * @mixin \Eloquent
 */
class SoundcloudFormat extends Model
{
    protected $table = 'soundcloud_formats';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'track_id',
        'format_id',
        'url',
        'protocol',
        'quality',
        'filesize_approx',
        'format',
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
            'quality' => 'integer',
            'filesize_approx' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the track that owns the format.
     */
    public function track(): BelongsTo
    {
        return $this->belongsTo(SoundcloudTrack::class, 'track_id');
    }
}
