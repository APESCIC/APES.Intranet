<?php

namespace App\Core\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Checks the app, database and Redis. Results say only "ok" or "fail" so the
 * public /healthz endpoint never reveals hosts, errors or credentials.
 */
class HealthCheck
{
    /**
     * @return array{app: string, database: string, redis: string}
     */
    public function run(): array
    {
        return [
            'app' => 'ok',
            'database' => $this->attempt(fn () => DB::connection()->select('select 1')),
            'redis' => $this->attempt(fn () => Redis::connection()->ping()),
        ];
    }

    private function attempt(callable $check): string
    {
        try {
            $check();

            return 'ok';
        } catch (Throwable $exception) {
            report($exception);

            return 'fail';
        }
    }
}
