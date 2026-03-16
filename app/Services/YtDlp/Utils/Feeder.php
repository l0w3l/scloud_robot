<?php

declare(strict_types=1);

namespace App\Services\YtDlp\Utils;

use App\Data\YtDlp\EventData;

final class Feeder
{
    private string $buffer = '';

    public function __construct(
        private readonly OutputParser $parser,
    ) {}

    /**
     * @return array<EventData>
     */
    public function feed(string $chunk, string $stream): array
    {
        // нормализуем переносы, потому что yt-dlp может писать \r\n или просто \n
        $this->buffer .= str_replace("\r\n", "\n", $chunk);

        $events = [];

        while (($pos = strpos($this->buffer, "\n")) !== false) {
            $line = substr($this->buffer, 0, $pos);
            $this->buffer = substr($this->buffer, $pos + 1);

            $evt = $this->parser->parseLine($line, $stream);
            if ($evt) {
                $events[] = $evt;
            }
        }

        return $events;
    }

    public function flush(string $stream = 'stdout'): ?EventData
    {
        $tail = trim($this->buffer);
        $this->buffer = '';

        return $tail !== '' ? $this->parser->parseLine($tail, $stream) : null;
    }
}
