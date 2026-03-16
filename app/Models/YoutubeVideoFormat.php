<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Lowel\Telepath\Facades\Extrasense;
use Lowel\Telepath\Facades\SpiritBox;
use Phptg\BotApi\Type\Message;
use RuntimeException;

/**
 * @property int $id
 * @property int $youtube_video_id
 * @property string $format_id
 * @property string|null $file_id
 * @property int|null $filesize
 * @property string|null $resolution
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read YoutubeVideo $video
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideoFormat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideoFormat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideoFormat query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideoFormat whereAudioChannels($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideoFormat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideoFormat whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideoFormat whereFilesize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideoFormat whereFormatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideoFormat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideoFormat whereResolution($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideoFormat whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideoFormat whereYoutubeVideoId($value)
 *
 * @property int|null $width
 * @property int|null $height
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideoFormat whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideoFormat whereWidth($value)
 *
 * @mixin \Eloquent
 */
class YoutubeVideoFormat extends Model
{
    protected $fillable = [
        'youtube_video_id',
        'format_id',
        'file_id',
        'filesize',
        'width',
        'height',
        'resolution',
    ];

    /**
     * @return BelongsTo<YoutubeVideo, $this>
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(YoutubeVideo::class, 'youtube_video_id');
    }

    public function send(): Message
    {
        if ($this->file_id !== null) {
            if ($this->resolution === 'audio only') {
                return SpiritBox::sendAudio($this->file_id, caption: '@'.Extrasense::profile()->username);
            } else {
                return SpiritBox::sendVideo($this->file_id, caption: '@'.Extrasense::profile()->username);
            }
        }

        throw new RuntimeException("No file id specified for current format ({$this->id})");
    }

    public function path(): string
    {
        if ($this->resolution === 'audio only') {
            $path = Storage::disk('public')->path("youtube/{$this->video->youtube_id}/{$this->format_id}/file.mp3");
        } else {
            $path = Storage::disk('public')->path("youtube/{$this->video->youtube_id}/{$this->format_id}/file.mp4");
        }

        if (! file_exists($path)) {
            throw new RuntimeException("File for format {$this->id} was not founded ({$path})");
        }

        return $path;
    }
}
