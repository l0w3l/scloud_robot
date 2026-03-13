<?php

namespace App\Data\Soundcloud;

use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class TrackInfoData extends Data
{
    #[Computed]
    public int $soundcloud_id;

    public function __construct(
        int $id,
        public string $uploader,
        public int $uploader_id,
        public int $timestamp,
        public string $title,
        public string $track,
        public ?string $description,
        /** @var DataCollection<ThumbnailData> */
        #[DataCollectionOf(ThumbnailData::class)]
        public ?DataCollection $thumbnails,
        public float $duration,
        /** @var string[] */
        public ?array $genres,
        /** @var string[] */
        public ?array $tags,
        /** @var string[] */
        public ?array $artists,
        /** @var DataCollection<FormatData> */
        #[DataCollectionOf(FormatData::class)]
        public ?DataCollection $formats,
        public string $webpage_url = '',
        public string $page_url = '',
        public string $short_url = '',
    ) {
        $this->soundcloud_id = $id;
    }
}
