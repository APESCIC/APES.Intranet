<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Question bank, quizzes and attempts for v0.6.7 (#176).
 * Gradebook release / schemes land in later stories; attempt scores here are
 * attempt-local only and do not write learning_lesson_progress.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->string('type', 32); // mcq|true_false|short_answer|essay
            $table->longText('stem');
            $table->json('options')->nullable(); // mcq choices; null for other types
            $table->json('correct_answer')->nullable(); // auto-mark payload; null for essay
            $table->unsignedInteger('points')->default(1);
            $table->boolean('needs_manual_mark')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['course_id', 'type']);
        });

        Schema::create('learning_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('learning_lessons')->nullOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->unsignedInteger('attempt_limit')->nullable(); // null = unlimited
            $table->unsignedInteger('time_limit_seconds')->nullable(); // null = no timer
            $table->unsignedInteger('random_draw_count')->nullable(); // null = use all attached
            $table->string('status', 16)->default('draft'); // draft|published
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['course_id', 'status']);
        });

        Schema::create('learning_quiz_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('learning_quizzes')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('learning_questions')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['quiz_id', 'question_id']);
            $table->index(['quiz_id', 'sort_order']);
        });

        Schema::create('learning_quiz_extra_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('learning_quizzes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('extra_seconds');
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['quiz_id', 'user_id']);
        });

        Schema::create('learning_quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('learning_quizzes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->string('status', 24)->default('in_progress'); // in_progress|submitted|expired
            $table->timestamp('started_at');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('auto_score', 10, 4)->nullable();
            $table->decimal('manual_score', 10, 4)->nullable();
            $table->json('drawn_question_ids')->nullable();
            $table->timestamps();

            $table->unique(['quiz_id', 'user_id', 'attempt_number']);
            $table->index(['quiz_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('learning_quiz_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('learning_quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('learning_questions')->cascadeOnDelete();
            $table->longText('response')->nullable();
            $table->boolean('needs_manual_mark')->default(false);
            $table->boolean('is_correct')->nullable();
            $table->decimal('score', 10, 4)->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable();
            $table->text('marker_feedback')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
            $table->index(['needs_manual_mark', 'marked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_quiz_attempt_answers');
        Schema::dropIfExists('learning_quiz_attempts');
        Schema::dropIfExists('learning_quiz_extra_times');
        Schema::dropIfExists('learning_quiz_question');
        Schema::dropIfExists('learning_quizzes');
        Schema::dropIfExists('learning_questions');
    }
};
