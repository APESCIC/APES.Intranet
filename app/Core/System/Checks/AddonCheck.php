<?php

namespace App\Core\System\Checks;

/**
 * One Cloudron addon the intranet depends on.
 */
interface AddonCheck
{
    /**
     * Matches a key in CloudronAddons::definitions().
     */
    public function key(): string;

    /**
     * Whether the app has the settings to reach it at all.
     */
    public function configured(): bool;

    /**
     * Throws if the addon cannot be reached. Must not send mail or change data.
     */
    public function probe(): void;

    /**
     * Non-secret connection details to show admins.
     *
     * @return array{host: ?string, port: ?string, database: ?string}
     */
    public function details(): array;
}
