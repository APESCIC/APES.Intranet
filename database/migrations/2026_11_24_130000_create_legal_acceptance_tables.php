<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('legal_page_version_id')->constrained('legal_page_versions')->cascadeOnDelete();
            $table->timestamp('accepted_at');
            $table->timestamps();

            $table->unique(['user_id', 'legal_page_version_id']);
        });

        Schema::create('legal_notice_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('legal_page_id')->constrained('legal_pages')->cascadeOnDelete();
            $table->foreignId('legal_page_version_id')->constrained('legal_page_versions')->cascadeOnDelete();
            $table->timestamp('dismissed_at');
            $table->timestamps();

            $table->unique(['user_id', 'legal_page_version_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_notice_dismissals');
        Schema::dropIfExists('legal_acceptances');
    }
};
