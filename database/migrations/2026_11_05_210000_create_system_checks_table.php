<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_checks', function (Blueprint $table) {
            $table->id();
            $table->string('check', 40)->unique();
            $table->string('status', 20);
            $table->timestamp('checked_at');
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamp('alerted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_checks');
    }
};
