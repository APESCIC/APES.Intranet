<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_courses', function (Blueprint $table) {
            $table->boolean('self_enrol_enabled')->default(false)->after('status');
        });

        Schema::create('learning_cohorts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['course_id', 'name']);
        });

        Schema::create('learning_enrolments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->foreignId('cohort_id')->nullable()->constrained('learning_cohorts')->nullOnDelete();
            $table->string('method', 16); // manual|group|self
            $table->string('status', 16)->default('active'); // active|ended
            $table->string('source_group')->nullable(); // canonical Cloudron group cn when method=group
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('enrolled_at');
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
            $table->index(['course_id', 'status']);
            $table->index(['source_group', 'status']);
        });

        Schema::create('learning_course_group_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->string('directory_group'); // e.g. intranet.staff
            $table->foreignId('cohort_id')->nullable()->constrained('learning_cohorts')->nullOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'directory_group']);
        });

        Schema::create('learning_course_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->foreignId('prerequisite_course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'prerequisite_course_id'], 'learning_prereq_unique');
        });

        // Minimal completion marker so prerequisites can block access before #174
        // expands progress computation. Grades stay out of this table.
        Schema::create('learning_course_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_course_completions');
        Schema::dropIfExists('learning_course_prerequisites');
        Schema::dropIfExists('learning_course_group_rules');
        Schema::dropIfExists('learning_enrolments');
        Schema::dropIfExists('learning_cohorts');

        Schema::table('learning_courses', function (Blueprint $table) {
            $table->dropColumn('self_enrol_enabled');
        });
    }
};
