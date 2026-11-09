<?php

use App\Core\Changelog\Release;
use App\Core\Changelog\ReleaseChangelogSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

function writeReleaseNote(string $directory, string $version, array $overrides = []): void
{
    file_put_contents("{$directory}/{$version}.json", json_encode(array_merge([
        'version' => $version,
        'released_at' => '2026-11-09',
        'title' => "Release {$version}",
        'summary' => 'What this release means for you.',
        'audiences' => ['staff'],
        'changes' => ['added' => ['Something new.']],
        'published' => true,
    ], $overrides)));
}

beforeEach(function () {
    $this->notes = sys_get_temp_dir().'/apes-changelog-'.uniqid();
    mkdir($this->notes);
    config(['intranet.changelog_path' => $this->notes]);
});

afterEach(function () {
    array_map('unlink', glob($this->notes.'/*') ?: []);
    rmdir($this->notes);
});

it('produces identical rows when run twice', function () {
    writeReleaseNote($this->notes, 'v0.1.0');
    writeReleaseNote($this->notes, 'v0.2.0', ['internal_notes' => ['For admins.']]);
    $sync = app(ReleaseChangelogSync::class);

    $first = $sync->sync();
    $rows = Release::query()->orderBy('id')->get()->toArray();
    $this->travel(5)->minutes();
    $second = $sync->sync();

    expect($first->created)->toBe(['v0.1.0', 'v0.2.0'])
        ->and($second->created)->toBe([])
        ->and($second->updated)->toBe([])
        ->and($second->unchanged)->toBe(['v0.1.0', 'v0.2.0'])
        ->and(Release::query()->orderBy('id')->get()->toArray())->toBe($rows)
        ->and(Release::count())->toBe(2);
});

it('updates a row when its file changes', function () {
    writeReleaseNote($this->notes, 'v0.1.0');
    app(ReleaseChangelogSync::class)->sync();

    writeReleaseNote($this->notes, 'v0.1.0', ['summary' => 'A better summary.']);
    $result = app(ReleaseChangelogSync::class)->sync();

    expect($result->updated)->toBe(['v0.1.0'])
        ->and(Release::query()->sole()->summary)->toBe('A better summary.');
});

it('stores unpublished entries as drafts', function () {
    writeReleaseNote($this->notes, 'v0.1.0', ['published' => false]);
    app(ReleaseChangelogSync::class)->sync();

    expect(Release::query()->sole()->is_published)->toBeFalse()
        ->and(Release::query()->published()->count())->toBe(0);
});

it('keeps and reports rows whose file has gone', function () {
    writeReleaseNote($this->notes, 'v0.1.0');
    writeReleaseNote($this->notes, 'v0.2.0');
    app(ReleaseChangelogSync::class)->sync();

    unlink($this->notes.'/v0.1.0.json');
    $result = app(ReleaseChangelogSync::class)->sync();

    expect($result->orphaned)->toBe(['v0.1.0'])
        ->and(Release::count())->toBe(2);
});

it('writes nothing when any file is invalid', function () {
    writeReleaseNote($this->notes, 'v0.1.0');
    writeReleaseNote($this->notes, 'v0.2.0', ['audiences' => ['public']]);

    expect(fn () => app(ReleaseChangelogSync::class)->sync())->toThrow(RuntimeException::class, 'nothing was synced')
        ->and(Release::count())->toBe(0);
});

it('stores the version parts so releases sort by version', function () {
    writeReleaseNote($this->notes, 'v0.3.9');
    writeReleaseNote($this->notes, 'v0.3.10');
    app(ReleaseChangelogSync::class)->sync();

    expect(Release::query()->newestFirst()->pluck('version')->all())->toBe(['v0.3.10', 'v0.3.9']);
});

it('logs a failed sync and exits non-zero without throwing', function () {
    writeReleaseNote($this->notes, 'v0.1.0', ['title' => '']);
    Log::spy();

    $this->artisan('changelog:sync')->assertFailed();

    Log::shouldHaveReceived('error')->once();
});

it('syncs from the command and reports orphans', function () {
    writeReleaseNote($this->notes, 'v0.1.0');

    $this->artisan('changelog:sync')
        ->expectsOutput('Release notes synced: 1 created, 0 updated, 0 unchanged.')
        ->assertSuccessful();
});

it('validates from the command, and can require a published entry', function () {
    writeReleaseNote($this->notes, 'v0.1.0');
    writeReleaseNote($this->notes, 'v0.2.0', ['published' => false]);

    $this->artisan('changelog:validate')->assertSuccessful();
    $this->artisan('changelog:validate', ['--require-published' => 'v0.1.0'])->assertSuccessful();
    $this->artisan('changelog:validate', ['--require-published' => 'v0.2.0'])->assertFailed();
    $this->artisan('changelog:validate', ['--require-published' => 'main'])->assertFailed();
});

it('keeps the release notes in this repository valid', function () {
    config(['intranet.changelog_path' => base_path('changelog/releases')]);

    $this->artisan('changelog:validate')->assertSuccessful();
});

it('syncs release notes after migrations and seeders on every Cloudron deploy, without failing the deploy', function () {
    $script = (string) file_get_contents(base_path('deploy/cloudron/activate-release.sh'));

    $migrate = strpos($script, 'artisan migrate --force');
    $seed = strpos($script, 'CommunicationsBootstrapSeeder');
    $sync = strpos($script, 'artisan changelog:sync ||');

    expect($sync)->not->toBeFalse()
        ->and($sync)->toBeGreaterThan($migrate)
        ->and($sync)->toBeGreaterThan($seed);
});
