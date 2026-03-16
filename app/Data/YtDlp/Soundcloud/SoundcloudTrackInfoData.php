<?php

namespace App\Data\YtDlp\Soundcloud;

use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class SoundcloudTrackInfoData extends Data
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
        /** @var DataCollection<SoundcloudThumbnailData> */
        #[DataCollectionOf(SoundcloudThumbnailData::class)]
        public ?DataCollection $thumbnails,
        public float $duration,
        /** @var string[] */
        public ?array $genres,
        /** @var string[] */
        public ?array $tags,
        /** @var string[] */
        public ?array $artists,
        /** @var DataCollection<SoundcloudFormatData> */
        #[DataCollectionOf(SoundcloudFormatData::class)]
        public ?DataCollection $formats,
        public string $webpage_url = '',
        public string $page_url = '',
        public string $short_url = '',
    ) {
        $this->soundcloud_id = $id;
    }
}
