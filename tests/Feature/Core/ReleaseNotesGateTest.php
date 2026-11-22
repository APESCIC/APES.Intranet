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
    $workflow = (string) file_get_contents(base_path('.github/workflows/deploy-cloudron.yml'));
    $confirm = (string) str($workflow)->after('- name: Confirm tag is on main and has a published Release')->before('- name: Set up PHP');

    expect($confirm)->toContain('changelog/releases/${TAG}.json')
        ->and($confirm)->toContain('release note is not published')
        ->and($confirm)->not->toContain('tr -d')
        ->and($workflow)->toContain("printf '%s' \"\$TAG\" > release/VERSION")
        ->and($workflow)->toContain('expected_version="$TAG"')
        ->and($workflow)->not->toContain('deploy/cloudron/deploy.ps1');
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
