<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('visibility')->default('everyone');
            $table->boolean('needs_acceptance')->default(false);
            $table->unsignedInteger('review_interval_months')->default(12);
            $table->boolean('locked')->default(false);
            $table->timestamps();
        });

        Schema::create('legal_page_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_page_id')->constrained('legal_pages')->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->longText('body');
            $table->string('change_summary')->nullable();
            $table->timestamp('in_effect_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->boolean('material_change')->default(false);
            $table->timestamps();

            $table->unique(['legal_page_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_page_versions');
        Schema::dropIfExists('legal_pages');
    }
};
