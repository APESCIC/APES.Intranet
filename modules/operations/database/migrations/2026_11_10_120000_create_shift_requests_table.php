<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operations_shift_requests', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // claim, swap
            $table->foreignId('shift_id')->constrained('operations_shifts')->cascadeOnDelete();
            $table->foreignId('target_shift_id')->nullable()->constrained('operations_shifts')->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, approved, declined, cancelled
            $table->string('note', 500)->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note', 500)->nullable();
            $table->timestamps();
            $table->index(['status', 'shift_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations_shift_requests');
    }
};
