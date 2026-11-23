<?php

use Illuminate\Support\Facades\File;
use Tests\Support\ModuleConventions;

it('discovers every modules folder with a manifest', function () {
    expect(ModuleConventions::shippedSlugs())->toBe([
        'communications',
        'helpdesk',
        'hr',
        'learning',
        'microsoft-365',
        'operations',
        'people',
    ]);
});

it('requires each shipped module to follow the layout checklist', function () {
    foreach (ModuleConventions::shippedSlugs() as $slug) {
        expect(ModuleConventions::violations($slug))
            ->toBe([], "Module [{$slug}] failed the layout checklist");
    }
});

it('reports convention failures for an incomplete temporary module', function () {
    $tempRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'apes-module-convention-'.uniqid('', true);
    $slug = 'sample-broken';
    $moduleRoot = $tempRoot.DIRECTORY_SEPARATOR.$slug;
    File::ensureDirectoryExists($moduleRoot);
    File::put($moduleRoot.'/module.php', <<<'PHP'
<?php

return [
    'slug' => 'sample-broken',
    'name' => 'Sample broken',
    'kind' => 'module',
    'description' => 'Intentionally incomplete for convention tests.',
    'default_enabled' => true,
];
PHP);

    try {
        expect(ModuleConventions::violations($slug, $tempRoot))->not->toBeEmpty()
            ->and(ModuleConventions::violations($slug, $tempRoot))->toContain(
                '[sample-broken] manifest: Manifest [sample-broken] needs a valid SemVer version (for example 1.0.0).',
            )
            ->and(ModuleConventions::violations($slug, $tempRoot))->toContain(
                '[sample-broken] missing service provider at modules/sample-broken/src/SampleBrokenServiceProvider.php',
            );
    } finally {
        File::deleteDirectory($tempRoot);
    }
});
