<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_appraisal_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('year');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('open')->default(true);
            $table->timestamps();
        });

        Schema::create('hr_appraisals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('hr_appraisal_cycles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('self_review')->nullable();
            $table->text('manager_review')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->unique(['cycle_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_appraisals');
        Schema::dropIfExists('hr_appraisal_cycles');
    }
};
