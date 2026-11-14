<x-layouts.app title="Onboarding and offboarding" subtitle="Checklists by member type for HR, managers and IT.">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-sg-field bg-sg-danger-mist p-3 text-sg-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="sg-card mb-6 max-w-xl space-y-3 p-6" aria-labelledby="start-heading">
        <h2 id="start-heading" class="font-serif text-xl font-semibold">Start a checklist</h2>
        <form method="post" action="{{ route('hr.checklists.store') }}" class="grid gap-3">
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
                <label class="mb-1 block text-sm font-medium" for="member_type">Member type</label>
                <select id="member_type" name="member_type" class="sg-input" required>
                    @foreach ($memberTypes as $type)
                        <option value="{{ $type }}" @selected(old('member_type') === $type)>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="kind">Kind</label>
                <select id="kind" name="kind" class="sg-input" required>
                    <option value="onboarding" @selected(old('kind', 'onboarding') === 'onboarding')>Onboarding</option>
                    <option value="offboarding" @selected(old('kind') === 'offboarding')>Offboarding</option>
                </select>
            </div>
            <button class="sg-btn sg-btn-primary" type="submit">Start</button>
        </form>
    </section>

    <section class="sg-card p-6" aria-labelledby="open-heading">
        <h2 id="open-heading" class="mb-3 font-serif text-xl font-semibold">Open checklists</h2>
        @if ($open->isEmpty())
            <p class="text-sg-muted">None open.</p>
        @else
            <ul class="space-y-2">
                @foreach ($open as $row)
                    <li>
                        <a class="text-sg-brand underline" href="{{ route('hr.checklists.show', $row) }}">
                            {{ ucfirst($row->kind) }} — {{ $row->user->name }} ({{ $row->member_type }})
                        </a>
                        <span class="text-sg-muted"> · {{ $row->items->whereNotNull('completed_at')->count() }}/{{ $row->items->count() }} done</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts.app>
