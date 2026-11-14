<x-layouts.app title="Overdue training" subtitle="Members missing a current completion for a required course.">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif

    <section class="sg-card mb-6 p-6" aria-labelledby="report-heading">
        <h2 id="report-heading" class="mb-3 font-serif text-xl font-semibold">Overdue report</h2>
        @if ($rows === [])
            <p class="text-sg-muted">Nobody is overdue.</p>
        @else
            <ul class="space-y-4">
                @foreach ($rows as $row)
                    <li data-member="{{ $row['user']->id }}">
                        <p class="font-medium">{{ $row['user']->name }}</p>
                        <ul class="ml-4 list-disc text-sg-muted">
                            @foreach ($row['overdue'] as $course)
                                <li>{{ $course->title }}</li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="sg-card max-w-xl space-y-3 p-6" aria-labelledby="record-heading">
        <h2 id="record-heading" class="font-serif text-xl font-semibold">Record a completion</h2>
        <form method="post" action="{{ route('hr.training.completions.store') }}" class="grid gap-3">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium" for="user_id">Member</label>
                <select id="user_id" name="user_id" class="sg-input" required>
                    <option value="">Choose…</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}" @selected((string) old('user_id') === (string) $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="course_id">Course</label>
                <select id="course_id" name="course_id" class="sg-input" required>
                    <option value="">Choose…</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected((string) old('course_id') === (string) $course->id)>{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="completed_on">Completed on</label>
                <input id="completed_on" class="sg-input" type="date" name="completed_on" value="{{ old('completed_on', now()->toDateString()) }}" required>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="notes">Notes (optional)</label>
                <input id="notes" class="sg-input" name="notes" value="{{ old('notes') }}">
            </div>
            <button class="sg-btn sg-btn-primary" type="submit">Save completion</button>
        </form>
        <p class="text-sm text-sg-muted">Fake demo data only. Course content stays on the LMS.</p>
    </section>
</x-layouts.app>
