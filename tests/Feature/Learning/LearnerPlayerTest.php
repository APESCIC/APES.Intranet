<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\LearningPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Learning\LearningPermissions;
use Modules\Learning\Services\CourseAuthoringService;
use Modules\Learning\Services\EnrolmentService;
use Modules\Learning\Services\ProgressService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LearningPermissionsSeeder::class);
});

function playerAuthor(): User
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

function playerLearner(): User
{
    $user = User::factory()->create();
    $user->assignRole(RoleName::Staff->value);
    $user->givePermissionTo(LearningPermissions::ACCESS);

    return $user;
}

it('shows enrolled courses on My learning and plays a lesson at phone widths', function () {
    $author = playerAuthor();
    $learner = playerLearner();
    $authoring = app(CourseAuthoringService::class);

    $course = $authoring->createCourse(['title' => 'Induction player'], $author);
    $section = $authoring->addSection($course, 'Welcome');
    $lesson = $authoring->addLesson($section, 'Hello', '<p>Safe content</p>');
    $authoring->publishLesson($lesson, $author);
    app(EnrolmentService::class)->enrolManual($course->refresh(), $learner, $author);

    $this->actingAs($learner)
        ->get(route('learning.mine'))
        ->assertOk()
        ->assertSee('Induction player')
        ->assertSee('Continue')
        ->assertDontSee('grade', false)
        ->assertDontSee('Grade');

    $this->actingAs($learner)
        ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)')
        ->get(route('learning.play.show', $course))
        ->assertOk()
        ->assertSee('Hello')
        ->assertSee('Start');

    $this->actingAs($learner)
        ->get(route('learning.play.lesson', [$course, $lesson]))
        ->assertOk()
        ->assertSee('Safe content')
        ->assertSee('Mark lesson complete')
        ->assertDontSee('grade', false);

    $this->actingAs($learner)
        ->post(route('learning.play.complete', [$course, $lesson]))
        ->assertRedirect();

    expect(app(ProgressService::class)->isLessonComplete($learner, $lesson))->toBeTrue()
        ->and(app(ProgressService::class)->isCourseComplete($learner, $course))->toBeTrue();
});

it('blocks the player when the learner is not enrolled', function () {
    $author = playerAuthor();
    $learner = playerLearner();
    $authoring = app(CourseAuthoringService::class);
    $course = $authoring->createCourse(['title' => 'Locked'], $author);
    $section = $authoring->addSection($course, 'Core');
    $lesson = $authoring->addLesson($section, 'Secret', '<p>No</p>');
    $authoring->publishLesson($lesson, $author);

    $this->actingAs($learner)
        ->get(route('learning.play.show', $course->refresh()))
        ->assertForbidden();
});

it('keeps grade data out of the push-only service worker cache surface', function () {
    $script = file_get_contents(public_path('sw.js'));

    expect($script)->not->toContain('caches.open')
        ->and($script)->not->toContain('cache.put')
        ->and($script)->not->toContain('CacheStorage')
        ->and(strtolower($script))->not->toContain('grade')
        ->and(strtolower($script))->not->toContain('learning.grade')
        ->and(strtolower($script))->not->toContain('/learning/');
});
