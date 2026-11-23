<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('appearance_theme', 16)->default('glass')->after('remember_token');
            $table->string('appearance_mode', 16)->default('system')->after('appearance_theme');
            $table->boolean('appearance_reduced_transparency')->default(false)->after('appearance_mode');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'appearance_theme',
                'appearance_mode',
                'appearance_reduced_transparency',
            ]);
        });
    }
};
