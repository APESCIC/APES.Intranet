<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\LearningPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Learning\EnrolmentMethod;
use Modules\Learning\LearningPermissions;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Enrolment;
use Modules\Learning\Services\CourseAuthoringService;
use Modules\Learning\Services\EnrolmentService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LearningPermissionsSeeder::class);
});

function enrolManager(): User
{
    $user = User::factory()->create();
    $user->assignRole(RoleName::Admin->value);
    $user->givePermissionTo([
        LearningPermissions::ACCESS,
        LearningPermissions::COURSE_AUTHOR,
        LearningPermissions::ENROL_MANAGE,
    ]);

    return $user;
}

function publishedCourse(User $author, string $title = 'Induction'): Course
{
    $service = app(CourseAuthoringService::class);
    $course = $service->createCourse(['title' => $title], $author);
    $section = $service->addSection($course, 'Core');
    $lesson = $service->addLesson($section, 'Welcome', '<p>Hello</p>');
    $service->publishLesson($lesson, $author);

    return $course->refresh();
}

it('enrols manually and grants access within dates', function () {
    $manager = enrolManager();
    $learner = User::factory()->create();
    $learner->assignRole(RoleName::Staff->value);
    $learner->givePermissionTo(LearningPermissions::ACCESS);

    $course = publishedCourse($manager);
    $service = app(EnrolmentService::class);

    $enrolment = $service->enrolManual($course, $learner, $manager, [
        'starts_at' => now()->subDay()->toDateTimeString(),
        'ends_at' => now()->addWeek()->toDateTimeString(),
    ]);

    expect($enrolment->method)->toBe(EnrolmentMethod::Manual)
        ->and($enrolment->isActive())->toBeTrue()
        ->and($service->canAccess($learner, $course))->toBeTrue();
});

it('updates group enrolments when canonical group membership changes', function () {
    $manager = enrolManager();
    $learner = User::factory()->create();
    $learner->assignRole(RoleName::Staff->value);
    $learner->givePermissionTo(LearningPermissions::ACCESS);

    $course = publishedCourse($manager, 'Fire safety');
    $service = app(EnrolmentService::class);
    $service->attachGroupRule($course, 'intranet.staff');

    $service->syncGroupEnrolmentsForUser($learner);

    $enrolment = Enrolment::query()->where('user_id', $learner->id)->where('course_id', $course->id)->first();
    expect($enrolment)->not->toBeNull()
        ->and($enrolment->method)->toBe(EnrolmentMethod::Group)
        ->and($enrolment->source_group)->toBe('intranet.staff')
        ->and($enrolment->isActive())->toBeTrue()
        ->and($service->canAccess($learner, $course))->toBeTrue();

    $learner->removeRole(RoleName::Staff->value);
    $service->syncGroupEnrolmentsForUser($learner->fresh());

    expect($enrolment->fresh()->status)->toBe(Enrolment::STATUS_ENDED)
        ->and($service->canAccess($learner->fresh(), $course))->toBeFalse();

    $learner->assignRole(RoleName::Staff->value);
    $service->syncGroupEnrolmentsForUser($learner->fresh());

    expect($enrolment->fresh()->status)->toBe(Enrolment::STATUS_ACTIVE)
        ->and($service->canAccess($learner->fresh(), $course))->toBeTrue();
});

it('blocks access until prerequisites are met', function () {
    $manager = enrolManager();
    $learner = User::factory()->create();
    $learner->assignRole(RoleName::Staff->value);
    $learner->givePermissionTo(LearningPermissions::ACCESS);

    $basics = publishedCourse($manager, 'Basics');
    $advanced = publishedCourse($manager, 'Advanced');
    $service = app(EnrolmentService::class);

    $service->addPrerequisite($advanced, $basics);
    $service->enrolManual($advanced, $learner, $manager);

    expect($service->prerequisitesMet($learner, $advanced))->toBeFalse()
        ->and($service->canAccess($learner, $advanced))->toBeFalse();

    $service->markCourseComplete($learner, $basics);

    expect($service->prerequisitesMet($learner, $advanced))->toBeTrue()
        ->and($service->canAccess($learner, $advanced))->toBeTrue();
});

it('supports self-enrol when enabled on a published course', function () {
    $manager = enrolManager();
    $learner = User::factory()->create();
    $learner->assignRole(RoleName::Volunteer->value);
    $learner->givePermissionTo(LearningPermissions::ACCESS);

    $course = publishedCourse($manager, 'Optional workshop');
    $course->update(['self_enrol_enabled' => true]);

    $service = app(EnrolmentService::class);
    $enrolment = $service->enrolSelf($course, $learner);

    expect($enrolment->method)->toBe(EnrolmentMethod::Self)
        ->and($service->canAccess($learner, $course))->toBeTrue();

    $this->actingAs($learner)
        ->post(route('learning.courses.self-enrol', $course))
        ->assertRedirect();
});

it('covers each enrolment method through the manage UI for managers', function () {
    $manager = enrolManager();
    $learner = User::factory()->create(['name' => 'Alex Learner']);
    $learner->assignRole(RoleName::Staff->value);
    $learner->givePermissionTo(LearningPermissions::ACCESS);

    $course = publishedCourse($manager, 'UI enrol');
    $course->update(['self_enrol_enabled' => true]);

    $this->actingAs($manager)
        ->get(route('learning.courses.enrolments.index', $course))
        ->assertOk();

    $this->actingAs($manager)
        ->post(route('learning.courses.enrolments.manual', $course), [
            'user_id' => $learner->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(Enrolment::query()->where('user_id', $learner->id)->where('method', EnrolmentMethod::Manual->value)->exists())->toBeTrue();

    $other = publishedCourse($manager, 'Prior course');

    $this->actingAs($manager)
        ->post(route('learning.courses.enrolments.prerequisites', $course), [
            'prerequisite_course_id' => $other->id,
        ])
        ->assertRedirect();

    $this->actingAs($manager)
        ->post(route('learning.courses.enrolments.group-rules', $course), [
            'directory_group' => 'intranet.staff',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($course->fresh()->groupRules)->toHaveCount(1);
});
