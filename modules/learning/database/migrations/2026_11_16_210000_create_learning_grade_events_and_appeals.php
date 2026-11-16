<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only grade events, overrides and appeals (#181).
 * Events are never updated or deleted by application code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_grade_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_item_id')->constrained('learning_grade_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // learner
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 32); // override|appeal_opened|appeal_resolved|mark
            $table->decimal('old_score', 12, 6)->nullable();
            $table->decimal('new_score', 12, 6)->nullable();
            $table->text('reason');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['grade_item_id', 'user_id', 'occurred_at'], 'learning_gevent_item_user_time_idx');
        });

        Schema::create('learning_grade_appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_item_id')->constrained('learning_grade_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('grounds');
            $table->string('status', 24)->default('open'); // open|upheld|rejected
            $table->text('outcome_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['grade_item_id', 'user_id', 'status'], 'learning_gappeal_item_user_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_grade_appeals');
        Schema::dropIfExists('learning_grade_events');
    }
};
