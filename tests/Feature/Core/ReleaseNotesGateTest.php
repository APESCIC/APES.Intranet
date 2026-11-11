<?php

it('fails CI on a version tag that has no published release note', function () {
    $workflow = (string) file_get_contents(base_path('.github/workflows/ci.yml'));
    $push = (string) str($workflow)->after("  push:\n")->before("\npermissions:");
    $tagStep = (string) str($workflow)->after('- name: Check the tag has a published release note')->before('- name:');

    expect($push)->toContain("tags:\n      - 'v*'")
        ->and($workflow)->toContain("run: php artisan changelog:validate\n")
        ->and($tagStep)->toContain("if: github.ref_type == 'tag'")
        ->and($tagStep)->toContain('changelog:validate --require-published="$TAG"');
});

it('refuses to deploy a tag without a published release note', function () {
    $script = (string) file_get_contents(base_path('deploy/cloudron/deploy.ps1'));

    $noteCheck = strpos($script, 'changelog/releases/$Tag.json');
    $build = strpos($script, "build-release.ps1') -Ref");

    expect($noteCheck)->not->toBeFalse()
        ->and($noteCheck)->toBeLessThan($build)
        ->and($script)->toContain('release note is not published');
});

it('documents the publish step in the shipping rule and the rollback in the release-notes guide', function () {
    $agents = (string) file_get_contents(base_path('AGENTS.md'));
    $rule = (string) file_get_contents(base_path('.cursor/rules/shipping.mdc'));
    $guide = (string) file_get_contents(base_path('docs/release-notes.md'));

    expect($agents)->toContain('.cursor/rules/shipping.mdc')
        ->and($rule)->toContain('changelog/releases/vX.Y.Z.json')
        ->and($rule)->toContain('"published": true')
        ->and($rule)->toContain('docs/release-notes.md')
        ->and($guide)->toContain('## Rolling back')
        ->and($guide)->toContain('## Unpublishing or correcting a note')
        ->and($guide)->toContain('full version');
});
