<?php

declare(strict_types=1);

namespace App\Helpers;

class Throttle
{
    protected static array $lastExecution = [];

    public function call(callable $callback, int $seconds = 1, ?string $key = null)
    {
        $key ??= $this->resolveKey($callback);

        $now = microtime(true);

        if (! isset(self::$lastExecution[$key])) {
            self::$lastExecution[$key] = 0;
        }

        if (($now - self::$lastExecution[$key]) < $seconds) {
            return null;
        }

        self::$lastExecution[$key] = $now;

        return $callback();
    }

    protected function resolveKey(callable $callback): string
    {
        if (is_array($callback)) {
            return spl_object_hash($callback[0]).'::'.$callback[1];
        }

        if ($callback instanceof \Closure) {
            return spl_object_hash($callback);
        }

        return md5(serialize($callback));
    }
}
