<?php

declare(strict_types=1);

namespace App\Services\Redis;

use Illuminate\Support\Facades\Redis;
use Lowel\LaravelServiceMaker\Services\AbstractService;

class RedisService extends AbstractService implements RedisServiceInterface
{
    public function simpleThrottle(string $name, int $seconds = 5, int $allow = 1): callable
    {
        return fn (callable $callable) => Redis::throttle($name)
            ->block(0)
            ->allow($allow)
            ->every($seconds)
            ->then($callable, fn () => null);
    }
}
