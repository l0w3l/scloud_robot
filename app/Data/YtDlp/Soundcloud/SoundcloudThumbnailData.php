<?php

namespace App\Data\YtDlp\Soundcloud;

use Spatie\LaravelData\Data;

class SoundcloudThumbnailData extends Data
{
    public function __construct(
        public string $url,
        public ?int $width = null,
        public ?int $height = null,
        public ?string $resolution = null,
    ) {}
}
