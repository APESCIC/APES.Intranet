<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grading schemes, rubrics and minimal grade items for v0.6.7 (#178).
 * Letter scales stay out of scope. Calculation engine is #179.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_grading_schemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 32); // points|percentage|pass_fail
            $table->decimal('max_points', 10, 4)->nullable(); // points schemes
            $table->decimal('pass_threshold', 10, 4)->nullable(); // percentage or points to pass; pass_fail uses 1
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['course_id', 'type']);
        });

        Schema::create('learning_rubrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('learning_rubric_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained('learning_rubrics')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['rubric_id', 'sort_order'], 'learning_rcrit_rubric_sort_idx');
        });

        Schema::create('learning_rubric_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criterion_id')->constrained('learning_rubric_criteria')->cascadeOnDelete();
            $table->string('label');
            $table->decimal('points', 10, 4);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['criterion_id', 'sort_order'], 'learning_rlevel_crit_sort_idx');
        });

        Schema::create('learning_grade_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->string('title');
            $table->foreignId('scheme_id')->nullable()->constrained('learning_grading_schemes')->nullOnDelete();
            $table->foreignId('rubric_id')->nullable()->constrained('learning_rubrics')->nullOnDelete();
            $table->string('source_type', 32)->nullable(); // quiz|assignment|manual
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('weight', 8, 4)->nullable(); // used by #179
            $table->timestamps();

            $table->index(['course_id', 'source_type', 'source_id'], 'learning_gitem_source_idx');
        });

        Schema::create('learning_grade_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_item_id')->constrained('learning_grade_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('raw_score', 12, 6)->nullable();
            $table->boolean('passed')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();

            $table->unique(['grade_item_id', 'user_id'], 'learning_gmark_item_user_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_grade_marks');
        Schema::dropIfExists('learning_grade_items');
        Schema::dropIfExists('learning_rubric_levels');
        Schema::dropIfExists('learning_rubric_criteria');
        Schema::dropIfExists('learning_rubrics');
        Schema::dropIfExists('learning_grading_schemes');
    }
};
