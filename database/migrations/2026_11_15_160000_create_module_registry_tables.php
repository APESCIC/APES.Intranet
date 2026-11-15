<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_states', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->boolean('enabled');
            $table->string('reason', 500);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
        });

        Schema::create('module_settings', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64);
            $table->string('key', 100);
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['slug', 'key']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_settings');
        Schema::dropIfExists('module_states');
    }
};
