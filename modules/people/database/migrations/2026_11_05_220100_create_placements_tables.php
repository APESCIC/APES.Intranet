<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('organisation')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->text('guidance')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'starts_on']);
        });

        Schema::create('placement_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->constrained('placements')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->date('worked_on');
            $table->unsignedSmallInteger('minutes');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('reviewer_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['placement_id', 'status']);
            $table->index(['student_id', 'worked_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_hours');
        Schema::dropIfExists('placements');
    }
};
