<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_courses', function (Blueprint $table) {
            $table->decimal('cpd_hours', 5, 2)->nullable()->after('self_enrol_enabled');
            $table->unsignedInteger('certificate_renew_months')->nullable()->after('cpd_hours');
        });

        Schema::create('learning_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('learning_courses')->cascadeOnDelete();
            $table->foreignId('course_completion_id')->nullable()->constrained('learning_course_completions')->nullOnDelete();
            $table->string('verify_code', 32)->unique();
            $table->decimal('cpd_hours', 5, 2)->nullable();
            $table->timestamp('issued_at');
            $table->date('expires_on')->nullable();
            $table->timestamp('expiry_reminded_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
            $table->index(['expires_on', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_certificates');

        Schema::table('learning_courses', function (Blueprint $table) {
            $table->dropColumn(['cpd_hours', 'certificate_renew_months']);
        });
    }
};
