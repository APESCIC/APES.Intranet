<?php

use App\Core\Audit\AuditLog;
use App\Core\Qa\QaLogin;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Communications\Models\Event;
use Modules\Communications\Models\EventRsvp;
use Modules\Communications\Models\HandbookChapter;
use Modules\Communications\Models\PolicyDocument;
use Modules\Communications\Models\UpdateComment;
use Modules\Communications\Models\UpdatePost;
use Modules\Communications\Models\UpdateRead;
use Modules\Operations\Models\ChecklistRun;
use Modules\Operations\Models\ClockRecord;
use Modules\Operations\Models\Form;
use Modules\Operations\Models\RotaWeek;
use Modules\Operations\Models\Shift;
use Modules\Operations\Models\Task;
use Modules\Operations\Models\Timesheet;
use Modules\People\Models\Placement;
use Modules\People\Models\PlacementHour;

uses(RefreshDatabase::class);

const DRAMA_PHONE = '/^(07700 900\d{3}|0113 496 0\d{3}|020 7946 0\d{3})$/';

function seedDemo(): void
{
    app()->detectEnvironment(fn () => 'local');
    test()->seed(DemoSeeder::class);
}

/**
 * @return array<string, int>
 */
function demoCounts(): array
{
    return collect([User::class, Placement::class, PlacementHour::class, UpdatePost::class, UpdateComment::class, UpdateRead::class, Event::class, EventRsvp::class, HandbookChapter::class, PolicyDocument::class, AuditLog::class, RotaWeek::class, Shift::class, ClockRecord::class, Timesheet::class, Task::class, Form::class, ChecklistRun::class])
        ->mapWithKeys(fn (string $model) => [class_basename($model) => $model::query()->count()])
        ->all();
}

it('refuses to load outside the local environment', function (string $env) {
    app()->detectEnvironment(fn () => $env);

    app(DemoSeeder::class)->run();
})->with(['production', 'testing', 'staging'])->throws(RuntimeException::class, DemoSeeder::REFUSAL);

it('refuses demo:seed and demo:reset outside local', function (string $command) {
    $this->artisan($command)->expectsOutputToContain(DemoSeeder::REFUSAL)->assertExitCode(1);
})->with(['demo:seed', 'demo:reset']);

it('fills every main list', function () {
    seedDemo();

    $counts = demoCounts();
    expect($counts['User'])->toBe(DemoSeeder::PEOPLE + count(QaLogin::profiles()))
        ->and($counts['UpdatePost'])->toBe(30)
        ->and($counts['Event'])->toBe(12)
        ->and($counts['PolicyDocument'])->toBe(8)
        ->and($counts['AuditLog'])->toBe(40)
        ->and($counts['Placement'])->toBeGreaterThan(10)
        ->and($counts['PlacementHour'])->toBe($counts['Placement'] * 6)
        ->and($counts['UpdateComment'])->toBeGreaterThan(40)
        ->and($counts['EventRsvp'])->toBeGreaterThan(100)
        ->and($counts['RotaWeek'])->toBe(2)
        ->and($counts['Shift'])->toBe(28)
        ->and($counts['Timesheet'])->toBeGreaterThan(0)
        ->and($counts['Task'])->toBe(5)
        ->and($counts['Form'])->toBe(2)
        ->and($counts['ChecklistRun'])->toBe(14);
});

it('gives the same data, with no duplicates, when run again', function () {
    seedDemo();
    $counts = demoCounts();
    $people = User::query()->orderBy('email')->get(['email', 'name', 'mobile', 'manager_id'])->toArray();

    seedDemo();

    expect(demoCounts())->toBe($counts)
        ->and(User::query()->orderBy('email')->get(['email', 'name', 'mobile', 'manager_id'])->toArray())->toBe($people);
});

it('uses a fixed seed, so a fresh database gets the same people', function () {
    seedDemo();
    $first = User::query()->where('email', 'person01@example.test')->value('name');

    User::query()->where('email', 'like', 'person%')->delete();
    seedDemo();

    expect(User::query()->where('email', 'person01@example.test')->value('name'))->toBe($first);
});

it('only uses example domains and Ofcom drama phone numbers', function () {
    seedDemo();

    expect(User::query()->pluck('email')->reject(fn ($email) => preg_match('/@example\.(com|test|org)$/', $email) === 1)->all())->toBe([]);

    $phones = User::query()->get(['phone', 'mobile', 'emergency_contact_phone'])
        ->flatMap(fn (User $u) => [$u->phone, $u->mobile, $u->emergency_contact_phone])
        ->filter();
    expect($phones->reject(fn ($phone) => preg_match(DRAMA_PHONE, $phone) === 1)->all())->toBe([]);

    expect(PolicyDocument::query()->pluck('sharepoint_url')->reject(fn ($url) => str_starts_with($url, 'https://example.org/'))->all())->toBe([]);
});

it('gives each permission role a demo person', function () {
    seedDemo();

    foreach (['admin', 'management', 'hr', 'legal', 'supervisor'] as $role) {
        expect(User::role($role)->where('email', 'like', 'person%@example.test')->exists())->toBeTrue("no demo person has {$role}");
    }
});

it('keeps the factory phone numbers in the drama ranges', function () {
    $users = User::factory()->count(20)->make();

    foreach ($users as $user) {
        foreach ([$user->phone, $user->mobile, $user->emergency_contact_phone] as $phone) {
            if ($phone !== null) {
                expect($phone)->toMatch(DRAMA_PHONE);
            }
        }
    }
});
