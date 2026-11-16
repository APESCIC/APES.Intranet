<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\LearningPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Learning\LearningPermissions;
use Modules\Learning\LearningRole;
use Modules\Learning\LearningScope;
use Modules\Learning\Models\Course;
use Modules\Learning\Services\AssignmentSubmissionService;
use Modules\Learning\Services\CourseAuthoringService;
use Modules\Learning\Services\EnrolmentService;
use Modules\Learning\Services\LearningAssignmentService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LearningPermissionsSeeder::class);
    Storage::fake('local');
});

function assignmentAuthor(): User
{
    $user = User::factory()->create();
    $user->assignRole(RoleName::Admin->value);
    $user->givePermissionTo([
        LearningPermissions::ACCESS,
        LearningPermissions::COURSE_AUTHOR,
        LearningPermissions::ENROL_MANAGE,
        LearningPermissions::ASSIGNMENTS_MANAGE,
        LearningPermissions::GRADE_VIEW,
    ]);

    return $user;
}

function assignmentLearner(): User
{
    $user = User::factory()->create();
    $user->assignRole(RoleName::Staff->value);
    $user->givePermissionTo(LearningPermissions::ACCESS);

    return $user;
}

it('rejects disallowed file types and oversized files', function () {
    $author = assignmentAuthor();
    $learner = assignmentLearner();
    $course = app(CourseAuthoringService::class)->createCourse(['title' => 'Assign course'], $author);
    $course->update(['status' => Course::STATUS_PUBLISHED]);

    $service = app(AssignmentSubmissionService::class);
    $assignment = $service->createAssignment($course, [
        'title' => 'Essay upload',
        'allow_text' => false,
        'allow_file' => true,
        'allowed_extensions' => ['pdf'],
        'max_file_bytes' => 2048, // UploadedFile::fake size arg is kilobytes
    ], $author);
    $service->publishAssignment($assignment, $author);
    app(EnrolmentService::class)->enrolManual($course, $learner, $author);

    $exe = UploadedFile::fake()->create('malware.exe', 1, 'application/octet-stream');
    expect(fn () => $service->submit($assignment, $learner, null, $exe))
        ->toThrow(InvalidArgumentException::class);

    $big = UploadedFile::fake()->create('paper.pdf', 5, 'application/pdf'); // ~5 KiB
    expect(fn () => $service->submit($assignment, $learner, null, $big))
        ->toThrow(InvalidArgumentException::class);

    $ok = UploadedFile::fake()->create('paper.pdf', 1, 'application/pdf'); // ~1 KiB
    $submission = $service->submit($assignment, $learner, null, $ok);
    expect($submission->version_number)->toBe(1)
        ->and($submission->stored_path)->toStartWith('learning/submissions/')
        ->and(Storage::disk('local')->exists($submission->stored_path))->toBeTrue();
});

it('keeps every submission version on resubmit', function () {
    $author = assignmentAuthor();
    $learner = assignmentLearner();
    $course = app(CourseAuthoringService::class)->createCourse(['title' => 'Versioned'], $author);
    $course->update(['status' => Course::STATUS_PUBLISHED]);

    $service = app(AssignmentSubmissionService::class);
    $assignment = $service->createAssignment($course, [
        'title' => 'Text only',
        'allow_text' => true,
        'allow_file' => false,
    ], $author);
    $service->publishAssignment($assignment, $author);
    app(EnrolmentService::class)->enrolManual($course, $learner, $author);

    $v1 = $service->submit($assignment, $learner, '<p>First draft</p>');
    $v2 = $service->submit($assignment, $learner, '<p>Second draft</p>');
    $versions = $service->versions($assignment, $learner);

    expect($v1->version_number)->toBe(1)
        ->and($v2->version_number)->toBe(2)
        ->and($versions)->toHaveCount(2)
        ->and($versions->pluck('text_body')->all())->toContain('<p>First draft</p>', '<p>Second draft</p>');
});

it('hides feedback from the learner until release', function () {
    $author = assignmentAuthor();
    $learner = assignmentLearner();
    $marker = User::factory()->create();
    $marker->assignRole(RoleName::Staff->value);
    $marker->givePermissionTo([LearningPermissions::ACCESS, LearningPermissions::GRADE_VIEW]);

    $course = app(CourseAuthoringService::class)->createCourse(['title' => 'Feedback course'], $author);
    $course->update(['status' => Course::STATUS_PUBLISHED]);

    $service = app(AssignmentSubmissionService::class);
    $assignment = $service->createAssignment($course, [
        'title' => 'Reflection',
        'allow_text' => true,
        'allow_file' => false,
    ], $author);
    $service->publishAssignment($assignment, $author);
    app(EnrolmentService::class)->enrolManual($course, $learner, $author);
    app(LearningAssignmentService::class)->assign(
        $marker,
        LearningRole::Assessor,
        LearningScope::Course,
        $course->id,
        $author,
    );

    $approver = User::factory()->create();
    $approver->assignRole(RoleName::Staff->value);
    $approver->givePermissionTo([LearningPermissions::ACCESS, LearningPermissions::GRADE_VIEW]);
    app(LearningAssignmentService::class)->assign(
        $approver,
        LearningRole::GradeApprover,
        LearningScope::Course,
        $course->id,
        $author,
    );

    $service->submit($assignment, $learner, '<p>My work</p>');
    $feedback = $service->writeFeedback($assignment, $learner, '<p>Solid effort</p>', $marker);

    expect($feedback->isReleased())->toBeFalse()
        ->and($service->feedbackForLearner($assignment, $learner))->toBeNull();

    $this->actingAs($learner)
        ->get(route('learning.play.assignment', [$course, $assignment]))
        ->assertOk()
        ->assertSee('No released feedback yet')
        ->assertDontSee('Solid effort');

    $service->releaseFeedback($feedback, $approver);

    expect($service->feedbackForLearner($assignment, $learner)?->body)->toContain('Solid effort');

    $this->actingAs($learner)
        ->get(route('learning.play.assignment', [$course, $assignment]))
        ->assertOk()
        ->assertSee('Solid effort');
});
