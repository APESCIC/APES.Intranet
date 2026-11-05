<?php

namespace App\Core\System\Checks;

use Illuminate\Support\Facades\DB;

class MysqlCheck implements AddonCheck
{
    public function key(): string
    {
        return 'mysql';
    }

    public function configured(): bool
    {
        return true;
    }

    public function probe(): void
    {
        DB::connection()->select('select 1');
    }

    public function details(): array
    {
        $connection = (array) config('database.connections.'.config('database.default'), []);

        return [
            'host' => isset($connection['host']) ? (string) $connection['host'] : null,
            'port' => isset($connection['port']) ? (string) $connection['port'] : null,
            'database' => isset($connection['database']) ? (string) $connection['database'] : null,
        ];
    }
}
