<?php

it('syncs roles and permissions in the live preview gate from the deploy seeder list', function () {
    $gate = file_get_contents(base_path('scripts/preview.ps1'));

    expect($gate)->toContain('activate-release.sh')
        ->and($gate)->toContain('db:seed --class=')
        ->and($gate)->toContain('permission:cache-reset');
});

it('finds the deploy seeders the live preview gate runs', function () {
    $script = file_get_contents(base_path('deploy/cloudron/activate-release.sh'));

    preg_match_all("/db:seed --class='([^']+)'/", $script, $matches);

    expect($matches[1])->toContain('Database\\Seeders\\RolesAndPermissionsSeeder')
        ->and($matches[1][0])->toBe('Database\\Seeders\\RolesAndPermissionsSeeder');

    foreach ($matches[1] as $seeder) {
        expect(class_exists($seeder))->toBeTrue();
    }
});
