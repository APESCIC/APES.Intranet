<?php

namespace App\Core\Changelog;

use App\Core\Access\RoleName;
use App\Models\User;

/**
 * Who may read which release note. Members see published notes tagged for
 * their account type; only admins see drafts and internal notes.
 */
class ReleasePolicy
{
    /** Account type role => audience tag used in the release-note files. */
    public const AUDIENCE_BY_ACCOUNT_TYPE = [
        'staff' => 'staff',
        'volunteer' => 'volunteers',
        'student' => 'students',
    ];

    public function view(User $user, Release $release): bool
    {
        if ($this->viewDrafts($user)) {
            return true;
        }

        return $release->is_published
            && array_intersect($release->audiences, self::audiencesFor($user)) !== [];
    }

    /**
     * Drafts and internal notes.
     */
    public function viewDrafts(User $user): bool
    {
        return $user->hasRole(RoleName::Admin->value);
    }

    /**
     * @return list<string>
     */
    public static function audiencesFor(User $user): array
    {
        return array_values(array_filter(
            self::AUDIENCE_BY_ACCOUNT_TYPE,
            fn (string $audience, string $role) => $user->hasRole($role),
            ARRAY_FILTER_USE_BOTH,
        ));
    }
}
