<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_appraisal_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_id')->constrained('hr_appraisals')->cascadeOnDelete();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('hr_appraisals', function (Blueprint $table) {
            $table->timestamp('member_signed_at')->nullable()->after('locked_at');
            $table->foreignId('member_signed_by')->nullable()->after('member_signed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('manager_signed_at')->nullable()->after('member_signed_by');
            $table->foreignId('manager_signed_by')->nullable()->after('manager_signed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hr_appraisals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_signed_by');
            $table->dropConstrainedForeignId('manager_signed_by');
            $table->dropColumn(['member_signed_at', 'manager_signed_at']);
        });
        Schema::dropIfExists('hr_appraisal_objectives');
    }
};
