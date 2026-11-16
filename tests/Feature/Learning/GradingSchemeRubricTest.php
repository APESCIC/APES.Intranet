<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Models\User;
use Database\Seeders\LearningPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Learning\GradingSchemeType;
use Modules\Learning\LearningPermissions;
use Modules\Learning\Services\CourseAuthoringService;
use Modules\Learning\Services\GradingSchemeService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(LearningPermissionsSeeder::class);
});

function schemeAuthor(): User
{
    $user = User::factory()->create();
    $user->assignRole(RoleName::Admin->value);
    $user->givePermissionTo([
        LearningPermissions::ACCESS,
        LearningPermissions::COURSE_AUTHOR,
    ]);

    return $user;
}

it('matches rubric fixture totals from criterion level maxima', function () {
    $author = schemeAuthor();
    $course = app(CourseAuthoringService::class)->createCourse(['title' => 'Scheme course'], $author);
    $service = app(GradingSchemeService::class);

    // Fixture: Writing (0/2/4) + Content (1/3/5) + Presentation (0/2) => 4+5+2 = 11
    $rubric = $service->createRubric($course, [
        'title' => 'Essay rubric',
        'criteria' => [
            [
                'title' => 'Writing',
                'levels' => [
                    ['label' => 'Weak', 'points' => 0],
                    ['label' => 'OK', 'points' => 2],
                    ['label' => 'Strong', 'points' => 4],
                ],
            ],
            [
                'title' => 'Content',
                'levels' => [
                    ['label' => 'Thin', 'points' => 1],
                    ['label' => 'Sound', 'points' => 3],
                    ['label' => 'Excellent', 'points' => 5],
                ],
            ],
            [
                'title' => 'Presentation',
                'levels' => [
                    ['label' => 'Untidy', 'points' => 0],
                    ['label' => 'Clear', 'points' => 2],
                ],
            ],
        ],
    ], $author);

    expect($service->rubricTotalPoints($rubric))->toBe(11.0);

    $selection = [];
    foreach ($rubric->criteria as $criterion) {
        $selection[$criterion->id] = $criterion->levels->sortByDesc('points')->first()->id;
    }
    expect($service->scoreRubricSelection($rubric, $selection))->toBe(11.0);

    $mid = [];
    foreach ($rubric->criteria as $criterion) {
        $mid[$criterion->id] = $criterion->levels->sortBy('points')->values()->get(1)?->id
            ?? $criterion->levels->first()->id;
    }
    // Writing OK(2) + Content Sound(3) + Presentation Clear(2) = 7
    expect($service->scoreRubricSelection($rubric, $mid))->toBe(7.0);
});

it('requires confirmation and audits scheme changes after marking', function () {
    $author = schemeAuthor();
    $learner = User::factory()->create();
    $learner->assignRole(RoleName::Staff->value);
    $learner->givePermissionTo(LearningPermissions::ACCESS);

    $course = app(CourseAuthoringService::class)->createCourse(['title' => 'Marked course'], $author);
    $service = app(GradingSchemeService::class);

    $points = $service->createScheme($course, [
        'name' => 'Out of 20',
        'type' => GradingSchemeType::Points,
        'max_points' => 20,
        'pass_threshold' => 10,
    ], $author);

    $percent = $service->createScheme($course, [
        'name' => 'Percent',
        'type' => GradingSchemeType::Percentage,
        'pass_threshold' => 50,
    ], $author);

    $passFail = $service->createScheme($course, [
        'name' => 'Pass fail',
        'type' => GradingSchemeType::PassFail,
    ], $author);

    expect($points->type)->toBe(GradingSchemeType::Points)
        ->and($percent->type)->toBe(GradingSchemeType::Percentage)
        ->and($passFail->type)->toBe(GradingSchemeType::PassFail);

    $item = $service->createGradeItem($course, 'Quiz 1', $points);
    $service->recordMark($item, $learner, 15.0, $author, true);

    expect(fn () => $service->changeScheme($item, $percent, $author, confirmed: false))
        ->toThrow(RuntimeException::class);

    $changed = $service->changeScheme($item->fresh(), $percent, $author, confirmed: true);
    expect($changed->scheme_id)->toBe($percent->id);

    $audit = AuditLog::query()
        ->where('event', 'learning.scheme.changed')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->context['from_scheme_id'])->toBe($points->id)
        ->and($audit->context['to_scheme_id'])->toBe($percent->id)
        ->and($audit->context['had_marks'])->toBeTrue()
        ->and($audit->context['confirmed'])->toBeTrue();
});
