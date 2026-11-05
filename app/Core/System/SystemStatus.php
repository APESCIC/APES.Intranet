<?php

namespace App\Core\System;

use App\Core\System\Checks\AddonCheck;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs the addon checks and describes each addon for admins, without ever
 * exposing an error message, password, secret or token.
 */
final class SystemStatus
{
    public const SCHEDULER = 'scheduler';

    /** The scheduler runs the checks every 5 minutes; older than this means it has stopped. */
    public const SCHEDULER_STALE_MINUTES = 15;

    /**
     * @param  list<AddonCheck>  $checks
     */
    public function __construct(private readonly array $checks) {}

    /**
     * Runs every check and stores the results.
     *
     * @return array<string, SystemCheck> Check key => stored result.
     */
    public function run(): array
    {
        $results = [];

        foreach ($this->checks as $check) {
            $results[$check->key()] = SystemCheck::record($check->key(), $this->probe($check));
        }

        return $results;
    }

    public function recordSchedulerHeartbeat(): void
    {
        SystemCheck::record(self::SCHEDULER, SystemCheck::OK);
    }

    /**
     * @return list<array{key: string, label: string, present: ?bool, status: string, checkedAt: ?Carbon, details: array{host: ?string, port: ?string, database: ?string}, variables: array<string, bool>}>
     */
    public function summary(): array
    {
        $definitions = CloudronAddons::definitions();
        /** @var list<string> $setNames */
        $setNames = config('intranet.cloudron_variables', []);
        $stored = SystemCheck::query()->get()->keyBy('check');
        $rows = [];

        foreach ($this->checks as $check) {
            $definition = $definitions[$check->key()];
            $variables = [];
            foreach ($definition['variables'] as $name) {
                $variables[$name] = in_array($name, $setNames, true);
            }

            $result = $stored->get($check->key());
            $rows[] = [
                'key' => $check->key(),
                'label' => $definition['label'],
                'present' => $check->key() === 'oidc' ? $check->configured() : ($variables[$definition['variables'][0]] ?? false),
                'status' => $result === null ? 'not_checked' : $result->status,
                'checkedAt' => $result?->checked_at,
                'details' => $check->details(),
                'variables' => $variables,
            ];
        }

        $heartbeat = $stored->get(self::SCHEDULER);
        $rows[] = [
            'key' => self::SCHEDULER,
            'label' => 'Scheduler',
            'present' => null,
            'status' => match (true) {
                $heartbeat === null => 'not_run',
                $heartbeat->checked_at->lt(now()->subMinutes(self::SCHEDULER_STALE_MINUTES)) => SystemCheck::DOWN,
                default => SystemCheck::OK,
            },
            'checkedAt' => $heartbeat?->checked_at,
            'details' => ['host' => null, 'port' => null, 'database' => null],
            'variables' => [],
        ];

        return $rows;
    }

    private function probe(AddonCheck $check): string
    {
        if (! $check->configured()) {
            return SystemCheck::NOT_CONFIGURED;
        }

        try {
            $check->probe();

            return SystemCheck::OK;
        } catch (Throwable $exception) {
            // The exception class only: messages can carry hosts or connection strings.
            Log::warning('System check failed', ['check' => $check->key(), 'exception' => $exception::class]);

            return SystemCheck::DOWN;
        }
    }
}
