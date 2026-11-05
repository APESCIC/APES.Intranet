<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('email');
            $table->timestamp('deactivated_at')->nullable()->after('is_active');
            $table->string('cloudron_username')->nullable()->after('oidc_sub');
            $table->string('job_title')->nullable()->after('name');
            $table->string('team')->nullable()->after('job_title');
            $table->string('phone')->nullable()->after('team');
            $table->string('mobile')->nullable()->after('phone');
            $table->string('location')->nullable()->after('mobile');
            $table->json('skills')->nullable()->after('location');
            $table->text('bio')->nullable()->after('skills');
            $table->string('photo_path')->nullable()->after('bio');
            $table->string('emergency_contact_name')->nullable()->after('photo_path');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->string('employee_number')->nullable()->after('emergency_contact_phone');
            $table->foreignId('manager_id')->nullable()->after('employee_number')->constrained('users')->nullOnDelete();
            $table->boolean('totp_enabled')->nullable()->after('manager_id');
            $table->timestamp('totp_checked_at')->nullable()->after('totp_enabled');
            $table->index(['is_active', 'name']);
            $table->index('team');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_id');
            $table->dropIndex(['is_active', 'name']);
            $table->dropIndex(['team']);
            $table->dropColumn([
                'is_active',
                'deactivated_at',
                'cloudron_username',
                'job_title',
                'team',
                'phone',
                'mobile',
                'location',
                'skills',
                'bio',
                'photo_path',
                'emergency_contact_name',
                'emergency_contact_phone',
                'employee_number',
                'totp_enabled',
                'totp_checked_at',
            ]);
        });
    }
};
