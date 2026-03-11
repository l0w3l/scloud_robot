<?php

namespace App\Data\Soundcloud;

use Spatie\LaravelData\Data;

class ThumbnailData extends Data
{
    public function __construct(
        public string $url,
        public ?int $width = null,
        public ?int $height = null,
        public ?string $resolution = null,
    ) {}
}
