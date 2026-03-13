<?php

namespace App\Data\Soundcloud;

use Spatie\LaravelData\Data;

class EventData extends Data
{
    public function __construct(
        public string $type,            // download_progress | destination | log | raw | ...
        public ?string $service = null,  // download | soundcloud | hlsnative | ...
        public ?string $message = null,
        public array $meta = [],
        public ?string $raw = null,
        public ?string $stream = null,  // stdout|stderr
        public ?string $ts = null,      // ISO8601
    ) {}
}
