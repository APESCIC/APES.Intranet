<?php

use Illuminate\Support\Facades\Route;
use Modules\Learning\Http\Controllers\CourseAuthoringController;
use Modules\Learning\Http\Controllers\CoursePlayerController;
use Modules\Learning\Http\Controllers\EnrolmentController;
use Modules\Learning\Http\Controllers\MyLearningController;
use Modules\Learning\LearningPermissions;

Route::middleware(['web', 'auth', 'can:'.LearningPermissions::ACCESS, 'module:learning'])
    ->prefix('learning')
    ->name('learning.')
    ->group(function () {
        Route::get('/', MyLearningController::class)->name('mine');

        Route::get('/play/{course}', [CoursePlayerController::class, 'show'])->name('play.show');
        Route::get('/play/{course}/lessons/{lesson}', [CoursePlayerController::class, 'lesson'])->name('play.lesson');
        Route::post('/play/{course}/lessons/{lesson}/complete', [CoursePlayerController::class, 'complete'])->name('play.complete');

        Route::get('/courses', [CourseAuthoringController::class, 'index'])->name('courses.index');
        Route::get('/courses/create', [CourseAuthoringController::class, 'create'])->name('courses.create');
        Route::post('/courses', [CourseAuthoringController::class, 'store'])->name('courses.store');
        Route::get('/courses/{course}', [CourseAuthoringController::class, 'show'])->name('courses.show');
        Route::post('/courses/{course}/sections', [CourseAuthoringController::class, 'storeSection'])->name('courses.sections.store');
        Route::post('/courses/{course}/sections/{section}/lessons', [CourseAuthoringController::class, 'storeLesson'])->name('courses.sections.lessons.store');
        Route::get('/courses/{course}/lessons/{lesson}/edit', [CourseAuthoringController::class, 'editLesson'])->name('courses.lessons.edit');
        Route::put('/courses/{course}/lessons/{lesson}', [CourseAuthoringController::class, 'updateLesson'])->name('courses.lessons.update');
        Route::post('/courses/{course}/lessons/{lesson}/publish', [CourseAuthoringController::class, 'publishLesson'])->name('courses.lessons.publish');

        Route::get('/courses/{course}/enrolments', [EnrolmentController::class, 'index'])->name('courses.enrolments.index');
        Route::post('/courses/{course}/enrolments/manual', [EnrolmentController::class, 'storeManual'])->name('courses.enrolments.manual');
        Route::post('/courses/{course}/enrolments/group-rules', [EnrolmentController::class, 'storeGroupRule'])->name('courses.enrolments.group-rules');
        Route::post('/courses/{course}/enrolments/prerequisites', [EnrolmentController::class, 'storePrerequisite'])->name('courses.enrolments.prerequisites');
        Route::post('/courses/{course}/enrolments/sync', [EnrolmentController::class, 'sync'])->name('courses.enrolments.sync');
        Route::post('/courses/{course}/self-enrol', [EnrolmentController::class, 'selfEnrol'])->name('courses.self-enrol');
    });
