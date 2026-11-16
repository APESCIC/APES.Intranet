<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assignments, versioned submissions and written feedback for v0.6.7 (#177).
 * Files live on the private local disk under learning/submissions/.
 * Feedback visibility uses released_at (wired further in #180).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('learning_lessons')->nullOnDelete();
            $table->string('title');
            $table->longText('instructions')->nullable();
            $table->boolean('allow_text')->default(true);
            $table->boolean('allow_file')->default(true);
            $table->json('allowed_extensions')->nullable(); // e.g. ["pdf","docx"]
            $table->unsignedInteger('max_file_bytes')->nullable();
            $table->string('status', 16)->default('draft'); // draft|published
            $table->timestamp('due_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['course_id', 'status']);
        });

        Schema::create('learning_assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('learning_assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->longText('text_body')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('stored_path')->nullable(); // private disk relative path
            $table->string('mime')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique(['assignment_id', 'user_id', 'version_number'], 'learning_asub_assign_user_ver_uq');
            $table->index(['assignment_id', 'user_id'], 'learning_asub_assign_user_idx');
        });

        Schema::create('learning_assignment_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('learning_assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // learner
            $table->foreignId('submission_id')->nullable()->constrained('learning_assignment_submissions')->nullOnDelete();
            $table->longText('body');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable(); // null = hidden from learner (#180 hook)
            $table->timestamps();

            $table->unique(['assignment_id', 'user_id'], 'learning_afeedback_assign_user_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_assignment_feedback');
        Schema::dropIfExists('learning_assignment_submissions');
        Schema::dropIfExists('learning_assignments');
    }
};
