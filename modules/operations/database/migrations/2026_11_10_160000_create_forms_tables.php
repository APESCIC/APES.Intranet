<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operations_forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft, published
            $table->foreignId('route_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('route_to_role')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('operations_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('operations_forms')->cascadeOnDelete();
            $table->string('label');
            $table->string('type'); // text, textarea, number, date, select, checkbox, photo, signature
            $table->boolean('required')->default(false);
            $table->json('options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('operations_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('operations_forms')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('routed_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('routed_to_role')->nullable();
            $table->timestamps();
        });

        Schema::create('operations_form_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('operations_form_submissions')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('operations_form_fields')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations_form_answers');
        Schema::dropIfExists('operations_form_submissions');
        Schema::dropIfExists('operations_form_fields');
        Schema::dropIfExists('operations_forms');
    }
};
