<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('update_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->longText('body');
            $table->json('audiences')->nullable();
            $table->boolean('pinned')->default(false);
            $table->boolean('comments_enabled')->default(true);
            $table->boolean('email_alert')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('update_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('update_post_id')->constrained('update_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['update_post_id', 'user_id']);
        });

        Schema::create('update_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('update_post_id')->constrained('update_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->longText('body')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('reminder_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('event_rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status'); // going, maybe, declined
            $table->timestamps();
            $table->unique(['event_id', 'user_id']);
        });

        Schema::create('handbook_chapters', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('published')->default(false);
            $table->timestamps();
        });

        Schema::create('handbook_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('handbook_chapter_id')->constrained('handbook_chapters')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->longText('body');
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['handbook_chapter_id', 'version_number']);
        });

        Schema::create('handbook_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('handbook_version_id')->constrained('handbook_versions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('acknowledged_at');
            $table->unique(['handbook_version_id', 'user_id']);
        });

        Schema::create('policy_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('sharepoint_url');
            $table->string('owner_name')->nullable();
            $table->date('review_due_on')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('policy_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_document_id')->constrained('policy_documents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['policy_document_id', 'user_id']);
        });

        Schema::create('microsoft_links', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('url');
            $table->string('icon')->default('app-window');
            $table->string('audience')->default('all'); // all | staff
            $table->boolean('opens_in_new_tab')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('dashboard_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('card_key');
            $table->timestamp('dismissed_until');
            $table->timestamps();
            $table->unique(['user_id', 'card_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_dismissals');
        Schema::dropIfExists('microsoft_links');
        Schema::dropIfExists('policy_reads');
        Schema::dropIfExists('policy_documents');
        Schema::dropIfExists('handbook_acknowledgements');
        Schema::dropIfExists('handbook_versions');
        Schema::dropIfExists('handbook_chapters');
        Schema::dropIfExists('event_rsvps');
        Schema::dropIfExists('events');
        Schema::dropIfExists('update_comments');
        Schema::dropIfExists('update_reads');
        Schema::dropIfExists('update_posts');
    }
};
