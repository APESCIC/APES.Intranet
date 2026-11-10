<?php

namespace Tests\Support;

use App\Core\Access\RoleName;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\OperationsPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Modules\Operations\Models\RotaWeek;
use Modules\Operations\Models\Shift;
use Modules\Operations\Models\Site;

/**
 * Fixtures for the Operations feature tests. Fake data only.
 */
final class Operations
{
    public static function seedRoles(): void
    {
        Artisan::call('db:seed', ['--class' => RolesAndPermissionsSeeder::class]);
        Artisan::call('db:seed', ['--class' => OperationsPermissionsSeeder::class]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function member(RoleName $role = RoleName::Staff, array $attributes = []): User
    {
        return User::factory()->create($attributes + ['team' => 'Care'])->assignRole($role->value);
    }

    public static function site(string $team = 'Care', string $name = 'Reptile house'): Site
    {
        return Site::query()->create(['name' => $name, 'team' => $team, 'active' => true]);
    }

    public static function week(Site $site, string $status = RotaWeek::STATUS_PUBLISHED, ?CarbonImmutable $weekStart = null): RotaWeek
    {
        $weekStart ??= CarbonImmutable::now()->startOfWeek();

        return RotaWeek::query()->create([
            'site_id' => $site->id,
            'week_start' => $weekStart->toDateString(),
            'status' => $status,
            'published_at' => $status === RotaWeek::STATUS_PUBLISHED ? now() : null,
        ]);
    }

    public static function shift(RotaWeek $week, ?User $member, CarbonImmutable $start, int $hours = 8, string $role = 'Animal care'): Shift
    {
        return $week->shifts()->create([
            'site_id' => $week->site_id,
            'user_id' => $member?->getKey(),
            'role' => $role,
            'starts_at' => $start,
            'ends_at' => $start->addHours($hours),
        ]);
    }
}
