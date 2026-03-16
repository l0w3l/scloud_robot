<?php

namespace App\Data\YtDlp\Youtube;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class YoutubeVideoData extends Data
{
    public function __construct(
        #[MapInputName('id')]
        public string $youtube_id,
        public string $title,
        public ?string $fulltitle,
        public ?string $thumbnail,
        public ?int $duration,
        /** @var string[] */
        public array $categories,
        /** @var string[] */
        public array $tags,
        /** @var YoutubeVideoFormatData[] */
        public array $formats,
        /** @var YoutubeThumdnailData[] */
        public array $thumbnails,
    ) {}
}
