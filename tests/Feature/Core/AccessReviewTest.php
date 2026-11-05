<?php

use App\Core\Access\Notifications\AccessReviewDue;
use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\Audit\AuditLogger;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @param  list<RoleName>  $roles
 */
function reviewPerson(string $name, array $roles, ?int $lastSignInDaysAgo = 1): User
{
    $user = User::factory()->create(['name' => $name])->syncRoles(array_map(fn (RoleName $role) => $role->value, $roles));

    if ($lastSignInDaysAgo !== null) {
        test()->travelTo(now()->subDays($lastSignInDaysAgo));
        app(AuditLogger::class)->record('auth.signed_in', $user, ['method' => 'cloudron'], $user);
        test()->travelBack();
    }

    return $user;
}

function openReview(object $test, RoleName $role = RoleName::Admin): string
{
    $viewer = reviewPerson('Rita Reviewer', [RoleName::Staff, $role]);
    $test->actingAs($viewer)->withSession(['intranet.auth_time' => now()->getTimestamp()]);

    return $test->get(route('admin.access-review.index'))->assertOk()->getContent();
}

function reviewSection(string $html, string $section): string
{
    return (string) str($html)->after('data-section="'.$section.'"')->before('</section>');
}

it('lists people with more than one senior role', function () {
    reviewPerson('Stacked Sam', [RoleName::Staff, RoleName::Admin, RoleName::Hr]);
    reviewPerson('Single Sue', [RoleName::Staff, RoleName::Hr]);

    $section = reviewSection(openReview($this), 'stacked');

    expect($section)->toContain('Stacked Sam')->toContain('Admin, HR, Staff')
        ->and($section)->not->toContain('Single Sue');
});

it('lists role holders who have not signed in for 90 days', function () {
    reviewPerson('Gone Gary', [RoleName::Volunteer], lastSignInDaysAgo: 100);
    reviewPerson('Never Nina', [RoleName::Student], lastSignInDaysAgo: null);
    reviewPerson('Active Ann', [RoleName::Staff], lastSignInDaysAgo: 10);

    $section = reviewSection(openReview($this), 'leavers');

    expect($section)->toContain('Gone Gary')->toContain('Never Nina')->toContain('never')
        ->and($section)->not->toContain('Active Ann');
});

it('lists people in no group', function () {
    reviewPerson('Groupless Gail', [], lastSignInDaysAgo: 5);
    reviewPerson('Member Mo', [RoleName::Staff]);

    $section = reviewSection(openReview($this), 'no-group');

    expect($section)->toContain('Groupless Gail')
        ->and($section)->not->toContain('Member Mo');
});

it('lists admin and management views of DBS and health data, but not HR views', function () {
    $admin = reviewPerson('Admin Ali', [RoleName::Staff, RoleName::Admin]);
    $manager = reviewPerson('Manager Meg', [RoleName::Staff, RoleName::Management]);
    $hr = reviewPerson('HR Hana', [RoleName::Staff, RoleName::Hr]);
    $subject = User::factory()->create();

    foreach ([[$admin, 'dbs'], [$manager, 'health'], [$hr, 'dbs']] as [$actor, $category]) {
        app(AuditLogger::class)->record('hr.special_category.viewed', $subject, ['category' => $category], $actor);
    }

    $section = reviewSection(openReview($this), 'sensitive');

    expect($section)->toContain('Admin Ali')->toContain('viewed DBS record #'.$subject->id)
        ->and($section)->toContain('Manager Meg')->toContain('viewed health record')
        ->and($section)->not->toContain('HR Hana');
});

it('is for admin and HR only, with a fresh sign-in, and audits each view', function () {
    foreach ([RoleName::Management, RoleName::Legal, RoleName::Supervisor, RoleName::Staff] as $role) {
        $user = reviewPerson('Someone', [RoleName::Staff, $role]);
        $this->actingAs($user)->withSession(['intranet.auth_time' => now()->getTimestamp()])
            ->get(route('admin.access-review.index'))->assertForbidden();
    }

    openReview($this, RoleName::Hr);
    expect(AuditLog::where('event', 'admin.access_review.viewed')->count())->toBe(1);

    $admin = reviewPerson('Stale Admin', [RoleName::Admin]);
    $this->actingAs($admin)->withSession(['intranet.auth_time' => now()->subHour()->getTimestamp()])
        ->get(route('admin.access-review.index'))->assertRedirect(route('auth.reauth'));
});

it('schedules the reminder every quarter', function () {
    $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains((string) $event->command, 'access-review:remind'));

    expect($event?->expression)->toBe('0 9 1 1-12/3 *');
});

it('sends the quarterly reminder to admin and HR only', function () {
    Notification::fake();
    $admin = reviewPerson('Admin Ali', [RoleName::Staff, RoleName::Admin]);
    $hr = reviewPerson('HR Hana', [RoleName::Staff, RoleName::Hr]);
    $manager = reviewPerson('Manager Meg', [RoleName::Staff, RoleName::Management]);

    $this->artisan('access-review:remind')->assertSuccessful();

    Notification::assertSentTo([$admin, $hr], AccessReviewDue::class);
    Notification::assertNotSentTo($manager, AccessReviewDue::class);
});
