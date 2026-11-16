<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Weighted grade categories for the calculation engine (#179).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_grade_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('weight', 8, 4); // percent of course total; siblings must sum to 100
            $table->string('drop_policy', 32)->nullable(); // none|lowest — reserved
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['course_id', 'sort_order'], 'learning_gcat_course_sort_idx');
        });

        Schema::table('learning_grade_items', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('course_id')
                ->constrained('learning_grade_categories')
                ->nullOnDelete();
            $table->string('attempt_policy', 32)->default('latest')->after('weight'); // best|latest|average
            $table->timestamp('due_at')->nullable()->after('attempt_policy');
            $table->string('missing_policy', 32)->default('missing')->after('due_at'); // missing|zero_after_due
        });
    }

    public function down(): void
    {
        Schema::table('learning_grade_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['attempt_policy', 'due_at', 'missing_policy']);
        });

        Schema::dropIfExists('learning_grade_categories');
    }
};
