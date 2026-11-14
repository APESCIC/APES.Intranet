<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_identity_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('check_type'); // dbs | right_to_work
            $table->string('label')->nullable();
            $table->string('reference')->nullable();
            $table->date('checked_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->string('document_url')->nullable(); // SharePoint link only; never a scan
            $table->timestamp('expiry_reminded_at')->nullable();
            $table->timestamps();

            $table->index(['check_type', 'expires_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_identity_checks');
    }
};
