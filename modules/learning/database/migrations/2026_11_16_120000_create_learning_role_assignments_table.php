<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32);
            $table->string('scope_type', 16);
            // 0 means “no id” (global learning admin). Avoids MySQL NULL unique quirks.
            $table->unsignedBigInteger('scope_id')->default(0);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'role', 'scope_type', 'scope_id'], 'learning_role_assignments_unique');
            $table->index(['scope_type', 'scope_id']);
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_role_assignments');
    }
};
