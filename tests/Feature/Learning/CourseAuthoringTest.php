<?php

use App\Core\Access\RoleName;
use App\Core\Support\HtmlSanitizer;
use App\Models\User;
use Database\Seeders\LearningPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Learning\LearningPermissions;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Services\CourseAuthoringService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LearningPermissionsSeeder::class);
});

function authorUser(): User
{
    $user = User::factory()->create();
    $user->assignRole(RoleName::Admin->value);
    $user->givePermissionTo([
        LearningPermissions::ACCESS,
        LearningPermissions::COURSE_AUTHOR,
    ]);

    return $user;
}

it('publishes an edit as a new lesson version with sanitised body', function () {
    $author = authorUser();
    $service = app(CourseAuthoringService::class);

    $course = $service->createCourse(['title' => 'Safeguarding basics'], $author);
    $section = $service->addSection($course, 'Intro');
    $lesson = $service->addLesson($section, 'Welcome', '<p onclick="x">Hello<script>bad()</script></p>');

    $v1 = $service->publishLesson($lesson, $author);
    expect($v1->version_number)->toBe(1)
        ->and($v1->body)->toContain('Hello')
        ->and($v1->body)->not->toContain('<script')
        ->and($v1->body)->not->toContain('onclick')
        ->and($course->refresh()->status)->toBe(Course::STATUS_PUBLISHED);

    $service->updateLessonDraft($lesson, 'Welcome', '<p>Updated body</p>');
    $v2 = $service->publishLesson($lesson->refresh(), $author);

    expect($v2->version_number)->toBe(2)
        ->and($lesson->versions()->count())->toBe(2)
        ->and($lesson->fresh()->currentVersion?->id)->toBe($v2->id);
});

it('keeps learners on their locked version after a later publish', function () {
    $author = authorUser();
    $learner = User::factory()->create();
    $learner->assignRole(RoleName::Staff->value);
    $learner->givePermissionTo(LearningPermissions::ACCESS);

    $service = app(CourseAuthoringService::class);
    $course = $service->createCourse(['title' => 'Fire safety'], $author);
    $section = $service->addSection($course, 'Core');
    $lesson = $service->addLesson($section, 'Extinguishers', '<p>Version one</p>');
    $v1 = $service->publishLesson($lesson, $author);

    $locked = $service->lockLearnerVersion($learner, $lesson);
    expect($locked->id)->toBe($v1->id);

    $service->updateLessonDraft($lesson, 'Extinguishers', '<p>Version two</p>');
    $v2 = $service->publishLesson($lesson->refresh(), $author);

    expect($service->versionForLearner($learner, $lesson->fresh())->id)->toBe($v1->id)
        ->and($v2->version_number)->toBe(2)
        ->and($lesson->fresh()->currentVersion?->id)->toBe($v2->id);
});

it('lets an author create and publish a lesson through the web UI', function () {
    $author = authorUser();

    $this->actingAs($author)
        ->post(route('learning.courses.store'), [
            'title' => 'Induction',
            'summary' => 'Day one',
        ])
        ->assertRedirect();

    $course = Course::query()->where('slug', 'induction')->firstOrFail();

    $this->actingAs($author)
        ->post(route('learning.courses.sections.store', $course), ['title' => 'Welcome'])
        ->assertRedirect();

    $section = $course->sections()->firstOrFail();

    $this->actingAs($author)
        ->post(route('learning.courses.sections.lessons.store', [$course, $section]), [
            'title' => 'Hello',
            'body' => '<p>Safe <script>x</script>text</p>',
        ])
        ->assertRedirect();

    $lesson = Lesson::query()->where('title', 'Hello')->firstOrFail();
    expect($lesson->draft_body)->not->toContain('<script');

    $this->actingAs($author)
        ->post(route('learning.courses.lessons.publish', [$course, $lesson]))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($lesson->fresh()->versions)->toHaveCount(1);
});

it('reuses the core HtmlSanitizer for lesson bodies', function () {
    $sanitizer = app(HtmlSanitizer::class);
    $dirty = '<p onclick="alert(1)">Hi<script>x</script></p>';

    expect($sanitizer->clean($dirty))->not->toContain('onclick')
        ->and($sanitizer->clean($dirty))->not->toContain('<script');
});
