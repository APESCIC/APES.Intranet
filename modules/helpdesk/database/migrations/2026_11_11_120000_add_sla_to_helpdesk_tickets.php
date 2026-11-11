<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('helpdesk_tickets', function (Blueprint $table) {
            $table->timestamp('response_due_at')->nullable()->after('merged_into_id');
            $table->timestamp('resolve_due_at')->nullable()->after('response_due_at');
            $table->timestamp('first_responded_at')->nullable()->after('resolve_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('helpdesk_tickets', function (Blueprint $table) {
            $table->dropColumn(['response_due_at', 'resolve_due_at', 'first_responded_at']);
        });
    }
};
