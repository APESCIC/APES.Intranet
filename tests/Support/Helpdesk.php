<?php

namespace Tests\Support;

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\HelpdeskBootstrapSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Modules\Helpdesk\Models\Category;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Services\Sla;

/**
 * Fixtures for the help desk feature tests. Fake data only.
 */
final class Helpdesk
{
    public static function seed(): void
    {
        Artisan::call('db:seed', ['--class' => RolesAndPermissionsSeeder::class]);
        Artisan::call('db:seed', ['--class' => HelpdeskBootstrapSeeder::class]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function member(RoleName $role = RoleName::Staff, array $attributes = []): User
    {
        return User::factory()->create($attributes)->assignRole($role->value);
    }

    public static function category(string $slug): Category
    {
        return Category::query()->where('slug', $slug)->firstOrFail();
    }

    /**
     * A ticket with its first message, as a member would raise it.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function ticket(User $requester, string $category = 'devices', array $attributes = []): Ticket
    {
        $category = self::category($category);

        $ticket = Ticket::query()->create($attributes + [
            'requester_id' => $requester->getKey(),
            'queue' => $category->queue,
            'category_id' => $category->getKey(),
            'priority' => $category->default_priority,
            'status' => Ticket::STATUS_OPEN,
            'subject' => 'The printer is jammed',
        ]);
        app(Sla::class)->apply($ticket);
        $ticket->save();
        $ticket->messages()->create(['author_id' => $requester->getKey(), 'body' => '<p>It jams on every page.</p>', 'is_private' => false]);

        return $ticket;
    }
}
