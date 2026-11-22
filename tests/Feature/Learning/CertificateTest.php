<?php

use App\Core\Access\RoleName;
use App\Core\Notifications\Notifications\IntranetAlert;
use App\Models\User;
use Database\Seeders\LearningPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Learning\LearningPermissions;
use Modules\Learning\Models\Certificate;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\CourseCompletion;
use Modules\Learning\Models\Enrolment;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Services\CourseAuthoringService;
use Modules\Learning\Services\EnrolmentService;
use Modules\Learning\Services\ProgressService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LearningPermissionsSeeder::class);
});

function certAuthor(): User
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

function certLearner(): User
{
    $user = User::factory()->create();
    $user->assignRole(RoleName::Staff->value);
    $user->givePermissionTo(LearningPermissions::ACCESS);

    return $user;
}

/**
 * @return array{0: Course, 1: list<Lesson>}
 */
function certTwoLessonCourse(User $author, float $cpdHours = 2.5, int $renewMonths = 12): array
{
    $authoring = app(CourseAuthoringService::class);
    $course = $authoring->createCourse(['title' => 'Certificate course'], $author);
    $course->forceFill([
        'cpd_hours' => $cpdHours,
        'certificate_renew_months' => $renewMonths,
        'status' => Course::STATUS_PUBLISHED,
    ])->save();

    $section = $authoring->addSection($course, 'Core');
    $l1 = $authoring->addLesson($section, 'One', '<p>First</p>');
    $l2 = $authoring->addLesson($section, 'Two', '<p>Second</p>');
    $authoring->publishLesson($l1, $author);
    $authoring->publishLesson($l2, $author);

    return [$course->refresh(), [$l1->refresh(), $l2->refresh()]];
}

it('issues a certificate with verify code and CPD hours when the course is completed', function () {
    $author = certAuthor();
    $learner = certLearner();
    [$course, $lessons] = certTwoLessonCourse($author);

    app(EnrolmentService::class)->enrolManual($course, $learner, $author);
    $progress = app(ProgressService::class);

    foreach ($lessons as $lesson) {
        $progress->completeLesson($learner, $lesson);
    }

    $certificate = Certificate::query()
        ->where('user_id', $learner->id)
        ->where('course_id', $course->id)
        ->first();

    expect($certificate)->not->toBeNull()
        ->and($certificate->verify_code)->toHaveLength(12)
        ->and((float) $certificate->cpd_hours)->toBe(2.5)
        ->and($certificate->expires_on)->not->toBeNull()
        ->and($certificate->isCurrent())->toBeTrue();
});

it('shows a valid certificate on the verify page without grade content', function () {
    $author = certAuthor();
    $learner = certLearner();
    [$course, $lessons] = certTwoLessonCourse($author);

    app(EnrolmentService::class)->enrolManual($course, $learner, $author);
    foreach ($lessons as $lesson) {
        app(ProgressService::class)->completeLesson($learner, $lesson);
    }

    $certificate = Certificate::query()->where('user_id', $learner->id)->firstOrFail();

    $response = $this->get(route('learning.certificates.verify', $certificate->verify_code));

    $response->assertOk()
        ->assertSee('Valid certificate', false)
        ->assertSee($certificate->verify_code, false)
        ->assertSee($course->title, false)
        ->assertDontSee('Grade distribution', false)
        ->assertDontSee('final grade', false);
});

it('downloads a PDF certificate for the owner', function () {
    $author = certAuthor();
    $learner = certLearner();
    [$course, $lessons] = certTwoLessonCourse($author);

    app(EnrolmentService::class)->enrolManual($course, $learner, $author);
    foreach ($lessons as $lesson) {
        app(ProgressService::class)->completeLesson($learner, $lesson);
    }

    $certificate = Certificate::query()->where('user_id', $learner->id)->firstOrFail();

    $response = $this->actingAs($learner)
        ->get(route('learning.certificates.download', $certificate));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('reminds learners when a certificate is near expiry', function () {
    Notification::fake();

    $author = certAuthor();
    $learner = certLearner();
    [$course, $lessons] = certTwoLessonCourse($author);

    app(EnrolmentService::class)->enrolManual($course, $learner, $author);
    foreach ($lessons as $lesson) {
        app(ProgressService::class)->completeLesson($learner, $lesson);
    }

    $certificate = Certificate::query()->where('user_id', $learner->id)->firstOrFail();
    $certificate->forceFill([
        'expires_on' => now()->addDays(10)->toDateString(),
        'expiry_reminded_at' => null,
    ])->save();

    $this->artisan('learning:remind-certificate-expiry', ['--days' => 30])
        ->assertSuccessful();

    expect($certificate->fresh()->expiry_reminded_at)->not->toBeNull();

    Notification::assertSentTo($learner, IntranetAlert::class, function (IntranetAlert $alert) {
        return ! str_contains(strtolower($alert->title.' '.$alert->body), 'grade');
    });
});

it('expires certificates, clears completion and keeps enrolment active without grade content', function () {
    Notification::fake();

    $author = certAuthor();
    $learner = certLearner();
    [$course, $lessons] = certTwoLessonCourse($author);

    app(EnrolmentService::class)->enrolManual($course, $learner, $author);
    foreach ($lessons as $lesson) {
        app(ProgressService::class)->completeLesson($learner, $lesson);
    }

    $certificate = Certificate::query()->where('user_id', $learner->id)->firstOrFail();
    $certificate->forceFill([
        'expires_on' => now()->subDay()->toDateString(),
        'expiry_reminded_at' => now(),
    ])->save();

    $this->artisan('learning:remind-certificate-expiry')->assertSuccessful();

    $certificate->refresh();

    expect($certificate->revoked_at)->not->toBeNull()
        ->and(CourseCompletion::query()->where('user_id', $learner->id)->where('course_id', $course->id)->exists())->toBeFalse()
        ->and(Enrolment::query()->where('user_id', $learner->id)->where('course_id', $course->id)->where('status', Enrolment::STATUS_ACTIVE)->exists())->toBeTrue();

    Notification::assertSentTo($learner, IntranetAlert::class);
});
