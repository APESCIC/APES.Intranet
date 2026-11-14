<?php

it('keeps an approved HR records DPIA in the repository', function () {
    $path = base_path('docs/dpia/hr-records.md');

    expect(file_exists($path))->toBeTrue();

    $dpia = file_get_contents($path);

    expect($dpia)->toContain('Approved by the owner on 14 November 2026')
        ->and($dpia)->toContain('DBS')
        ->and($dpia)->toContain('right-to-work')
        ->and($dpia)->toContain('appraisals')
        ->and($dpia)->toContain('Not SharePoint')
        ->and($dpia)->toContain('Do **not** claim SOC 2');
});

it('sets HR retention periods in config with safe minimums', function () {
    $hr = config('intranet.retention.hr');

    expect($hr)->toBeArray()
        ->and($hr['record_years_after_leave'])->toBeGreaterThanOrEqual(1)
        ->and($hr['dbs_years_after_leave'])->toBeGreaterThanOrEqual(1)
        ->and($hr['right_to_work_years_after_leave'])->toBeGreaterThanOrEqual(1)
        ->and($hr['leave_years'])->toBeGreaterThanOrEqual(1)
        ->and($hr['appraisal_years_after_leave'])->toBeGreaterThanOrEqual(1)
        ->and($hr['training_years_after_leave'])->toBeGreaterThanOrEqual(1)
        ->and($hr['checklist_years_after_leave'])->toBeGreaterThanOrEqual(1)
        ->and($hr['recognition_years_after_leave'])->toBeGreaterThanOrEqual(1);
});

it('lists the HR DPIA from the docs index', function () {
    $readme = file_get_contents(base_path('docs/README.md'));

    expect($readme)->toContain('dpia/hr-records.md');
});
