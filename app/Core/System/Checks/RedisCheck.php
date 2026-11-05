<?php

namespace App\Core\System\Checks;

use Illuminate\Support\Facades\Redis;

class RedisCheck implements AddonCheck
{
    public function key(): string
    {
        return 'redis';
    }

    public function configured(): bool
    {
        return filled(config('database.redis.default.host'));
    }

    public function probe(): void
    {
        Redis::connection()->ping();
    }

    public function details(): array
    {
        return [
            'host' => (string) config('database.redis.default.host'),
            'port' => (string) config('database.redis.default.port'),
            'database' => null,
        ];
    }
}
