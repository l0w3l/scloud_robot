<?php

namespace App\Models;

use App\Data\YtDlp\Youtube\YoutubeThumdnailData;
use App\Data\YtDlp\Youtube\YoutubeVideoData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $youtube_id
 * @property string $title
 * @property string|null $fulltitle
 * @property string|null $thumbnail
 * @property int|null $duration
 * @property array<array-key, mixed>|null $categories
 * @property array<array-key, mixed>|null $tags
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, YoutubeVideoFormat> $formats
 * @property-read int|null $formats_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideo whereCategories($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideo whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideo whereFulltitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideo whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideo whereThumbnail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideo whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|YoutubeVideo whereYoutubeId($value)
 *
 * @mixin \Eloquent
 */
class YoutubeVideo extends Model
{
    protected $fillable = [
        'youtube_id',
        'title',
        'fulltitle',
        'thumbnail',
        'duration',
        'categories',
        'tags',
    ];

    protected $casts = [
        'categories' => 'array',
        'tags' => 'array',
    ];

    public function formats(): HasMany
    {
        return $this->hasMany(YoutubeVideoFormat::class)->orderBy('id');
    }

    public static function createFor(YoutubeVideoData $youtubeVideoData): self
    {
        return DB::transaction(function () use ($youtubeVideoData) {
            $thumnail = collect($youtubeVideoData->thumbnails)->filter(
                fn (YoutubeThumdnailData $thumbnail) => ! ($thumbnail->width > 320 || $thumbnail->width === null || $thumbnail->height > 320 || $thumbnail->height === null)
            )->last();

            $yourubeVideo = self::create([
                ...$youtubeVideoData->toArray(),
                'thumbnail' => $thumnail->url ?? $youtubeVideoData->thumbnail,
            ]);

            $yourubeVideo->formats()->createMany($youtubeVideoData->toArray()['formats']);

            return $yourubeVideo;
        });
    }

    public function url(): string
    {
        return 'https://youtu.be/'.$this->youtube_id;
    }
}
