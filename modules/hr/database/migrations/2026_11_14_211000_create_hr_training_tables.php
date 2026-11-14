<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_training_courses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->string('lms_url');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('hr_training_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('hr_training_courses')->cascadeOnDelete();
            $table->string('role'); // Spatie role name
            $table->unsignedSmallInteger('renew_months')->nullable();
            $table->timestamps();
            $table->unique(['course_id', 'role']);
        });

        Schema::create('hr_training_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('hr_training_courses')->cascadeOnDelete();
            $table->date('completed_on');
            $table->date('expires_on')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'course_id', 'completed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_training_completions');
        Schema::dropIfExists('hr_training_requirements');
        Schema::dropIfExists('hr_training_courses');
    }
};
