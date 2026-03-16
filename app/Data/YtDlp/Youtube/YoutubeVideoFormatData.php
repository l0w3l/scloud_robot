<?php

namespace App\Data\YtDlp\Youtube;

use Spatie\LaravelData\Data;

class YoutubeVideoFormatData extends Data
{
    public function __construct(
        public string $format_id,
        public ?int $filesize,
        public ?int $width,
        public ?int $height,
        public ?string $file_id,
        public ?string $resolution,
    ) {}
}
