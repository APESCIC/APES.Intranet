<?php

it('runs pending migrations and only the idempotent legal pages seeder in the live preview gate', function () {
    $gate = file_get_contents(base_path('scripts/preview.ps1'));

    expect($gate)->toContain('php artisan migrate --force')
        ->and($gate)->not->toContain('Ask before running php artisan migrate')
        ->and($gate)->toContain("\$seeders = @('Database\\Seeders\\LegalPagesSeeder')")
        ->and($gate)->toContain('Synced idempotent seeders')
        ->and($gate)->not->toContain('permission:cache-reset')
        ->and($gate)->not->toContain("db:seed --class='([^']+)'");
});

it('keeps LegalPagesSeeder in the Cloudron activate-release seeder list', function () {
    $script = file_get_contents(base_path('deploy/cloudron/activate-release.sh'));

    preg_match_all("/db:seed --class='([^']+)'/", $script, $matches);

    expect($matches[1])->toContain('Database\\Seeders\\RolesAndPermissionsSeeder')
        ->and($matches[1])->toContain('Database\\Seeders\\LegalPagesSeeder')
        ->and($matches[1][0])->toBe('Database\\Seeders\\RolesAndPermissionsSeeder');

    foreach ($matches[1] as $seeder) {
        expect(class_exists($seeder))->toBeTrue();
    }
});

it('compares package-lock by content hash and stops this site Vite before npm ci', function () {
    $gate = file_get_contents(base_path('scripts/preview.ps1'));

    expect($gate)->toContain('Get-FileHash -Algorithm SHA256')
        ->and($gate)->toContain('.apes-package-lock.sha256')
        ->and($gate)->not->toContain('LastWriteTime -gt (Get-Item $npmMarker).LastWriteTime')
        ->and($gate)->toContain('function Stop-SiteVite')
        ->and($gate)->toContain('before npm ci')
        ->and($gate)->toContain('Restarted npm run dev on port');
});
