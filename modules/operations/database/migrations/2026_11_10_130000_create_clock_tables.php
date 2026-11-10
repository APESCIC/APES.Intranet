<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operations_clock_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('operations_shifts')->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('operations_sites')->nullOnDelete();
            $table->dateTime('clock_in_at');
            $table->dateTime('clock_out_at')->nullable();
            // Only "pass" or "fail", and only when the approved geofence ran. Never coordinates.
            $table->string('geofence_result', 4)->nullable();
            $table->timestamp('missed_clock_out_flagged_at')->nullable();
            $table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'clock_in_at']);
        });

        Schema::create('operations_clock_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clock_record_id')->constrained('operations_clock_records')->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations_clock_breaks');
        Schema::dropIfExists('operations_clock_records');
    }
};
