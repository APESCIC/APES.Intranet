<?php

it('keeps a Learning records DPIA in the repository', function () {
    $path = base_path('docs/dpia/learning-records.md');

    expect(file_exists($path))->toBeTrue();

    $dpia = file_get_contents($path);

    expect($dpia)->toContain('Approved by the owner on 16 November 2026')
        ->and($dpia)->toContain('minors')
        ->and($dpia)->toContain('progress')
        ->and($dpia)->toContain('grades')
        ->and($dpia)->toContain('Not SharePoint')
        ->and($dpia)->toContain('Do **not** claim SOC 2')
        ->and($dpia)->toContain('retention.learning')
        ->and($dpia)->toContain('Step 7: Sign-off and outcomes');
});

it('points the older learning DPIA path at the repository DPIA', function () {
    $pointer = base_path('docs/learning/dpia.md');

    expect(file_exists($pointer))->toBeTrue()
        ->and(file_get_contents($pointer))->toContain('docs/dpia/learning-records.md');
});

it('sets Learning retention periods in config with safe minimums', function () {
    $learning = config('intranet.retention.learning');

    expect($learning)->toBeArray()
        ->and($learning['enrolment_years_after_end'])->toBeGreaterThanOrEqual(1)
        ->and($learning['progress_years_after_end'])->toBeGreaterThanOrEqual(1)
        ->and($learning['submission_years_after_end'])->toBeGreaterThanOrEqual(1)
        ->and($learning['grade_years_after_end'])->toBeGreaterThanOrEqual(1)
        ->and($learning['assignment_years_after_end'])->toBeGreaterThanOrEqual(1)
        ->and($learning['course_version_years_after_archive'])->toBeGreaterThanOrEqual(1);
});

it('lists the Learning DPIA from the docs index', function () {
    $readme = file_get_contents(base_path('docs/README.md'));

    expect($readme)->toContain('dpia/learning-records.md');
});
