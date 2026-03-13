<?php

declare(strict_types=1);

namespace App\Services\Redis;

use Lowel\LaravelServiceMaker\Services\ServiceFactoryInterface;

class RedisServiceFactory implements ServiceFactoryInterface
{
    public function get(array $params = []): RedisServiceInterface
    {
        return new RedisService;
    }
}
