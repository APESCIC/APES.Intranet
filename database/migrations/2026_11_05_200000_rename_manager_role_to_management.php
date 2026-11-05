<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Renames the role in place, so everyone who had `manager` keeps it as
 * `management` without signing in again.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->rename('manager', 'management');
    }

    public function down(): void
    {
        $this->rename('management', 'manager');
    }

    private function rename(string $from, string $to): void
    {
        $roles = DB::table(config('permission.table_names.roles', 'roles'));

        if ($roles->clone()->where('name', $to)->where('guard_name', 'web')->exists()) {
            return;
        }

        $roles->where('name', $from)->where('guard_name', 'web')->update(['name' => $to, 'updated_at' => now()]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
