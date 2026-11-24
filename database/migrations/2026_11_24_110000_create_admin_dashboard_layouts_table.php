<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-admin card order/visibility. A row with null user_id is the shared default.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_dashboard_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->json('ordered_keys');
            $table->json('hidden_keys')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_dashboard_layouts');
    }
};
