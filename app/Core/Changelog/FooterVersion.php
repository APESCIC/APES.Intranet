<?php

namespace App\Core\Changelog;

use App\Core\Version\AppVersion;
use App\Models\User;
use Illuminate\Database\QueryException;

/**
 * The footer version pill: a real version label on every page (including
 * signed-out), linked to its change log entry when the viewer may read it.
 */
class FooterVersion
{
    public function __construct(private readonly AppVersion $version) {}

    /**
     * The accessible name is hiddenBefore + label + hiddenAfter, so it always
     * contains the visible text (WCAG 2.5.3 Label in Name).
     *
     * @return array{label: string, url: string|null, hiddenBefore: string, hiddenAfter: string}
     */
    public function for(?User $user): array
    {
        $label = $this->version->label();
        $base = $this->version->baseVersion();

        if ($user === null) {
            return [
                'label' => $label,
                'url' => null,
                'hiddenBefore' => 'Intranet version ',
                'hiddenAfter' => '',
            ];
        }

        if ($label === AppVersion::DEV || $base === null) {
            return [
                'label' => $label,
                'url' => route('change-log.index'),
                'hiddenBefore' => 'View the intranet change log for the ',
                'hiddenAfter' => ' version',
            ];
        }

        return [
            'label' => $label,
            'url' => $this->canRead($user, $base) ? route('change-log.show', $base) : route('change-log.index'),
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
