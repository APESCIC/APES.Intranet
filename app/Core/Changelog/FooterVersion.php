<?php

namespace App\Core\Changelog;

use App\Core\Version\AppVersion;
use App\Models\User;
use Illuminate\Database\QueryException;

/**
 * The footer version pill: the deployed version (never the newest tag),
 * linked to its change log entry when the viewer may read it, otherwise to
 * the hub, so the pill never leads to a 404.
 */
class FooterVersion
{
    public function __construct(private readonly AppVersion $version) {}

    /**
     * The accessible name is hiddenBefore + label + hiddenAfter, so it always
     * contains the visible text (WCAG 2.5.3 Label in Name).
     *
     * @return array{label: string, url: string, hiddenBefore: string, hiddenAfter: string}|null
     */
    public function for(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $current = $this->version->current();
        if ($current === AppVersion::DEV) {
            return [
                'label' => AppVersion::DEV,
                'url' => route('change-log.index'),
                'hiddenBefore' => 'View the intranet change log for the ',
                'hiddenAfter' => ' version',
            ];
        }

        return [
            'label' => $current,
            'url' => $this->canRead($user, $current) ? route('change-log.show', $current) : route('change-log.index'),
            'hiddenBefore' => 'View the intranet change log for version ',
            'hiddenAfter' => '',
        ];
    }

    private function canRead(User $user, string $version): bool
    {
        try {
            $release = Release::query()->where('version', $version)->first();
        } catch (QueryException $exception) {
            report($exception);

            return false;
        }

        return $release !== null && $user->can('view', $release);
    }
}
