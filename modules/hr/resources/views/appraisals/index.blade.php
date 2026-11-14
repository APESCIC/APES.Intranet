<x-layouts.app title="Appraisals" subtitle="Cycles, self and manager reviews.">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-sg-field bg-sg-danger-mist p-3 text-sg-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    @if ($canManage)
        <section class="sg-card mb-6 max-w-xl space-y-3 p-6" aria-labelledby="cycle-heading">
            <h2 id="cycle-heading" class="font-serif text-xl font-semibold">Open a cycle</h2>
            <form method="post" action="{{ route('hr.appraisals.cycles.store') }}" class="grid gap-3 sm:grid-cols-2">
                @csrf
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium" for="name">Name</label>
                    <input id="name" class="sg-input" name="name" value="{{ old('name', 'Annual appraisal') }}" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium" for="year">Year</label>
                    <input id="year" class="sg-input" type="number" name="year" value="{{ old('year', now()->year) }}" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium" for="starts_on">Starts</label>
                    <input id="starts_on" class="sg-input" type="date" name="starts_on" value="{{ old('starts_on') }}" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium" for="ends_on">Ends</label>
                    <input id="ends_on" class="sg-input" type="date" name="ends_on" value="{{ old('ends_on') }}" required>
                </div>
                <div class="sm:col-span-2">
                    <button class="sg-btn sg-btn-primary" type="submit">Open cycle</button>
                </div>
            </form>
        </section>

        <section class="sg-card mb-6 max-w-xl space-y-3 p-6" aria-labelledby="start-heading">
            <h2 id="start-heading" class="font-serif text-xl font-semibold">Start an appraisal</h2>
            <form method="post" action="{{ route('hr.appraisals.store') }}" class="grid gap-3">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium" for="cycle_id">Cycle</label>
                    <select id="cycle_id" name="cycle_id" class="sg-input" required>
                        @foreach ($cycles as $cycle)
                            <option value="{{ $cycle->id }}">{{ $cycle->name }} ({{ $cycle->year }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium" for="user_id">Member</label>
                    <select id="user_id" name="user_id" class="sg-input" required>
                        @foreach ($members as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="sg-btn sg-btn-primary" type="submit">Start</button>
            </form>
        </section>
    @endif

    <section class="sg-card p-6" aria-labelledby="list-heading">
        <h2 id="list-heading" class="mb-3 font-serif text-xl font-semibold">Your appraisals</h2>
        @if ($appraisals->isEmpty())
            <p class="text-sg-muted">None yet.</p>
        @else
            <ul class="space-y-2">
                @foreach ($appraisals as $row)
                    <li>
                        <a class="text-sg-brand underline" href="{{ route('hr.appraisals.show', $row) }}">
                            {{ $row->user->name }} — {{ $row->cycle->name }} ({{ $row->status }})
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts.app>
