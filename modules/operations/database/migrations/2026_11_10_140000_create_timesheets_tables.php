<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operations_timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default('draft'); // draft, submitted, approved, returned
            $table->unsignedInteger('total_minutes')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 1000)->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'period_start']);
        });

        Schema::create('operations_timesheet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timesheet_id')->constrained('operations_timesheets')->cascadeOnDelete();
            $table->foreignId('clock_record_id')->nullable()->constrained('operations_clock_records')->nullOnDelete();
            $table->date('worked_on');
            $table->unsignedInteger('minutes');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations_timesheet_entries');
        Schema::dropIfExists('operations_timesheets');
    }
};
