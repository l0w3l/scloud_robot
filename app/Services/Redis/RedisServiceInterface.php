<?php

declare(strict_types=1);

namespace App\Services\Redis;

use Lowel\LaravelServiceMaker\Services\ServiceInterface;

interface RedisServiceInterface extends ServiceInterface
{
    public function simpleThrottle(string $name, int $seconds = 5, int $allow = 1): callable;
}
