<x-layouts.app :title="'Enrolments — '.$course->title">
    <div class="mx-auto max-w-4xl px-4 py-8">
        <p class="text-sm text-ink-muted">
            <a href="{{ route('learning.courses.show', $course) }}" class="underline">{{ $course->title }}</a>
            · Enrolments
        </p>
        <h1 class="mt-2 font-display text-3xl text-ink">Enrolments</h1>

        @if (session('status'))
            <p class="mt-4 text-sm text-brand" role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <ul class="mt-4 text-sm text-danger" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <section class="mt-8">
            <h2 class="font-display text-xl text-ink">Manual enrol</h2>
            <form method="post" action="{{ route('learning.courses.enrolments.manual', $course) }}" class="mt-3 flex flex-wrap gap-2">
                @csrf
                <select name="user_id" class="sg-input" required>
                    <option value="">Select learner</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                <button class="sg-btn sg-btn-primary" type="submit">Enrol</button>
            </form>
        </section>

        <section class="mt-10">
            <h2 class="font-display text-xl text-ink">Canonical group rules</h2>
            <p class="mt-1 text-sm text-ink-muted">Membership of an approved Cloudron group enrols learners; leaving the group ends the group enrolment.</p>
            <ul class="mt-3 divide-y divide-border">
                @forelse ($course->groupRules as $rule)
                    <li class="py-2 text-sm text-ink">{{ $rule->directory_group }}</li>
                @empty
                    <li class="py-2 text-sm text-ink-muted">No group rules yet.</li>
                @endforelse
            </ul>
            <form method="post" action="{{ route('learning.courses.enrolments.group-rules', $course) }}" class="mt-3 flex flex-wrap gap-2">
                @csrf
                <select name="directory_group" class="sg-input" required>
                    @foreach ($canonicalGroups as $group)
                        <option value="{{ $group }}">{{ $group }}</option>
                    @endforeach
                </select>
                <button class="sg-btn" type="submit">Add group rule</button>
            </form>
            <form method="post" action="{{ route('learning.courses.enrolments.sync', $course) }}" class="mt-2">
                @csrf
                <button class="sg-btn" type="submit">Sync group enrolments now</button>
            </form>
        </section>

        <section class="mt-10">
            <h2 class="font-display text-xl text-ink">Prerequisites</h2>
            <ul class="mt-3 divide-y divide-border">
                @forelse ($course->prerequisites as $row)
                    <li class="py-2 text-sm text-ink">{{ $row->prerequisite->title }}</li>
                @empty
                    <li class="py-2 text-sm text-ink-muted">No prerequisites.</li>
                @endforelse
            </ul>
            <form method="post" action="{{ route('learning.courses.enrolments.prerequisites', $course) }}" class="mt-3 flex flex-wrap gap-2">
                @csrf
                <select name="prerequisite_course_id" class="sg-input" required>
                    <option value="">Select course</option>
                    @foreach ($courses as $other)
                        <option value="{{ $other->id }}">{{ $other->title }}</option>
                    @endforeach
                </select>
                <button class="sg-btn" type="submit">Add prerequisite</button>
            </form>
        </section>

        <section class="mt-10">
            <h2 class="font-display text-xl text-ink">Current enrolments</h2>
            <ul class="mt-3 divide-y divide-border">
                @forelse ($enrolments as $enrolment)
                    <li class="flex flex-wrap justify-between gap-2 py-3 text-sm">
                        <span class="text-ink">{{ $enrolment->user->name }}</span>
                        <span class="text-ink-muted">{{ $enrolment->method->label() }} · {{ $enrolment->status }}</span>
                    </li>
                @empty
                    <li class="py-3 text-sm text-ink-muted">No enrolments yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.app>
