<?php

use App\Core\Access\RoleName;
use App\Core\System\Notifications\AddonDown;
use App\Core\System\SystemCheck;
use App\Core\System\SystemStatus;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Fakes\FakeAddonCheck;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Notification::fake();

    $this->redis = new FakeAddonCheck('redis');
    $this->mysql = new FakeAddonCheck('mysql');
    app()->instance(SystemStatus::class, new SystemStatus([$this->mysql, $this->redis]));

    $this->admins = User::factory()->count(2)->create()->each->assignRole(RoleName::Admin->value);
    $this->staff = User::factory()->create()->assignRole(RoleName::Staff->value);
});

function runChecksEveryFiveMinutes(object $test, int $times): void
{
    for ($i = 0; $i < $times; $i++) {
        $test->artisan('system:check')->assertSuccessful();
        $test->travel(5)->minutes();
    }
}

it('is scheduled every 5 minutes without overlapping', function () {
    $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains((string) $event->command, 'system:check'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('*/5 * * * *')
        ->and($event->withoutOverlapping)->toBeTrue();
});

it('is due every 5 minutes on a fake clock', function () {
    $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains((string) $event->command, 'system:check'));
    $due = function (int $hour, int $minute) use ($event) {
        $this->travelTo(now()->setTime($hour, $minute));

        return $event->isDue(app());
    };

    expect($due(9, 0))->toBeTrue()
        ->and($due(9, 2))->toBeFalse()
        ->and($due(9, 5))->toBeTrue()
        ->and($due(9, 7))->toBeFalse();
});

it('stores each result, the time and a scheduler heartbeat', function () {
    $this->travelTo(now()->setTime(9, 0));
    $this->redis->up = false;

    $this->artisan('system:check')->expectsOutput('mysql: ok')->expectsOutput('redis: down')->assertSuccessful();

    expect($this->redis->probes)->toBe(1)
        ->and(SystemCheck::where('check', 'redis')->first()?->checked_at->format('H:i'))->toBe('09:00')
        ->and(SystemCheck::where('check', 'redis')->value('consecutive_failures'))->toBe(1)
        ->and(SystemCheck::where('check', 'scheduler')->value('status'))->toBe(SystemCheck::OK);
});

it('emails admins once after 3 failures in a row, and not again for the same outage', function () {
    $this->redis->up = false;

    runChecksEveryFiveMinutes($this, 2);
    Notification::assertNothingSent();

    runChecksEveryFiveMinutes($this, 1);
    Notification::assertSentTo($this->admins, AddonDown::class, fn (AddonDown $n) => $n->addons === ['Redis']);
    Notification::assertNotSentTo($this->staff, AddonDown::class);

    runChecksEveryFiveMinutes($this, 5);
    Notification::assertSentToTimes($this->admins->first(), AddonDown::class, 1);
});

it('emails again for a new outage after recovering', function () {
    $this->redis->up = false;
    runChecksEveryFiveMinutes($this, 3);

    $this->redis->up = true;
    runChecksEveryFiveMinutes($this, 1);
    expect(SystemCheck::where('check', 'redis')->first()?->alerted_at)->toBeNull();

    $this->redis->up = false;
    runChecksEveryFiveMinutes($this, 3);
    Notification::assertSentToTimes($this->admins->first(), AddonDown::class, 2);
});

it('does not count a failure that recovers before 3 in a row', function () {
    $this->redis->up = false;
    runChecksEveryFiveMinutes($this, 2);
    $this->redis->up = true;
    runChecksEveryFiveMinutes($this, 1);
    $this->redis->up = false;
    runChecksEveryFiveMinutes($this, 2);

    Notification::assertNothingSent();
});

it('names the addon in the email without any error detail', function () {
    $mail = (new AddonDown(['Redis']))->toMail($this->admins->first());

    expect($mail->subject)->toBe('APES Intranet: Redis down')
        ->and(implode(' ', $mail->introLines))->toContain('3 times in a row');
});

it('leaves /healthz unchanged', function () {
    $this->redis->up = false;
    runChecksEveryFiveMinutes($this, 3);

    expect(array_keys($this->getJson('/healthz')->json()))->toBe(['status', 'checks', 'version', 'revision'])
        ->and(array_keys($this->getJson('/healthz')->json('checks')))->toBe(['app', 'database', 'redis']);
});
