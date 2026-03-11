<?php

namespace App\Models;

use App\Data\Soundcloud\TrackInfoData;
use App\Telegram\Keyboards\Inline\Soundcloud\Track\TrackInlineKeyboardFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lowel\Telepath\Facades\Extrasense;
use Lowel\Telepath\Facades\SpiritBox;
use Phptg\BotApi\Type\InputFile;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\ReplyParameters;

/**
 * @property int $id
 * @property int $soundcloud_id
 * @property string $uploader
 * @property int $uploader_id
 * @property \Illuminate\Support\Carbon $timestamp
 * @property string $title
 * @property string $track
 * @property string $description
 * @property float $duration
 * @property string $page_url
 * @property array<array-key, mixed>|null $genres
 * @property array<array-key, mixed>|null $tags
 * @property array<array-key, mixed>|null $artists
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SoundcloudFormat> $formats
 * @property-read int|null $formats_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SoundcloudThumbnail> $thumbnails
 * @property-read int|null $thumbnails_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack whereArtists($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack whereGenres($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack wherePageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack whereSoundcloudId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack whereTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack whereTrack($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack whereUploader($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack whereUploaderId($value)
 *
 * @property string|null $file_id
 * @property string|null $short_url
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SoundcloudTrack whereShortUrl($value)
 *
 * @mixin \Eloquent
 */
class SoundcloudTrack extends Model
{
    protected $table = 'soundcloud_tracks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'soundcloud_id',
        'file_id',
        'uploader',
        'uploader_id',
        'timestamp',
        'title',
        'track',
        'description',
        'duration',
        'page_url',
        'short_url',
        'genres',
        'tags',
        'artists',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'soundcloud_id' => 'integer',
            'uploader_id' => 'integer',
            'timestamp' => 'datetime',
            'duration' => 'float',
            'genres' => 'array',
            'tags' => 'array',
            'artists' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the thumbnails for the track.
     */
    public function thumbnails(): HasMany
    {
        return $this->hasMany(SoundcloudThumbnail::class, 'track_id');
    }

    /**
     * Get the formats for the track.
     */
    public function formats(): HasMany
    {
        return $this->hasMany(SoundcloudFormat::class, 'track_id');
    }

    public static function createFrom(string $filePath, TrackInfoData $metadata): self
    {
        $thumbnail = $metadata->thumbnails->toCollection()->where('width', 300)->where('height', 300)->first();
        $cover = $metadata->thumbnails->toCollection()->last();

        $message = SpiritBox::sendAudio(
            InputFile::fromLocalFile($filePath),
            config('services.telegram.storage_chat_id'),
            thumbnail: InputFile::fromLocalFile($thumbnail->url),
            caption: '@'.Extrasense::profile()->username,
            replyMarkup: (new TrackInlineKeyboardFactory)->make()->build([
                'soundcloudTrack' => $metadata,
                'cover' => $cover,
            ])
        );

        $soundcloudTrack = self::create([
            ...$metadata->toArray(),
            'file_id' => $message->audio->fileId,
        ]);

        $soundcloudTrack->thumbnails()->createMany($metadata->thumbnails->toArray());
        $soundcloudTrack->formats()->createMany($metadata->formats->toArray());

        return $soundcloudTrack;
    }

    public function send(): Message
    {
        $thumbnail = $this->thumbnails()->where('width', 300)->where('height', 300)->first();
        $cover = $this->thumbnails()->latest()->first();
        $context = Extrasense::message();

        return SpiritBox::sendAudio(
            $this->file_id,
            caption: '@'.Extrasense::profile()->username,
            replyParameters: new ReplyParameters($context->messageId, $context->chat->id),
            replyMarkup: (new TrackInlineKeyboardFactory)->make()->build([
                'soundcloudTrack' => $this,
                'cover' => $cover,
            ])
        );
    }
}
