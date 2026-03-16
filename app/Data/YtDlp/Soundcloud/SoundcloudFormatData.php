<?php

namespace App\Data\YtDlp\Soundcloud;

use Spatie\LaravelData\Data;

class SoundcloudFormatData extends Data
{
    public function __construct(
        public string $format_id,
        public string $url,
        public string $protocol,
        public int $quality,
        public int $filesize_approx,
        public string $format,
    ) {}
}
