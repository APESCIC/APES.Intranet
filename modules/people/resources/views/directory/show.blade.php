<x-layouts.app :title="$person->name" subtitle="Directory profile">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif

    <article class="sg-card max-w-3xl space-y-4 p-6" data-person="{{ $person->id }}">
        <div class="flex items-start gap-4">
            @if (in_array('photo_path', $visible, true) && $person->photoUrl())
                <img src="{{ $person->photoUrl() }}" alt="" class="h-20 w-20 rounded-full object-cover" width="80" height="80">
            @else
                <span class="sg-avatar text-lg" aria-hidden="true">{{ $person->initials() }}</span>
            @endif
            <div>
                <h2 class="font-serif text-2xl font-semibold">{{ $person->name }}</h2>
                @if (in_array('job_title', $visible, true) && $person->job_title)
                    <p class="text-sg-muted">{{ $person->job_title }}</p>
                @endif
            </div>
        </div>

        <dl class="grid gap-3 sm:grid-cols-2">
            @if (in_array('email', $visible, true))
                <div><dt class="text-sm text-sg-muted">Email</dt><dd>{{ $person->email }}</dd></div>
                <div class="sm:col-span-2">
                    <a
                        class="sg-btn sg-btn-secondary sg-btn-sm"
                        href="https://teams.microsoft.com/l/chat/0/0?users={{ rawurlencode($person->email) }}"
                        target="_blank"
                        rel="noopener"
                    >
                        Start a Teams chat<span class="sr-only"> (opens in a new tab)</span>
                    </a>
                </div>
            @endif
            @if (in_array('team', $visible, true) && $person->team)
                <div><dt class="text-sm text-sg-muted">Team</dt><dd>{{ $person->team }}</dd></div>
            @endif
            @if (in_array('phone', $visible, true) && $person->phone)
                <div><dt class="text-sm text-sg-muted">Phone</dt><dd>{{ $person->phone }}</dd></div>
            @endif
            @if (in_array('skills', $visible, true) && $person->skills)
                <div class="sm:col-span-2"><dt class="text-sm text-sg-muted">Skills</dt><dd>{{ implode(', ', $person->skills) }}</dd></div>
            @endif
            @if (in_array('emergency_contact_name', $visible, true))
                <div data-field="emergency_contact_name"><dt class="text-sm text-sg-muted">Emergency contact</dt><dd>{{ $person->emergency_contact_name ?: '—' }}</dd></div>
            @endif
            @if (in_array('employee_number', $visible, true))
                <div data-field="employee_number"><dt class="text-sm text-sg-muted">Employee number</dt><dd>{{ $person->employee_number ?: '—' }}</dd></div>
            @endif
            @if ($person->manager)
                <div><dt class="text-sm text-sg-muted">Manager</dt><dd><a class="underline" href="{{ route('people.directory.show', $person->manager) }}">{{ $person->manager->name }}</a></dd></div>
            @endif
        </dl>

        @can('setManager', $person)
            <form method="post" action="{{ route('people.manager.update', $person) }}" class="border-t border-sg-info-mist pt-4">
                @csrf
                @method('PATCH')
                <label class="mb-1 block text-sm font-semibold" for="manager_id">Set manager</label>
                <div class="flex flex-wrap gap-2">
                    <select id="manager_id" name="manager_id" class="rounded-sg-field border border-sg-info-mist bg-white px-3 py-2">
                        <option value="">No manager</option>
                        @foreach (\App\Models\User::query()->active()->whereKeyNot($person->id)->orderBy('name')->get(['id','name']) as $candidate)
                            <option value="{{ $candidate->id }}" @selected((int) $person->manager_id === (int) $candidate->id)>{{ $candidate->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">Save</button>
                </div>
                @error('manager_id')<p class="mt-1 text-sm text-sg-danger">{{ $message }}</p>@enderror
            </form>
        @endcan
    </article>
</x-layouts.app>
