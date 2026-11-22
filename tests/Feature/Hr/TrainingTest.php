<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\LearningPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Hr\Models\TrainingCourse;
use Modules\Hr\Services\TrainingService;
use Modules\Learning\LearningPermissions;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\CourseCompletion;
use Modules\Learning\Services\CourseAuthoringService;
use Modules\Learning\Services\EnrolmentService;
use Modules\Learning\Services\ProgressService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LearningPermissionsSeeder::class);
});

/**
 * @param  list<RoleName>  $roles
 */
function trainingActor(object $test, array $roles): User
{
    $user = User::factory()->create()->syncRoles(array_map(fn (RoleName $role) => $role->value, $roles));
    $test->actingAs($user)->withSession(['intranet.auth_time' => now()->getTimestamp()]);

    return $user;
}

it('shows a member their required courses and LMS links', function () {
    $member = trainingActor($this, [RoleName::Staff]);

    $this->get(route('hr.training.mine'))
        ->assertOk()
        ->assertSee('Safeguarding essentials')
        ->assertSee('https://upskilling.intranet.apes.org.uk')
        ->assertSee('Moodle Upskilling')
        ->assertSee('overdue or missing');

    $rows = app(TrainingService::class)->forMember($member);
    expect($rows)->not->toBeEmpty()
        ->and($rows->every(fn (array $row) => $row['overdue']))->toBeTrue();
});

it('lets HR record a completion and clear the overdue report', function () {
    $hr = trainingActor($this, [RoleName::Staff, RoleName::Hr]);
    $member = User::factory()->create(['name' => 'Trainee Example'])->assignRole(RoleName::Staff->value);
    app(TrainingService::class)->ensureDefaults();
    $course = TrainingCourse::query()->where('code', 'safeguarding')->firstOrFail();

    $this->get(route('hr.training.overdue'))
        ->assertOk()
        ->assertSee('Trainee Example');

    $this->post(route('hr.training.completions.store'), [
        'user_id' => $member->id,
        'course_id' => $course->id,
        'completed_on' => now()->toDateString(),
    ])->assertRedirect();

    $row = app(TrainingService::class)->forMember($member)
        ->first(fn (array $r) => $r['course']->id === $course->id);

    expect($row['overdue'])->toBeFalse()
        ->and($row['completion'])->not->toBeNull();
});

it('refuses the overdue report to staff without HR', function () {
    trainingActor($this, [RoleName::Staff]);
    $this->get(route('hr.training.overdue'))->assertForbidden();
});

it('treats a native Learning Module completion as satisfying a linked requirement', function () {
    $author = User::factory()->create();
    $author->assignRole(RoleName::Admin->value);
    $author->givePermissionTo([
        LearningPermissions::ACCESS,
        LearningPermissions::COURSE_AUTHOR,
        LearningPermissions::ENROL_MANAGE,
    ]);

    $member = trainingActor($this, [RoleName::Staff]);
    $member->givePermissionTo(LearningPermissions::ACCESS);

    $authoring = app(CourseAuthoringService::class);
    $learningCourse = $authoring->createCourse(['title' => 'Safeguarding on intranet'], $author);
    $learningCourse->forceFill(['status' => Course::STATUS_PUBLISHED])->save();
    $section = $authoring->addSection($learningCourse, 'Core');
    $lesson = $authoring->addLesson($section, 'Intro', '<p>Hi</p>');
    $authoring->publishLesson($lesson, $author);

    app(TrainingService::class)->ensureDefaults();
    $hrCourse = TrainingCourse::query()->where('code', 'safeguarding')->firstOrFail();
    $hrCourse->forceFill(['learning_course_id' => $learningCourse->id])->save();

    expect(app(TrainingService::class)->forMember($member)
        ->first(fn (array $r) => $r['course']->id === $hrCourse->id)['overdue'])->toBeTrue();

    app(EnrolmentService::class)->enrolManual($learningCourse, $member, $author);
    app(ProgressService::class)->completeLesson($member, $lesson->refresh());

    expect(CourseCompletion::query()->where('user_id', $member->id)->where('course_id', $learningCourse->id)->exists())->toBeTrue();

    $row = app(TrainingService::class)->forMember($member)
        ->first(fn (array $r) => $r['course']->id === $hrCourse->id);

    expect($row['overdue'])->toBeFalse()
        ->and($row['native_complete'])->toBeTrue();

    $this->get(route('hr.training.mine'))
        ->assertOk()
        ->assertSee('Safeguarding essentials')
        ->assertSee('intranet record')
        ->assertSee('https://upskilling.intranet.apes.org.uk');

    $overdueForMember = collect(app(TrainingService::class)->overdueReport())
        ->first(fn (array $r) => $r['user']->id === $member->id);

    expect($overdueForMember)->not->toBeNull()
        ->and(collect($overdueForMember['overdue'])->contains(fn ($c) => $c->code === 'safeguarding'))->toBeFalse();
});
