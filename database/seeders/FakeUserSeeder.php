<?php

namespace Database\Seeders;

use App\Core\Access\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\People\Models\Placement;

class FakeUserSeeder extends Seeder
{
    public const COUNT = 50;

    /**
     * Create fake people with example.test addresses. Safe to run again.
     */
    public function run(): void
    {
        $accountTypes = RoleName::accountTypes();
        $created = [];

        for ($i = 1; $i <= self::COUNT; $i++) {
            $email = sprintf('person%02d@example.test', $i);

            $user = User::query()->where('email', $email)->first();
            if ($user === null) {
                $user = User::factory()->create(['email' => $email]);
            }

            $type = $accountTypes[($i - 1) % count($accountTypes)];
            $user->syncRoles([$type->value]);
            $created[] = $user;
        }

        // A simple management chain for the org chart.
        for ($i = 1; $i < count($created); $i++) {
            if ($created[$i]->manager_id === null) {
                $created[$i]->forceFill(['manager_id' => $created[$i - 1]->getKey()])->save();
            }
        }

        $students = collect($created)->filter(fn (User $u) => $u->hasRole(RoleName::Student->value))->values();
        $supervisors = collect($created)->filter(fn (User $u) => $u->hasRole(RoleName::Staff->value))->values();

        foreach ($students as $index => $student) {
            if (Placement::query()->where('student_id', $student->getKey())->exists()) {
                continue;
            }

            Placement::query()->create([
                'student_id' => $student->getKey(),
                'supervisor_id' => $supervisors[$index % max(1, $supervisors->count())]->getKey() ?? null,
                'title' => 'Placement '.($index + 1),
                'organisation' => 'APES CIC',
                'starts_on' => now()->subMonths(1)->toDateString(),
                'ends_on' => now()->addMonths(5)->toDateString(),
                'guidance' => 'Fake placement guidance for local testing only.',
            ]);
        }
    }
}
