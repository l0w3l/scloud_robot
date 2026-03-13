<?php

declare(strict_types=1);

namespace App\Services\YtDlp\Soundcloud;

use App\Data\Soundcloud\EventData;

final class OutputParser
{
    private const JSON_PREFIX = 'DLJSON:';

    public function parseLine(string $line, string $stream): ?EventData
    {
        $line = trim($line);
        if ($line === '') {
            return null;
        }

        $ts = now()->toISOString();

        // 1) JSON progress
        $p = strpos($line, self::JSON_PREFIX);
        if ($p !== false) {
            $json = trim(substr($line, $p + strlen(self::JSON_PREFIX)));
            $data = json_decode($json, true);

            if (is_array($data)) {
                $percentStr = $data['percent'] ?? null;

                return new EventData(
                    type: 'download_progress',
                    service: 'download',
                    message: 'Progress',
                    meta: [
                        'status' => $data['status'] ?? null,
                        'percent_str' => $percentStr,
                        'percent' => $this->percentToFloat($percentStr),

                        'downloaded' => $this->numOrNull($data['downloaded'] ?? null),
                        'total' => $this->numOrNull($data['total'] ?? null),
                        'total_est' => $this->numOrNull($data['total_est'] ?? null),
                        'speed' => $this->numOrNull($data['speed'] ?? null),
                        'eta' => $this->numOrNull($data['eta'] ?? null),
                        'elapsed' => $this->numOrNull($data['elapsed'] ?? null),
                    ],
                    raw: $line,
                    stream: $stream,
                    ts: $ts,
                );
            }

            // если JSON не распарсился — не падаем
            return new EventData('raw', null, $line, [], $line, $stream, $ts);
        }

        // 2) прочие события (добавляй по мере необходимости)
        if (preg_match('~^\[(?<svc>download)\]\s+Destination:\s+(?<path>.+)$~u', $line, $m)) {
            return new EventData('destination', $m['svc'], 'Destination', ['path' => $m['path']], $line, $stream, $ts);
        }

        if (preg_match('~^\[(?<svc>[^\]]+)\]\s+(?<msg>.+)$~u', $line, $m)) {
            return new EventData('log', $m['svc'], $m['msg'], [], $line, $stream, $ts);
        }

        return new EventData('raw', null, $line, [], $line, $stream, $ts);
    }

    private function percentToFloat(mixed $v): ?float
    {
        if (! is_string($v)) {
            return null;
        }
        $s = trim($v);
        if ($s === '' || strtoupper($s) === 'NA') {
            return null;
        }
        $s = rtrim($s, "% \t");

        return is_numeric($s) ? (float) $s : null;
    }

    private function numOrNull(mixed $v): ?float
    {
        if ($v === null) {
            return null;
        }
        if (is_int($v) || is_float($v)) {
            return (float) $v;
        }

        if (is_string($v)) {
            $s = trim($v);
            if ($s === '' || strtoupper($s) === 'NA') {
                return null;
            }

            return is_numeric($s) ? (float) $s : null;
        }

        return null;
    }
}
