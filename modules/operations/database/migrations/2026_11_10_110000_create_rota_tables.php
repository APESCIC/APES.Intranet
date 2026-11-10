<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operations_shift_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('operations_sites')->cascadeOnDelete();
            $table->string('name');
            $table->string('role');
            $table->unsignedTinyInteger('weekday'); // ISO: 1 Monday to 7 Sunday
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('operations_rota_weeks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('operations_sites')->cascadeOnDelete();
            $table->date('week_start');
            $table->string('status')->default('draft'); // draft, published
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['site_id', 'week_start']);
        });

        Schema::create('operations_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rota_week_id')->constrained('operations_rota_weeks')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('operations_sites')->cascadeOnDelete();
            $table->foreignId('shift_template_id')->nullable()->constrained('operations_shift_templates')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamps();
            $table->index(['user_id', 'starts_at']);
        });

        Schema::create('operations_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('note', 200)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations_availabilities');
        Schema::dropIfExists('operations_shifts');
        Schema::dropIfExists('operations_rota_weeks');
        Schema::dropIfExists('operations_shift_templates');
    }
};
