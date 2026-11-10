<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operations_checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('operations_sites')->cascadeOnDelete();
            // Null means every shift at the site.
            $table->foreignId('shift_template_id')->nullable()->constrained('operations_shift_templates')->nullOnDelete();
            $table->string('name');
            $table->time('due_time');
            $table->json('items');
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('operations_checklist_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_template_id')->constrained('operations_checklist_templates')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('operations_shifts')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('operations_sites')->cascadeOnDelete();
            $table->dateTime('due_at')->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('missed_alerted_at')->nullable();
            $table->timestamps();
            $table->unique(['checklist_template_id', 'shift_id']);
        });

        Schema::create('operations_checklist_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_run_id')->constrained('operations_checklist_runs')->cascadeOnDelete();
            $table->string('label');
            $table->boolean('done')->default(false);
            $table->string('note', 1000)->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations_checklist_run_items');
        Schema::dropIfExists('operations_checklist_runs');
        Schema::dropIfExists('operations_checklist_templates');
    }
};
