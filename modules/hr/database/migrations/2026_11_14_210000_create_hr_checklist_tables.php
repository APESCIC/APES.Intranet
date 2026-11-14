<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->string('kind'); // onboarding | offboarding
            $table->string('member_type'); // staff | volunteer | student
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['kind', 'member_type']);
        });

        Schema::create('hr_checklist_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('hr_checklist_templates')->cascadeOnDelete();
            $table->string('owner_role'); // hr | manager | it
            $table->string('title');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('hr_member_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('hr_checklist_templates')->restrictOnDelete();
            $table->string('kind');
            $table->string('member_type');
            $table->string('status')->default('open'); // open | completed
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('it_ticket_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('hr_member_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('hr_member_checklists')->cascadeOnDelete();
            $table->string('owner_role');
            $table->string('title');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_member_checklist_items');
        Schema::dropIfExists('hr_member_checklists');
        Schema::dropIfExists('hr_checklist_template_items');
        Schema::dropIfExists('hr_checklist_templates');
    }
};
