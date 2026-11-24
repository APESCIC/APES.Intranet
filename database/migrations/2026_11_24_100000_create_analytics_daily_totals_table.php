<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily operational counts for the admin dashboard. No user IDs, names or IPs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_daily_totals', function (Blueprint $table) {
            $table->id();
            $table->date('metric_date');
            $table->string('metric', 100);
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['metric_date', 'metric']);
            $table->index('metric_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_totals');
    }
};
