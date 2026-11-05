<?php

namespace App\Core\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The latest result of one system check. Holds a status word and times only,
 * never an error message, host or credential.
 *
 * @property string $check
 * @property string $status
 * @property Carbon $checked_at
 * @property int $consecutive_failures
 * @property Carbon|null $alerted_at
 */
class SystemCheck extends Model
{
    public const OK = 'ok';

    public const DOWN = 'down';

    public const NOT_CONFIGURED = 'not_configured';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
            'alerted_at' => 'datetime',
            'consecutive_failures' => 'integer',
        ];
    }

    public static function record(string $check, string $status): self
    {
        $row = self::query()->firstOrNew(['check' => $check]);
        $row->status = $status;
        $row->checked_at = now();
        $row->consecutive_failures = $status === self::DOWN ? $row->consecutive_failures + 1 : 0;

        if ($status !== self::DOWN) {
            $row->alerted_at = null;
        }

        $row->save();

        return $row;
    }
}
