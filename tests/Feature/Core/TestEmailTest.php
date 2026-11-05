<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\System\Notifications\TestEmail;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Notification::fake();
});

function signedInAdmin(object $test, RoleName $role = RoleName::Admin): User
{
    $user = User::factory()->create(['email' => 'ada.admin@example.test'])->assignRole($role->value);
    $test->actingAs($user)->withSession(['intranet.auth_time' => now()->getTimestamp()]);

    return $user;
}

it('sends a test email to the signed-in admin and says so', function () {
    $admin = signedInAdmin($this);

    $this->post(route('admin.system.test-email'))
        ->assertRedirect(route('admin.system.index'))
        ->assertSessionHas('status', fn (string $status) => str_contains($status, 'ada.admin@example.test'));

    Notification::assertSentTo($admin, TestEmail::class);
    Notification::assertCount(1);
});

it('always sends to the signed-in admin, whatever address is posted', function () {
    $admin = signedInAdmin($this);
    $other = User::factory()->create(['email' => 'someone.else@example.test']);

    $this->post(route('admin.system.test-email'), ['email' => 'someone.else@example.test', 'to' => 'attacker@example.test', 'recipient' => $other->id]);

    Notification::assertSentTo($admin, TestEmail::class);
    Notification::assertNotSentTo($other, TestEmail::class);
    Notification::assertSentOnDemandTimes(TestEmail::class, 0);
    Notification::assertCount(1);
});

it('offers no address field', function () {
    signedInAdmin($this);

    $form = (string) str($this->get(route('admin.system.index'))->getContent())->after('aria-labelledby="test-email-heading"')->before('</section>');

    expect($form)->toContain('Send me a test email')
        ->and($form)->not->toContain('type="email"')
        ->and($form)->not->toMatch('/<input(?![^>]*name="_token")[^>]*>/');
});

it('allows only 3 test emails an hour', function () {
    signedInAdmin($this);

    foreach (range(1, 4) as $attempt) {
        $this->post(route('admin.system.test-email'));
    }

    Notification::assertCount(3);
    $this->post(route('admin.system.test-email'))->assertSessionHas('error', fn (string $error) => str_contains($error, '3 test emails an hour'));

    $this->travel(61)->minutes();
    $this->withSession(['intranet.auth_time' => now()->getTimestamp()]);
    $this->post(route('admin.system.test-email'));
    Notification::assertCount(4);
});

it('writes an audit entry for each email sent', function () {
    $admin = signedInAdmin($this);

    $this->post(route('admin.system.test-email'));

    $entry = AuditLog::where('event', 'admin.system.test_email')->firstOrFail();
    expect($entry->actor_id)->toBe($admin->id)
        ->and($entry->context)->toBe(['result' => 'sent']);
});

it('is for admins only, with a fresh sign-in', function () {
    signedInAdmin($this, RoleName::Management);
    $this->post(route('admin.system.test-email'))->assertForbidden();

    $admin = User::factory()->create()->assignRole(RoleName::Admin->value);
    $this->actingAs($admin)->withSession(['intranet.auth_time' => now()->subHour()->getTimestamp()]);
    $this->post(route('admin.system.test-email'))->assertRedirect(route('auth.reauth'));

    Notification::assertNothingSent();
});
