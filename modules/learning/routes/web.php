<?php

use Illuminate\Support\Facades\Route;
use Modules\Learning\Http\Controllers\AssignmentController;
use Modules\Learning\Http\Controllers\CourseAuthoringController;
use Modules\Learning\Http\Controllers\CoursePlayerController;
use Modules\Learning\Http\Controllers\EnrolmentController;
use Modules\Learning\Http\Controllers\MyLearningController;
use Modules\Learning\Http\Controllers\QuizAttemptController;
use Modules\Learning\Http\Controllers\QuizAuthoringController;
use Modules\Learning\LearningPermissions;

Route::middleware(['web', 'auth', 'can:'.LearningPermissions::ACCESS, 'module:learning'])
    ->prefix('learning')
    ->name('learning.')
    ->group(function () {
        Route::get('/', MyLearningController::class)->name('mine');

        Route::get('/play/{course}', [CoursePlayerController::class, 'show'])->name('play.show');
        Route::get('/play/{course}/lessons/{lesson}', [CoursePlayerController::class, 'lesson'])->name('play.lesson');
        Route::post('/play/{course}/lessons/{lesson}/complete', [CoursePlayerController::class, 'complete'])->name('play.complete');
        Route::get('/play/{course}/quizzes/{quiz}', [QuizAttemptController::class, 'show'])->name('play.quiz');
        Route::post('/play/{course}/quizzes/{quiz}/start', [QuizAttemptController::class, 'start'])->name('play.quiz.start');
        Route::get('/play/{course}/quizzes/{quiz}/attempts/{attempt}', [QuizAttemptController::class, 'attempt'])->name('play.quiz.attempt');
        Route::post('/play/{course}/quizzes/{quiz}/attempts/{attempt}', [QuizAttemptController::class, 'submit'])->name('play.quiz.submit');
        Route::get('/play/{course}/assignments/{assignment}', [AssignmentController::class, 'show'])->name('play.assignment');
        Route::post('/play/{course}/assignments/{assignment}/submit', [AssignmentController::class, 'submit'])->name('play.assignment.submit');
        Route::get('/play/{course}/assignments/{assignment}/submissions/{submission}/download', [AssignmentController::class, 'download'])->name('play.assignment.download');

        Route::get('/courses', [CourseAuthoringController::class, 'index'])->name('courses.index');
        Route::get('/courses/create', [CourseAuthoringController::class, 'create'])->name('courses.create');
        Route::post('/courses', [CourseAuthoringController::class, 'store'])->name('courses.store');
        Route::get('/courses/{course}', [CourseAuthoringController::class, 'show'])->name('courses.show');
        Route::post('/courses/{course}/sections', [CourseAuthoringController::class, 'storeSection'])->name('courses.sections.store');
        Route::post('/courses/{course}/sections/{section}/lessons', [CourseAuthoringController::class, 'storeLesson'])->name('courses.sections.lessons.store');
        Route::get('/courses/{course}/lessons/{lesson}/edit', [CourseAuthoringController::class, 'editLesson'])->name('courses.lessons.edit');
        Route::put('/courses/{course}/lessons/{lesson}', [CourseAuthoringController::class, 'updateLesson'])->name('courses.lessons.update');
        Route::post('/courses/{course}/lessons/{lesson}/publish', [CourseAuthoringController::class, 'publishLesson'])->name('courses.lessons.publish');

        Route::get('/courses/{course}/assignments', [AssignmentController::class, 'index'])->name('courses.assignments.index');
        Route::post('/courses/{course}/assignments', [AssignmentController::class, 'store'])->name('courses.assignments.store');
        Route::post('/courses/{course}/assignments/{assignment}/publish', [AssignmentController::class, 'publish'])->name('courses.assignments.publish');
        Route::post('/courses/{course}/assignments/{assignment}/feedback', [AssignmentController::class, 'storeFeedback'])->name('courses.assignments.feedback');

        Route::get('/courses/{course}/quizzes', [QuizAuthoringController::class, 'index'])->name('courses.quizzes.index');
        Route::get('/courses/{course}/quizzes/mark-queue', [QuizAuthoringController::class, 'markQueue'])->name('courses.quizzes.mark-queue');
        Route::post('/courses/{course}/questions', [QuizAuthoringController::class, 'storeQuestion'])->name('courses.questions.store');
        Route::post('/courses/{course}/quizzes', [QuizAuthoringController::class, 'storeQuiz'])->name('courses.quizzes.store');
        Route::post('/courses/{course}/quizzes/{quiz}/publish', [QuizAuthoringController::class, 'publish'])->name('courses.quizzes.publish');
        Route::post('/courses/{course}/quizzes/{quiz}/extra-time', [QuizAuthoringController::class, 'storeExtraTime'])->name('courses.quizzes.extra-time');
        Route::post('/courses/{course}/quizzes/{quiz}/answers/{answer}/mark', [QuizAuthoringController::class, 'markAnswer'])->name('courses.quizzes.mark');

        Route::get('/courses/{course}/enrolments', [EnrolmentController::class, 'index'])->name('courses.enrolments.index');
        Route::post('/courses/{course}/enrolments/manual', [EnrolmentController::class, 'storeManual'])->name('courses.enrolments.manual');
        Route::post('/courses/{course}/enrolments/group-rules', [EnrolmentController::class, 'storeGroupRule'])->name('courses.enrolments.group-rules');
        Route::post('/courses/{course}/enrolments/prerequisites', [EnrolmentController::class, 'storePrerequisite'])->name('courses.enrolments.prerequisites');
        Route::post('/courses/{course}/enrolments/sync', [EnrolmentController::class, 'sync'])->name('courses.enrolments.sync');
        Route::post('/courses/{course}/self-enrol', [EnrolmentController::class, 'selfEnrol'])->name('courses.self-enrol');
    });
