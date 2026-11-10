<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operations_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('assignee_type'); // person, team, shift
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('team')->nullable()->index();
            $table->foreignId('shift_id')->nullable()->constrained('operations_shifts')->nullOnDelete();
            $table->dateTime('due_at')->nullable()->index();
            $table->string('recurrence')->nullable(); // daily, weekly, monthly
            $table->string('status')->default('open'); // open, in_progress, done
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('previous_task_id')->nullable()->constrained('operations_tasks')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('operations_task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('operations_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations_task_comments');
        Schema::dropIfExists('operations_tasks');
    }
};
