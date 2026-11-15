<?php

use App\Core\Access\RoleName;
use Database\Seeders\CommunicationsBootstrapSeeder;
use Database\Seeders\Microsoft365BootstrapSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Communications\CommunicationsPermissions;
use Modules\Communications\Models\HandbookChapter;
use Modules\Microsoft365\Models\MicrosoftLink;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function deploySeed(): void
{
    test()->seed(RolesAndPermissionsSeeder::class);
    test()->seed(CommunicationsBootstrapSeeder::class);
    test()->seed(Microsoft365BootstrapSeeder::class);
}

it('runs the communications seeder after the core seeder on every Cloudron deploy', function () {
    $script = file_get_contents(base_path('deploy/cloudron/activate-release.sh'));

    $core = strpos($script, "db:seed --class='Database\\Seeders\\RolesAndPermissionsSeeder'");
    $comms = strpos($script, "db:seed --class='Database\\Seeders\\CommunicationsBootstrapSeeder'");
    $m365 = strpos($script, "db:seed --class='Database\\Seeders\\Microsoft365BootstrapSeeder'");

    expect($core)->not->toBeFalse()
        ->and($comms)->not->toBeFalse()
        ->and($m365)->not->toBeFalse()
        ->and($comms)->toBeGreaterThan($core)
        ->and($m365)->toBeGreaterThan($comms);
});

it('keeps the communications grants after a deploy re-runs the core seeder', function () {
    deploySeed();
    deploySeed();

    foreach (CommunicationsPermissions::grants() as $roleName => $permissions) {
        foreach ($permissions as $permission) {
            expect(Role::findByName($roleName)->hasPermissionTo($permission))->toBeTrue();
        }
    }

    expect(Role::findByName(RoleName::Staff->value)->hasPermissionTo(CommunicationsPermissions::PUBLISH))->toBeFalse()
        ->and(MicrosoftLink::query()->count())->toBe(8)
        ->and(HandbookChapter::query()->count())->toBe(2);
});

it('leaves Microsoft links and handbook chapters as admins edited them', function () {
    deploySeed();
    MicrosoftLink::query()->where('key', 'loop')->update(['active' => false, 'name' => 'Microsoft Loop']);
    HandbookChapter::query()->where('slug', 'sign-in-and-2fa')->update(['published' => false]);

    deploySeed();

    $loop = MicrosoftLink::query()->where('key', 'loop')->first();
    expect($loop->active)->toBeFalse()
        ->and($loop->name)->toBe('Microsoft Loop')
        ->and(HandbookChapter::query()->where('slug', 'sign-in-and-2fa')->value('published'))->toBeFalse();
});
