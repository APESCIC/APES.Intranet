<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('queue')->index(); // it, management
            $table->string('default_priority')->default('normal'); // urgent, high, normal, low
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('helpdesk_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->string('queue')->index(); // it, management
            $table->foreignId('category_id')->nullable()->constrained('helpdesk_categories')->nullOnDelete();
            $table->string('priority')->default('normal');
            $table->string('status')->default('open')->index(); // open, waiting, resolved, closed
            $table->string('subject');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('helpdesk_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('helpdesk_tickets')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('body');
            $table->boolean('is_private')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_messages');
        Schema::dropIfExists('helpdesk_tickets');
        Schema::dropIfExists('helpdesk_categories');
    }
};
