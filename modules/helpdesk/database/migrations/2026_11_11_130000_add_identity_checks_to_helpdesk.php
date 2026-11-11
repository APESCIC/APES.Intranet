<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('helpdesk_categories', function (Blueprint $table) {
            $table->boolean('requires_identity_check')->default(false)->after('default_priority');
        });

        Schema::table('helpdesk_tickets', function (Blueprint $table) {
            $table->timestamp('two_factor_reset_at')->nullable()->after('first_responded_at');
            $table->foreignId('two_factor_reset_by')->nullable()->after('two_factor_reset_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('helpdesk_identity_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('helpdesk_tickets')->cascadeOnDelete();
            $table->string('method'); // callback, manager_in_person, manager_video
            $table->text('note')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_identity_checks');

        Schema::table('helpdesk_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('two_factor_reset_by');
            $table->dropColumn('two_factor_reset_at');
        });

        Schema::table('helpdesk_categories', function (Blueprint $table) {
            $table->dropColumn('requires_identity_check');
        });
    }
};
