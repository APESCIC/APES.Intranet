<?php

namespace Modules\Learning\Gradebook;

use InvalidArgumentException;

/**
 * Pure gradebook maths: weighted categories, missing/zero/exempt, attempt
 * policies, resits, and half-away-from-zero rounding to 2 decimal places.
 * Uses BCMath strings for unrounded storage.
 */
final class GradebookCalculator
{
    private const SCALE = 12;

    /**
     * @param  list<GradeCategoryInput>  $categories
     */
    public function calculate(array $categories, ?float $passThreshold = 50.0): GradebookResult
    {
        $this->assertWeights($categories);

        $categoryPercents = [];
        $weightedSum = '0';
        $weightUsed = '0';

        foreach ($categories as $category) {
            $percent = $this->categoryPercent($category);
            $categoryPercents[$category->id] = $percent;

            if ($percent === null) {
                // Empty / all-exempt category drops out; remaining weights re-normalise.
                continue;
            }

            $w = $this->num((string) $category->weight);
            $weightedSum = bcadd($weightedSum, bcmul($percent, $w, self::SCALE), self::SCALE);
            $weightUsed = bcadd($weightUsed, $w, self::SCALE);
        }

        if (bccomp($weightUsed, '0', self::SCALE) === 0) {
            $course = '0';
        } else {
            // sum(percent * weight) / sum(weights used) → course percent 0–100.
            $course = bcdiv($weightedSum, $weightUsed, self::SCALE);
        }

        $rounded = $this->roundHalfAwayFromZero($course, 2);
        $passed = $passThreshold === null
            ? true
            : bccomp($course, $this->num((string) $passThreshold), self::SCALE) >= 0;

        return new GradebookResult(
            coursePercentUnrounded: $course,
            coursePercentRounded: $rounded,
            categoryPercentsUnrounded: array_map(
                static fn (?string $p) => $p ?? '0',
                $categoryPercents,
            ),
            passed: $passed,
        );
    }

    /**
     * @param  list<GradeCategoryInput>  $categories
     */
    public function assertWeights(array $categories): void
    {
        $sum = '0';
        foreach ($categories as $category) {
            if ($category->weight < 0) {
                throw new InvalidArgumentException('Category weights cannot be negative.');
            }
            $sum = bcadd($sum, $this->num((string) $category->weight), self::SCALE);
        }

        if (bccomp($sum, '100', 4) !== 0) {
            throw new InvalidArgumentException('Category weights must total 100.');
        }
    }

    public function roundHalfAwayFromZero(string $value, int $places): string
    {
        $negative = bccomp($value, '0', self::SCALE) < 0;
        $abs = $negative ? bcmul($value, '-1', self::SCALE) : $value;
        $factor = bcpow('10', (string) $places, 0);
        $scaled = bcmul($abs, $factor, self::SCALE);
        $whole = bcadd($scaled, '0', 0);
        $fraction = bcsub($scaled, $whole, self::SCALE);

        if (bccomp($fraction, '0.5', self::SCALE) >= 0) {
            $whole = bcadd($whole, '1', 0);
        }

        $rounded = bcdiv($whole, $factor, $places);

        return $negative ? bcmul($rounded, '-1', $places) : $rounded;
    }

    private function categoryPercent(GradeCategoryInput $category): ?string
    {
        $earned = '0';
        $possible = '0';
        $counted = 0;

        foreach ($category->items as $item) {
            $contribution = $this->itemContribution($item);
            if ($contribution === null) {
                continue; // exempt or still-missing
            }

            [$itemEarned, $itemMax] = $contribution;
            $earned = bcadd($earned, $itemEarned, self::SCALE);
            $possible = bcadd($possible, $itemMax, self::SCALE);
            $counted++;
        }

        if ($counted === 0 || bccomp($possible, '0', self::SCALE) === 0) {
            return null;
        }

        return bcmul(bcdiv($earned, $possible, self::SCALE), '100', self::SCALE);
    }

    /**
     * @return array{0: string, 1: string}|null earned, max — null means exclude from total
     */
    private function itemContribution(GradeItemInput $item): ?array
    {
        if ($item->status === 'exempt') {
            return null;
        }

        $max = $this->num((string) $item->maxPoints);
        if (bccomp($max, '0', self::SCALE) <= 0) {
            throw new InvalidArgumentException("Grade item {$item->id} needs a positive max.");
        }

        if ($item->status === 'zero') {
            return ['0', $max];
        }

        if ($item->status === 'missing') {
            if ($item->missingPolicy === 'zero_after_due' && $item->duePassed) {
                return ['0', $max];
            }

            return null;
        }

        $score = $this->resolveAttemptScore($item);
        if ($score === null) {
            if ($item->missingPolicy === 'zero_after_due' && $item->duePassed) {
                return ['0', $max];
            }

            return null;
        }

        if ($item->resitScore !== null && $item->resitScore > $score) {
            $score = $item->resitScore;
        }

        $earned = $this->num((string) $score);
        if (bccomp($earned, $max, self::SCALE) > 0) {
            $earned = $max;
        }
        if (bccomp($earned, '0', self::SCALE) < 0) {
            $earned = '0';
        }

        return [$earned, $max];
    }

    private function resolveAttemptScore(GradeItemInput $item): ?float
    {
        $scores = array_values(array_filter(
            $item->attempts,
            static fn ($s) => $s !== null,
        ));

        if ($scores === []) {
            return null;
        }

        return match ($item->attemptPolicy) {
            'best' => max($scores),
            'average' => array_sum($scores) / count($scores),
            default => $scores[array_key_last($scores)],
        };
    }

    private function num(string $value): string
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException('Expected a numeric value.');
        }

        return bcadd($value, '0', self::SCALE);
    }
}
