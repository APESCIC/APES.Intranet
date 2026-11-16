<?php

use Modules\Learning\Gradebook\GradebookCalculator;
use Modules\Learning\Gradebook\GradeCategoryInput;
use Modules\Learning\Gradebook\GradeItemInput;

it('rejects category weights that do not total 100', function () {
    $calc = new GradebookCalculator;

    expect(fn () => $calc->calculate([
        new GradeCategoryInput('a', 40, [
            new GradeItemInput('1', 10, attempts: [10]),
        ]),
        new GradeCategoryInput('b', 50, [
            new GradeItemInput('2', 10, attempts: [10]),
        ]),
    ]))->toThrow(InvalidArgumentException::class);
});

it('removes exempt items from the category total', function () {
    $calc = new GradebookCalculator;

    $result = $calc->calculate([
        new GradeCategoryInput('core', 100, [
            new GradeItemInput('quiz', 10, attempts: [10]),
            new GradeItemInput('essay', 10, status: 'exempt'),
        ]),
    ]);

    // Only quiz counts: 10/10 = 100%
    expect($result->coursePercentRounded)->toBe('100.00')
        ->and($result->coursePercentUnrounded)->toStartWith('100');
});

it('treats missing as excluded until due-date policy forces zero', function () {
    $calc = new GradebookCalculator;

    $beforeDue = $calc->calculate([
        new GradeCategoryInput('core', 100, [
            new GradeItemInput('quiz', 10, attempts: [8]),
            new GradeItemInput('hw', 10, status: 'missing', missingPolicy: 'zero_after_due', duePassed: false),
        ]),
    ]);
    expect($beforeDue->coursePercentRounded)->toBe('80.00');

    $afterDue = $calc->calculate([
        new GradeCategoryInput('core', 100, [
            new GradeItemInput('quiz', 10, attempts: [8]),
            new GradeItemInput('hw', 10, status: 'missing', missingPolicy: 'zero_after_due', duePassed: true),
        ]),
    ]);
    // 8/20 = 40%
    expect($afterDue->coursePercentRounded)->toBe('40.00');
});

it('supports best, latest and average attempt policies and resits', function () {
    $calc = new GradebookCalculator;

    $best = $calc->calculate([
        new GradeCategoryInput('c', 100, [
            new GradeItemInput('q', 10, attempts: [4, 9, 6], attemptPolicy: 'best'),
        ]),
    ]);
    expect($best->coursePercentRounded)->toBe('90.00');

    $latest = $calc->calculate([
        new GradeCategoryInput('c', 100, [
            new GradeItemInput('q', 10, attempts: [4, 9, 6], attemptPolicy: 'latest'),
        ]),
    ]);
    expect($latest->coursePercentRounded)->toBe('60.00');

    $average = $calc->calculate([
        new GradeCategoryInput('c', 100, [
            new GradeItemInput('q', 10, attempts: [4, 6], attemptPolicy: 'average'),
        ]),
    ]);
    expect($average->coursePercentRounded)->toBe('50.00');

    $resit = $calc->calculate([
        new GradeCategoryInput('c', 100, [
            new GradeItemInput('q', 10, attempts: [4], attemptPolicy: 'latest', resitScore: 9),
        ]),
    ]);
    expect($resit->coursePercentRounded)->toBe('90.00');
});

it('weights categories and rounds half away from zero to 2dp', function () {
    $calc = new GradebookCalculator;

    $result = $calc->calculate([
        new GradeCategoryInput('exams', 60, [
            new GradeItemInput('mid', 50, attempts: [40]), // 80%
        ]),
        new GradeCategoryInput('coursework', 40, [
            new GradeItemInput('cw', 20, attempts: [15]), // 75%
        ]),
    ], passThreshold: 50);

    // 80*0.6 + 75*0.4 = 48 + 30 = 78
    expect($result->coursePercentRounded)->toBe('78.00')
        ->and($result->passed)->toBeTrue();

    // 1.225 → 1.23 (half away from zero)
    expect($calc->roundHalfAwayFromZero('1.225', 2))->toBe('1.23')
        ->and($calc->roundHalfAwayFromZero('-1.225', 2))->toBe('-1.23')
        ->and($calc->roundHalfAwayFromZero('1.224', 2))->toBe('1.22');
});

it('treats explicit zero differently from missing', function () {
    $calc = new GradebookCalculator;

    $zero = $calc->calculate([
        new GradeCategoryInput('c', 100, [
            new GradeItemInput('a', 10, attempts: [10]),
            new GradeItemInput('b', 10, status: 'zero'),
        ]),
    ]);
    expect($zero->coursePercentRounded)->toBe('50.00');

    $missing = $calc->calculate([
        new GradeCategoryInput('c', 100, [
            new GradeItemInput('a', 10, attempts: [10]),
            new GradeItemInput('b', 10, status: 'missing'),
        ]),
    ]);
    expect($missing->coursePercentRounded)->toBe('100.00');
});
