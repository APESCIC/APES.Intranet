<?php

namespace Database\Seeders;

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\Qa\QaLogin;
use App\Core\Support\HtmlSanitizer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Communications\Models\Event;
use Modules\Communications\Models\EventRsvp;
use Modules\Communications\Models\HandbookChapter;
use Modules\Communications\Models\HandbookVersion;
use Modules\Communications\Models\PolicyDocument;
use Modules\Communications\Models\PolicyRead;
use Modules\Communications\Models\UpdateComment;
use Modules\Communications\Models\UpdatePost;
use Modules\Communications\Models\UpdateRead;
use Modules\Operations\Models\Availability;
use Modules\Operations\Models\ChecklistRun;
use Modules\Operations\Models\ChecklistTemplate;
use Modules\Operations\Models\ClockRecord;
use Modules\Operations\Models\Form;
use Modules\Operations\Models\RotaWeek;
use Modules\Operations\Models\Shift;
use Modules\Operations\Models\ShiftRequest;
use Modules\Operations\Models\ShiftTemplate;
use Modules\Operations\Models\Site;
use Modules\Operations\Models\Task;
use Modules\Operations\Models\Timesheet;
use Modules\Operations\Services\Checklists;
use Modules\People\Models\Placement;
use Modules\People\Models\PlacementHour;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Fake demo data for clicking through the intranet locally (php artisan
 * demo:seed). Everything is made up: example.test addresses, Ofcom drama
 * phone numbers and invented organisations. The same seed gives the same
 * data on every run, and running it again updates rows instead of adding
 * duplicates. Dates are relative to today.
 */
class DemoSeeder extends Seeder
{
    public const SEED = 20261109;

    public const PEOPLE = 50;

    public const REFUSAL = 'The demo data is fake and only loads when APP_ENV is local.';

    private const AUDIT_MARKER = 'APES demo seeder';

    private Generator $faker;

    private CarbonImmutable $today;

    /** @var Collection<int, User> */
    private Collection $people;

    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException(self::REFUSAL);
        }

        $this->faker = FakerFactory::create('en_GB');
        $this->today = CarbonImmutable::today();

        // On Windows, Apache can hold the file cache open while the seeder
        // rewrites the permission cache hundreds of times, so keep it in memory
        // until the end.
        $registrar = app(PermissionRegistrar::class);
        $store = config('permission.cache.store');
        config(['permission.cache.store' => 'array']);
        $registrar->initializeCache();

        try {
            $this->call([RolesAndPermissionsSeeder::class, CommunicationsBootstrapSeeder::class, OperationsBootstrapSeeder::class, HelpdeskBootstrapSeeder::class, QaUserSeeder::class, ReleaseNotesSeeder::class]);

            $this->seedPeople();
            $this->seedPlacements();
            $this->seedUpdates();
            $this->seedEvents();
            $this->seedHandbook();
            $this->seedPolicies();
            $this->seedOperations();
            $this->seedAuditLog();
        } finally {
            config(['permission.cache.store' => $store]);
            $registrar->initializeCache();
            $registrar->forgetCachedPermissions();
        }
    }

    private function seed(int $offset): Generator
    {
        $this->faker->seed(self::SEED + $offset);

        return $this->faker;
    }

    private function person(int $i): User
    {
        return $this->people[($i - 1) % self::PEOPLE];
    }

    private function seedPeople(): void
    {
        $teams = ['Animal care', 'Education', 'Operations', 'Fundraising', 'Office'];
        $skills = ['First aid', 'Driving', 'Admin', 'Animal care', 'Fundraising', 'IT', 'Reptile handling', 'Public speaking'];
        $titles = [
            RoleName::Staff->value => ['Animal Care Lead', 'Education Officer', 'Operations Coordinator', 'Fundraising Officer', 'Office Manager', 'Keeper'],
            RoleName::Volunteer->value => ['Animal Care Volunteer', 'Visitor Centre Volunteer', 'Events Volunteer', 'Fundraising Volunteer'],
            RoleName::Student->value => ['Placement Student'],
        ];
        $extraRoles = [1 => RoleName::Admin, 4 => RoleName::Management, 7 => RoleName::Hr, 10 => RoleName::Legal, 13 => RoleName::Supervisor, 16 => RoleName::Supervisor, 19 => RoleName::Supervisor];
        $accountTypes = RoleName::accountTypes();

        $people = collect();
        for ($i = 1; $i <= self::PEOPLE; $i++) {
            $faker = $this->seed($i);
            $type = $accountTypes[($i - 1) % count($accountTypes)];
            $team = $teams[$i % count($teams)];
            $personSkills = $faker->randomElements($skills, $faker->numberBetween(1, 3));

            $user = User::query()->firstOrNew(['email' => sprintf('person%02d@example.test', $i)]);
            $user->fill([
                'name' => $faker->firstName().' '.$faker->lastName(),
                'job_title' => $faker->randomElement($titles[$type->value]),
                'team' => $team,
                'phone' => $type === RoleName::Staff ? sprintf('0113 496 0%03d', 100 + $i) : null,
                'mobile' => sprintf('07700 900%03d', 100 + $i),
                'location' => $faker->city(),
                'skills' => $personSkills,
                'bio' => "Part of the {$team} team. Happy to help with ".Str::lower(implode(', ', $personSkills)).'.',
                'emergency_contact_name' => $faker->firstName().' '.$faker->lastName(),
                'emergency_contact_phone' => sprintf('07700 900%03d', 500 + $i),
                'employee_number' => sprintf('E%04d', 1000 + $i),
                'is_active' => true,
            ]);
            if (! $user->exists) {
                $user->password = Str::random(40);
            }
            $user->forceFill(['email_verified_at' => $this->today->subMonths(3)])->save();

            $roles = [$type->value];
            if (isset($extraRoles[$i])) {
                $roles[] = $extraRoles[$i]->value;
            }
            $user->syncRoles($roles);

            $people->push($user);
        }
        $this->people = $people;

        // Organisation chart: person01 at the top, staff manage staff in
        // fours, and everyone else reports to a member of staff.
        $staff = $people->filter(fn (User $u, int $index) => $index % count($accountTypes) === 0)->values();
        foreach ($people as $index => $user) {
            $position = $staff->search(fn (User $s) => $s->is($user));
            $manager = match (true) {
                $position === 0 => null,
                $position !== false => $staff[intdiv($position - 1, 4)],
                default => $staff[1 + ($index % ($staff->count() - 1))],
            };
            $user->forceFill(['manager_id' => $manager?->getKey()])->save();
        }
    }

    private function seedPlacements(): void
    {
        $organisations = ['Northfield Exotic Rescue (fake)', 'Riverside Reptile Sanctuary (fake)', 'Hollybank Wildlife Centre (fake)', 'Marsh Lane Animal Clinic (fake)'];
        $titles = ['Animal care placement', 'Education outreach placement', 'Visitor centre placement', 'Husbandry and records placement'];
        $supervisors = $this->people->filter(fn (User $u) => $u->hasRole(RoleName::Supervisor->value))->values();
        $students = $this->people->filter(fn (User $u) => $u->hasRole(RoleName::Student->value))->values();

        foreach ($students as $index => $student) {
            $supervisor = $supervisors[$index % $supervisors->count()];
            $placement = Placement::query()->firstOrNew(['student_id' => $student->getKey()]);
            $placement->fill([
                'supervisor_id' => $supervisor->getKey(),
                'title' => $titles[$index % count($titles)],
                'organisation' => $organisations[$index % count($organisations)],
                'starts_on' => $this->today->subWeeks(6 + $index % 4)->toDateString(),
                'ends_on' => $this->today->addMonths(3 + $index % 3)->toDateString(),
                'guidance' => 'Log your hours after each shift. Your supervisor reviews them each week. Demo content for local testing only.',
            ])->save();

            for ($k = 0; $k < 6; $k++) {
                $faker = $this->seed(2000 + $index * 10 + $k);
                $status = match (true) {
                    $k >= 4 => PlacementHour::STATUS_PENDING,
                    $k === 3 => PlacementHour::STATUS_RETURNED,
                    default => PlacementHour::STATUS_APPROVED,
                };
                $workedOn = $this->today->subDays(3 * $k + 1);
                $reviewed = $status !== PlacementHour::STATUS_PENDING;

                PlacementHour::query()->updateOrCreate(
                    ['placement_id' => $placement->getKey(), 'worked_on' => $workedOn],
                    [
                        'student_id' => $student->getKey(),
                        'minutes' => $faker->randomElement([120, 180, 210, 240, 300, 360, 420]),
                        'notes' => $faker->randomElement(['Cleaned and checked enclosures.', 'Helped with a school visit.', 'Prepared feeds and updated records.', 'Shadowed the keeper on health checks.', 'Supported the visitor centre.']),
                        'status' => $status,
                        'reviewer_notes' => $status === PlacementHour::STATUS_RETURNED ? 'Please add what you did in the afternoon.' : null,
                        'reviewed_by' => $reviewed ? $supervisor->getKey() : null,
                        'reviewed_at' => $reviewed ? $workedOn->addDays(2)->setTime(16, 0) : null,
                    ],
                );
            }
        }
    }

    private function seedUpdates(): void
    {
        $titles = [
            'Welcome to the new intranet', 'Bank holiday rota: cover still needed', 'New volunteer induction dates for December',
            'Reptile house heating checks this week', 'Christmas quiz night: teams wanted', 'Reminder: complete your safeguarding refresher',
            'Car park resurfacing on Thursday', 'Thank you from the education team', 'Updated lone working guidance',
            'Student placement showcase', 'Winter feed delivery changes', 'First aid course places available',
            'Kitchen deep clean on Friday', 'Fundraising target reached for the new enclosure', 'New sign-in methods and 2FA',
            'Visitor centre opening hours over winter', 'Lost property in the staff room', 'Supervisor drop-in sessions',
            'New expenses form', 'Wellbeing walk next Wednesday', 'Enrichment ideas wanted',
            'Handbook chapter updated: animal handling', 'Fire alarm test on Monday morning', 'Welcome to our new starters',
            'School visits booked for the spring term', 'Recycling changes in the office', 'Volunteer of the month',
            'Open shifts this weekend', 'Training room booking reminder', 'Summer open day: early planning',
        ];
        $paragraphs = [
            'If you have questions, reply in the comments or speak to your supervisor.',
            'Please check the rota before your next shift, and arrange any swaps through your supervisor.',
            'The details are in the handbook. Ask the office team if anything is unclear.',
            'Thank you to everyone who helped. It makes a real difference to the animals in our care.',
            'Sign-up sheets are in the staff room, or reply below to add your name.',
            'This applies from Monday. The old arrangement ends on Friday.',
        ];
        $comments = [
            'Thanks for the update.', 'I can help with this.', 'Is there a sign-up sheet?', 'Great news!',
            'Could this be shared with the volunteers too?', 'Noted, thank you.', "I'll raise this at the team meeting.",
            'Count me in.', 'What time does it start?', 'Very helpful, thanks.',
        ];
        $sanitizer = app(HtmlSanitizer::class);
        $authors = [$this->person(1), $this->person(4)];

        foreach ($titles as $n => $title) {
            $n++;
            $audiences = match (true) {
                $n % 5 === 3 => [RoleName::Staff->value],
                $n % 7 === 4 => [RoleName::Volunteer->value],
                $n % 9 === 5 => [RoleName::Student->value],
                default => [],
            };
            $publishedAt = $this->today->subDays($n)->setTime(9 + $n % 8, 0);
            $commentsEnabled = $n % 6 !== 0;

            $post = UpdatePost::query()->updateOrCreate(['title' => $title], [
                'author_id' => $authors[$n % 2]->getKey(),
                'body' => $sanitizer->clean('<p>'.e($title).'. Demo content for local testing only.</p><p>'.e($paragraphs[$n % count($paragraphs)]).'</p>'),
                'audiences' => $audiences,
                'pinned' => $n <= 2,
                'comments_enabled' => $commentsEnabled,
                'email_alert' => false,
                'published_at' => $publishedAt,
            ]);

            if ($commentsEnabled) {
                foreach ([3, 7] as $k => $step) {
                    UpdateComment::query()->updateOrCreate(
                        ['update_post_id' => $post->getKey(), 'user_id' => $this->person($n * $step + 1)->getKey()],
                        ['body' => $comments[($n + $k) % count($comments)]],
                    );
                }
            }

            foreach ($this->people as $index => $user) {
                if (($index + $n) % 3 === 0) {
                    UpdateRead::query()->updateOrCreate(
                        ['update_post_id' => $post->getKey(), 'user_id' => $user->getKey()],
                        ['read_at' => $publishedAt->addHours(1 + $index % 5)],
                    );
                }
            }
        }
    }

    private function seedEvents(): void
    {
        $events = [
            ['Volunteer induction (October)', -30], ['Reptile handling refresher', -21], ['Team meeting: autumn review', -14],
            ["Fundraising stall at the farmers' market", -7], ['Volunteer induction (December)', 2], ['First aid training', 5],
            ['Christmas quiz night', 9], ['Supervisor drop-in', 12], ['Wellbeing walk', 16],
            ['School visit: Year 5', 20], ['All-staff briefing', 27], ['Spring term planning', 35],
        ];
        $locations = ['Training room', 'Education centre', 'Reptile house', 'Online (Teams link in the invite)', 'Visitor centre', 'Meeting room 2'];
        $statuses = ['going', 'maybe', 'declined'];

        foreach ($events as $e => [$title, $days]) {
            $startsAt = $this->today->addDays($days)->setTime(10 + $e % 5, 0);
            $event = Event::query()->updateOrCreate(['title' => $title], [
                'author_id' => $this->person(4)->getKey(),
                'body' => '<p>Demo event for local testing only. Please reply so we know numbers.</p>',
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->addHours(2),
                'location' => $locations[$e % count($locations)],
                'reminder_at' => null,
            ]);

            foreach ($this->people as $index => $user) {
                if (($index + $e) % 4 === 0) {
                    EventRsvp::query()->updateOrCreate(
                        ['event_id' => $event->getKey(), 'user_id' => $user->getKey()],
                        ['status' => $statuses[($index + $e) % count($statuses)]],
                    );
                }
            }
        }
    }

    private function seedHandbook(): void
    {
        $chapters = [
            'demo-animal-handling' => ['Animal handling basics (demo)', 'Always work in pairs with large or venomous species. Wash your hands before and after handling any animal.'],
            'demo-lone-working' => ['Lone working (demo)', 'Tell the duty supervisor when you start and finish a lone shift. Carry the site phone at all times.'],
            'demo-expenses' => ['Expenses (demo)', 'Claim expenses within 30 days with a receipt. Mileage is paid at the published rate per mile.'],
            'demo-volunteer-shifts' => ['Volunteer shifts (demo)', 'Shifts are four hours. Swap shifts through the rota, and give at least 48 hours notice when you can.'],
        ];
        $sanitizer = app(HtmlSanitizer::class);
        $sort = 100;

        foreach ($chapters as $slug => [$title, $body]) {
            $chapter = HandbookChapter::query()->updateOrCreate(['slug' => $slug], ['title' => $title, 'sort_order' => $sort += 10, 'published' => true]);
            HandbookVersion::query()->updateOrCreate(
                ['handbook_chapter_id' => $chapter->getKey(), 'version_number' => 1],
                ['body' => $sanitizer->clean('<p>'.e($body).'</p>'), 'published_by' => $this->person(1)->getKey(), 'published_at' => $this->today->subMonth()],
            );
        }

        // The deploy-safe chapters are stamped with the time they were first
        // seeded. Pin them to a date, so every reset gives the same data.
        HandbookVersion::query()->whereNull('published_by')->update(['published_at' => $this->today->subMonths(2)]);
    }

    private function seedPolicies(): void
    {
        $policies = ['Safeguarding', 'Health and safety', 'Lone working', 'Data protection', 'Expenses', 'Volunteer', 'Animal welfare', 'Equality and diversity'];

        foreach ($policies as $k => $name) {
            $policy = PolicyDocument::query()->updateOrCreate(['title' => "{$name} policy (demo)"], [
                'sharepoint_url' => 'https://example.org/policies/'.Str::slug($name),
                'owner_name' => $k % 2 === 0 ? 'People team (demo)' : 'Operations team (demo)',
                'review_due_on' => $this->today->addDays($k === 0 ? -10 : 45 * $k)->toDateString(),
                'active' => true,
            ]);

            foreach ($this->people as $index => $user) {
                if (($index + $k) % 4 === 0) {
                    PolicyRead::query()->updateOrCreate(
                        ['policy_document_id' => $policy->getKey(), 'user_id' => $user->getKey()],
                        ['read_at' => $this->today->subDays(1 + ($index + $k) % 20)],
                    );
                }
            }
        }
    }

    /**
     * Two sites, this week's published rota and next week's draft, clock
     * records, timesheets, tasks, forms and checklists. The QA accounts join
     * the Animal care team so the role switcher shows real-looking data.
     */
    private function seedOperations(): void
    {
        User::query()->where('email', 'like', 'qa-%@example.test')->update(['team' => 'Animal care']);
        $qa = fn (string $profile) => User::query()->where('email', QaLogin::emailFor($profile))->firstOrFail();

        $reptiles = Site::query()->updateOrCreate(['name' => 'Reptile house (demo)'], ['team' => 'Animal care', 'active' => true]);
        Site::query()->updateOrCreate(['name' => 'Visitor centre (demo)'], ['team' => 'Office', 'active' => true]);

        $templates = collect();
        foreach ([['Morning feed', '08:00', '12:00'], ['Afternoon clean', '13:00', '17:00']] as [$name, $start, $end]) {
            foreach (array_keys(ShiftTemplate::WEEKDAYS) as $weekday) {
                $templates->push(ShiftTemplate::query()->updateOrCreate(
                    ['site_id' => $reptiles->id, 'name' => $name, 'weekday' => $weekday],
                    ['role' => 'Animal care', 'starts_at' => $start, 'ends_at' => $end, 'active' => true],
                ));
            }
        }

        $checklist = ChecklistTemplate::query()->updateOrCreate(
            ['site_id' => $reptiles->id, 'name' => 'Reptile house checks (demo)'],
            ['due_time' => '11:00', 'items' => ['Basking lamps on and working', 'Thermostats between 26 and 30°C', 'Water bowls cleaned and refilled', 'Enclosures locked'], 'active' => true],
        );

        $pool = $this->people->filter(fn (User $u) => $u->team === 'Animal care')->values()
            ->concat([$qa(RoleName::Staff->value), $qa(RoleName::Volunteer->value), $qa(RoleName::Student->value)]);
        $thisWeek = $this->today->startOfWeek();

        foreach ([[$thisWeek, RotaWeek::STATUS_PUBLISHED], [$thisWeek->addWeek(), RotaWeek::STATUS_DRAFT]] as [$weekStart, $status]) {
            $week = RotaWeek::query()->where('site_id', $reptiles->id)->whereDate('week_start', $weekStart)->first()
                ?? RotaWeek::query()->create(['site_id' => $reptiles->id, 'week_start' => $weekStart->toDateString(), 'status' => $status]);
            $week->forceFill(['status' => $status, 'published_at' => $status === RotaWeek::STATUS_PUBLISHED ? $weekStart->subDays(3)->setTime(16, 0) : null])->save();

            if ($week->shifts()->doesntExist()) {
                foreach ($templates->values() as $k => $template) {
                    [$start, $end] = $template->timesInWeek($weekStart);
                    $week->shifts()->create([
                        'site_id' => $reptiles->id,
                        'shift_template_id' => $template->id,
                        'user_id' => $k % 5 === 4 ? null : $pool[$k % $pool->count()]->getKey(),
                        'role' => $template->role,
                        'starts_at' => $start,
                        'ends_at' => $end,
                    ]);
                }
            }

            if ($status === RotaWeek::STATUS_PUBLISHED) {
                app(Checklists::class)->scheduleWeek($week);
            }
        }

        $published = Shift::query()->where('site_id', $reptiles->id)->whereDate('starts_at', '>=', $thisWeek)->whereDate('starts_at', '<', $thisWeek->addWeek())->orderBy('starts_at')->get();

        foreach ($pool->take(3) as $k => $member) {
            Availability::query()->updateOrCreate(
                ['user_id' => $member->getKey(), 'starts_at' => $thisWeek->addWeek()->addDays($k)->setTime(0, 0)],
                ['ends_at' => $thisWeek->addWeek()->addDays($k + 1)->setTime(0, 0), 'note' => 'Holiday (demo)'],
            );
        }

        $openShift = $published->first(fn (Shift $s) => $s->isOpen() && $s->starts_at->isFuture());
        if ($openShift !== null) {
            ShiftRequest::query()->firstOrCreate(
                ['shift_id' => $openShift->id, 'requester_id' => $qa(RoleName::Volunteer->value)->getKey(), 'type' => ShiftRequest::TYPE_CLAIM],
                ['status' => ShiftRequest::STATUS_PENDING, 'note' => 'Happy to cover this one (demo).'],
            );
        }

        foreach ($published->filter(fn (Shift $s) => $s->user_id !== null && $s->ends_at->isPast()) as $shift) {
            ClockRecord::query()->updateOrCreate(
                ['user_id' => $shift->user_id, 'clock_in_at' => $shift->starts_at->subMinutes(5)],
                ['shift_id' => $shift->id, 'site_id' => $shift->site_id, 'clock_out_at' => $shift->ends_at->addMinutes(10)],
            );
        }

        $lastWeek = $thisWeek->subWeek();
        foreach ($pool->filter(fn (User $u) => $u->hasRole(RoleName::Volunteer->value))->values() as $k => $volunteer) {
            $minutes = 0;
            for ($day = 0; $day < 3; $day++) {
                $in = $lastWeek->addDays($day * 2)->setTime(9, 0);
                $length = 180 + 30 * (($k + $day) % 4);
                ClockRecord::query()->updateOrCreate(['user_id' => $volunteer->getKey(), 'clock_in_at' => $in], ['site_id' => $reptiles->id, 'clock_out_at' => $in->addMinutes($length)]);
                $minutes += $length;
            }

            $approved = $k % 3 !== 2;
            $timesheet = Timesheet::query()->where('user_id', $volunteer->getKey())->whereDate('period_start', $lastWeek)->first()
                ?? new Timesheet(['user_id' => $volunteer->getKey(), 'period_start' => $lastWeek->toDateString()]);
            $timesheet->fill(['period_end' => $lastWeek->addDays(6)->toDateString(), 'total_minutes' => $minutes]);
            $timesheet->forceFill([
                'status' => $approved ? Timesheet::STATUS_APPROVED : Timesheet::STATUS_SUBMITTED,
                'submitted_at' => $thisWeek->setTime(9, 0),
                'reviewed_by' => $approved ? $qa(RoleName::Supervisor->value)->getKey() : null,
                'reviewed_at' => $approved ? $thisWeek->setTime(15, 0) : null,
                'locked_at' => $approved ? $thisWeek->setTime(15, 0) : null,
            ])->save();
            if ($timesheet->entries()->doesntExist()) {
                foreach (ClockRecord::query()->where('user_id', $volunteer->getKey())->whereBetween('clock_in_at', [$lastWeek, $lastWeek->addWeek()])->get() as $record) {
                    $timesheet->entries()->create(['clock_record_id' => $record->id, 'worked_on' => $record->clock_in_at->toDateString(), 'minutes' => $record->workedMinutes(), 'description' => 'Reptile house (demo)']);
                }
            }
        }

        $sanitizer = app(HtmlSanitizer::class);
        $manager = $qa(RoleName::Supervisor->value);
        $tasks = [
            ['Order more mealworms (demo)', Task::ASSIGN_PERSON, ['user_id' => $qa(RoleName::Staff->value)->getKey()], $this->today->subDay()->setTime(12, 0), null, Task::STATUS_OPEN],
            ['Deep clean the quarantine room (demo)', Task::ASSIGN_TEAM, ['team' => 'Animal care'], $this->today->addDays(2)->setTime(17, 0), null, Task::STATUS_IN_PROGRESS],
            ['Weekly weigh-in for the tortoises (demo)', Task::ASSIGN_TEAM, ['team' => 'Animal care'], $this->today->addDays(4)->setTime(10, 0), 'weekly', Task::STATUS_OPEN],
            ['Check the UV bulbs (demo)', Task::ASSIGN_PERSON, ['user_id' => $qa(RoleName::Volunteer->value)->getKey()], $this->today->addDay()->setTime(11, 0), 'monthly', Task::STATUS_OPEN],
            ['Restock the first aid box (demo)', Task::ASSIGN_PERSON, ['user_id' => $qa(RoleName::Staff->value)->getKey()], $this->today->subDays(3)->setTime(16, 0), null, Task::STATUS_DONE],
        ];
        foreach ($tasks as [$title, $type, $assignee, $due, $recurrence, $status]) {
            Task::query()->updateOrCreate(['title' => $title], $assignee + [
                'assignee_type' => $type,
                'description' => $sanitizer->clean('<p>Demo task for local testing only.</p>'),
                'due_at' => $due,
                'recurrence' => $recurrence,
                'status' => $status,
                'created_by' => $manager->getKey(),
            ]);
        }

        $incident = Form::query()->updateOrCreate(['title' => 'Incident report (demo)'], [
            'description' => 'Report an accident, injury, escape or near miss. Demo form for local testing only.',
            'status' => Form::STATUS_PUBLISHED,
            'route_to_role' => RoleName::Hr->value,
            'published_at' => $this->today->subMonth(),
        ]);
        foreach ([['What happened?', 'textarea', true, null], ['Where?', 'select', true, ['Reptile house', 'Visitor centre', 'Car park', 'Other']], ['Photo', 'photo', false, null], ['Signature', 'signature', true, null]] as $k => [$label, $type, $required, $options]) {
            $incident->fields()->updateOrCreate(['label' => $label], ['type' => $type, 'required' => $required, 'options' => $options, 'sort_order' => ($k + 1) * 10]);
        }
        $maintenance = Form::query()->updateOrCreate(['title' => 'Maintenance request (demo)'], [
            'description' => 'Ask for a repair. Demo form for local testing only.',
            'status' => Form::STATUS_PUBLISHED,
            'route_to_user_id' => $this->person(4)->getKey(),
            'published_at' => $this->today->subMonth(),
        ]);
        foreach ([['What needs fixing?', 'text', true, null], ['How urgent?', 'select', true, ['Today', 'This week', 'When possible']]] as $k => [$label, $type, $required, $options]) {
            $maintenance->fields()->updateOrCreate(['label' => $label], ['type' => $type, 'required' => $required, 'options' => $options, 'sort_order' => ($k + 1) * 10]);
        }
        if ($maintenance->submissions()->doesntExist()) {
            $submission = $maintenance->submissions()->create(['user_id' => $qa(RoleName::Staff->value)->getKey(), 'routed_to_user_id' => $maintenance->route_to_user_id]);
            foreach ($maintenance->fields()->get() as $field) {
                $submission->answers()->create(['field_id' => $field->id, 'value' => $field->type === 'select' ? 'This week' : 'The reptile house side door sticks (demo).']);
            }
        }

        ChecklistRun::query()
            ->where('checklist_template_id', $checklist->id)
            ->whereNull('completed_at')
            ->where('due_at', '<', $this->today)
            ->with('items')
            ->get()
            ->each(function (ChecklistRun $run) {
                $run->items->each(fn ($item) => $item->update(['done' => true]));
                $run->forceFill(['completed_at' => $run->due_at->subMinutes(20), 'completed_by' => $run->shift?->user_id])->save();
            });
    }

    private function seedAuditLog(): void
    {
        // Audit entries are append-only, so add the demo set once.
        if (AuditLog::query()->where('user_agent', self::AUDIT_MARKER)->exists()) {
            return;
        }

        $events = ['auth.signed_in', 'people.profile_updated', 'admin.roles.viewed', 'people.placement_hours_reviewed', 'auth.signed_out', 'admin.audit.viewed', 'people.manager_changed', 'admin.system.viewed'];

        for ($k = 0; $k < 40; $k++) {
            $actor = $this->person(1 + ($k * 7) % self::PEOPLE);
            AuditLog::query()->create([
                'occurred_at' => $this->today->subHours(5 * $k + 1),
                'actor_id' => $actor->getKey(),
                'event' => $events[$k % count($events)],
                'subject_type' => $actor->getMorphClass(),
                'subject_id' => $actor->getKey(),
                'ip_address' => '192.0.2.'.(10 + $k),
                'user_agent' => self::AUDIT_MARKER,
                'context' => ['demo' => true],
            ]);
        }
    }
}
