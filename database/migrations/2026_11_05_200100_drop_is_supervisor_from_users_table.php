<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The `intranet.supervisor` Cloudron group replaces the per-person flag.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_supervisor')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_supervisor');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_supervisor')->default(false)->after('email');
        });
    }
};
