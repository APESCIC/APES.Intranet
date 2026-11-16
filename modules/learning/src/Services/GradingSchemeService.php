<?php

namespace Modules\Learning\Services;

use App\Core\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Learning\GradeReleaseState;
use Modules\Learning\GradingSchemeType;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\GradeItem;
use Modules\Learning\Models\GradeMark;
use Modules\Learning\Models\GradingScheme;
use Modules\Learning\Models\Rubric;
use Modules\Learning\Models\RubricCriterion;
use RuntimeException;

class GradingSchemeService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{
     *   name: string,
     *   type: string|GradingSchemeType,
     *   max_points?: ?float,
     *   pass_threshold?: ?float
     * }  $data
     */
    public function createScheme(Course $course, array $data, User $author): GradingScheme
    {
        $type = $data['type'] instanceof GradingSchemeType
            ? $data['type']
            : GradingSchemeType::from((string) $data['type']);

        $max = isset($data['max_points']) ? (float) $data['max_points'] : null;
        $pass = isset($data['pass_threshold']) ? (float) $data['pass_threshold'] : null;

        match ($type) {
            GradingSchemeType::Points => $this->assertPoints($max, $pass),
            GradingSchemeType::Percentage => $this->assertPercentage($pass),
            GradingSchemeType::PassFail => null,
        };

        $scheme = GradingScheme::query()->create([
            'course_id' => $course->id,
            'name' => trim($data['name']),
            'type' => $type,
            'max_points' => $type === GradingSchemeType::Points ? $max : null,
            'pass_threshold' => $type === GradingSchemeType::PassFail ? 1.0 : $pass,
            'created_by' => $author->id,
        ]);

        $this->audit->record('learning.scheme.created', $scheme, [
            'course_id' => $course->id,
            'type' => $type->value,
        ], $author);

        return $scheme;
    }

    /**
     * @param  array{
     *   title: string,
     *   description?: ?string,
     *   criteria: list<array{title: string, levels: list<array{label: string, points: float|int}>}>
     * }  $data
     */
    public function createRubric(Course $course, array $data, User $author): Rubric
    {
        if ($data['criteria'] === []) {
            throw new InvalidArgumentException('A rubric needs at least one criterion.');
        }

        return DB::transaction(function () use ($course, $data, $author): Rubric {
            $rubric = Rubric::query()->create([
                'course_id' => $course->id,
                'title' => trim($data['title']),
                'description' => $data['description'] ?? null,
                'created_by' => $author->id,
            ]);

            foreach ($data['criteria'] as $cIndex => $criterionData) {
                if ($criterionData['levels'] === []) {
                    throw new InvalidArgumentException('Each criterion needs at least one level.');
                }

                /** @var RubricCriterion $criterion */
                $criterion = $rubric->criteria()->create([
                    'title' => trim($criterionData['title']),
                    'sort_order' => $cIndex + 1,
                ]);

                foreach ($criterionData['levels'] as $lIndex => $level) {
                    $criterion->levels()->create([
                        'label' => trim($level['label']),
                        'points' => (float) $level['points'],
                        'sort_order' => $lIndex + 1,
                    ]);
                }
            }

            $this->audit->record('learning.rubric.created', $rubric, [
                'course_id' => $course->id,
                'total_points' => $this->rubricTotalPoints($rubric->fresh('criteria.levels')),
            ], $author);

            return $rubric->fresh('criteria.levels');
        });
    }

    /**
     * Sum of each criterion's highest level points (fixture total).
     */
    public function rubricTotalPoints(Rubric $rubric): float
    {
        $rubric->loadMissing('criteria.levels');
        $total = 0.0;
        foreach ($rubric->criteria as $criterion) {
            $total += (float) $criterion->levels->max('points');
        }

        return round($total, 4);
    }

    /**
     * @param  array<int, int>  $levelIdsByCriterion  criterion_id => level_id
     */
    public function scoreRubricSelection(Rubric $rubric, array $levelIdsByCriterion): float
    {
        $rubric->loadMissing('criteria.levels');
        $total = 0.0;

        foreach ($rubric->criteria as $criterion) {
            $levelId = $levelIdsByCriterion[$criterion->id] ?? null;
            if ($levelId === null) {
                throw new InvalidArgumentException("Missing level for criterion {$criterion->id}.");
            }
            $level = $criterion->levels->firstWhere('id', $levelId);
            if ($level === null) {
                throw new InvalidArgumentException("Level {$levelId} is not on criterion {$criterion->id}.");
            }
            $total += (float) $level->points;
        }

        return round($total, 4);
    }

    public function createGradeItem(
        Course $course,
        string $title,
        ?GradingScheme $scheme = null,
        ?Rubric $rubric = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?float $weight = null,
    ): GradeItem {
        if ($scheme !== null && $scheme->course_id !== $course->id) {
            throw new InvalidArgumentException('Scheme must belong to the course.');
        }
        if ($rubric !== null && $rubric->course_id !== $course->id) {
            throw new InvalidArgumentException('Rubric must belong to the course.');
        }

        return GradeItem::query()->create([
            'course_id' => $course->id,
            'title' => $title,
            'scheme_id' => $scheme?->id,
            'rubric_id' => $rubric?->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'weight' => $weight,
        ]);
    }

    public function recordMark(GradeItem $item, User $learner, float $rawScore, User $marker, ?bool $passed = null): GradeMark
    {
        return GradeMark::query()->updateOrCreate(
            [
                'grade_item_id' => $item->id,
                'user_id' => $learner->id,
            ],
            [
                'raw_score' => $rawScore,
                'passed' => $passed,
                'release_state' => GradeReleaseState::Draft,
                'marked_by' => $marker->id,
                'marked_at' => now(),
            ],
        );
    }

    /**
     * Changing scheme after marking needs explicit confirmation and is audited.
     */
    public function changeScheme(GradeItem $item, GradingScheme $scheme, User $actor, bool $confirmed = false): GradeItem
    {
        if ($scheme->course_id !== $item->course_id) {
            throw new InvalidArgumentException('Scheme must belong to the same course.');
        }

        if ($item->scheme_id === $scheme->id) {
            return $item;
        }

        if ($item->hasMarks() && ! $confirmed) {
            throw new RuntimeException(
                'This grade item already has marks. Confirm the scheme change to continue.'
            );
        }

        $from = $item->scheme_id;
        $item->update(['scheme_id' => $scheme->id]);

        $this->audit->record('learning.scheme.changed', $item, [
            'from_scheme_id' => $from,
            'to_scheme_id' => $scheme->id,
            'had_marks' => $item->hasMarks(),
            'confirmed' => $confirmed,
        ], $actor);

        return $item->refresh();
    }

    private function assertPoints(?float $max, ?float $pass): void
    {
        if ($max === null || $max <= 0) {
            throw new InvalidArgumentException('Points schemes need a positive max_points.');
        }
        if ($pass !== null && ($pass < 0 || $pass > $max)) {
            throw new InvalidArgumentException('Pass threshold must be within max points.');
        }
    }

    private function assertPercentage(?float $pass): void
    {
        if ($pass !== null && ($pass < 0 || $pass > 100)) {
            throw new InvalidArgumentException('Percentage pass threshold must be between 0 and 100.');
        }
    }
}
