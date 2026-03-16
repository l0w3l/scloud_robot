<?php

namespace App\Data\YtDlp\Youtube;

use Spatie\LaravelData\Data;

class YoutubeThumdnailData extends Data
{
    public function __construct(
        public string $url,
        public ?int $height = null,
        public ?int $width = null,
    ) {}
}
