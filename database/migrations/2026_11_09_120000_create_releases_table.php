<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20)->unique();
            $table->unsignedSmallInteger('major');
            $table->unsignedSmallInteger('minor');
            $table->unsignedSmallInteger('patch');
            $table->string('title', 120);
            $table->date('released_at');
            $table->text('summary');
            $table->json('audiences');
            $table->json('changes');
            $table->json('internal_notes')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();

            $table->index(['major', 'minor', 'patch']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};
