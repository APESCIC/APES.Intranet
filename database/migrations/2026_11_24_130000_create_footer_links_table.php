<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('footer_links', function (Blueprint $table) {
            $table->id();
            $table->string('group');
            $table->string('label');
            $table->string('icon')->nullable();
            $table->string('target_type');
            $table->string('target_value');
            $table->string('visibility')->default('everyone');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('locked')->default(false);
            $table->string('module_slug')->nullable();
            $table->timestamps();

            $table->index(['group', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('footer_links');
    }
};
