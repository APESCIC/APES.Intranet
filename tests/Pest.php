<?php

use App\Core\Auth\Contracts\DirectoryGroups;
use App\Core\Auth\Contracts\IdentityProvider;
use App\Core\Auth\OidcIdentity;
use App\Core\Navigation\Menu;
use App\Core\Navigation\MenuMode;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\Fakes\FakeDirectoryGroups;
use Tests\Fakes\FakeIdentityProvider;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

/**
 * Swap Cloudron OIDC and LDAP for fakes, available as $test->idp and $test->directory.
 */
/**
 * @return list<array{key: string, heading: string, items: list<string>}>
 */
function menuSnapshotFor(User $user, MenuMode $mode = MenuMode::Staff): array
{
    return collect(Menu::for($user, $mode))
        ->map(fn (NavGroup $group) => [
            'key' => $group->key,
            'heading' => $group->heading,
            'items' => array_map(fn (NavItem $item) => $item->key, $group->items),
        ])
        ->values()
        ->all();
}

function fakeCloudron(TestCase $test): void
{
    $test->idp = new FakeIdentityProvider;
    $test->directory = new FakeDirectoryGroups;
    app()->instance(IdentityProvider::class, $test->idp);
    app()->instance(DirectoryGroups::class, $test->directory);
}

/**
 * @param  list<string>  $groups
 */
function signInWithCloudron(TestCase $test, array $groups, string $sub = 'sub-alex', string $email = 'alex@example.test'): TestResponse
{
    $test->idp->identity = new OidcIdentity($sub, $email, 'Alex Example');
    $test->directory->groups = $groups;

    return $test->get(route('auth.callback'));
}
