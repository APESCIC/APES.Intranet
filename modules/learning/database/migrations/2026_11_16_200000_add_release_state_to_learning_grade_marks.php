<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moderation / release workflow on grade marks (#180).
 * States: draft → marked → moderated → released.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_grade_marks', function (Blueprint $table) {
            $table->string('release_state', 24)->default('draft')->after('passed');
            $table->foreignId('moderated_by')->nullable()->after('marked_by')->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('marked_at');
            $table->foreignId('released_by')->nullable()->after('moderated_by')->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable()->after('moderated_at');
            $table->index(['release_state'], 'learning_gmark_release_idx');
        });
    }

    public function down(): void
    {
        Schema::table('learning_grade_marks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropConstrainedForeignId('released_by');
            $table->dropColumn(['release_state', 'moderated_at', 'released_at']);
        });
    }
};
