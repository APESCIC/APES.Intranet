<x-layouts.app :title="$person->name" subtitle="Profile">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif

    <article class="sg-card max-w-3xl space-y-4 p-6">
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
                @if (in_array('account_type', $visible, true) && $person->accountTypeLabel())
                    <p class="mt-1 text-sm">{{ $person->accountTypeLabel() }}</p>
                @endif
            </div>
        </div>

        <dl class="grid gap-3 sm:grid-cols-2">
            @if (in_array('email', $visible, true))
                <div><dt class="text-sm text-sg-muted">Email</dt><dd>{{ $person->email }}</dd></div>
            @endif
            @if (in_array('team', $visible, true) && $person->team)
                <div><dt class="text-sm text-sg-muted">Team</dt><dd>{{ $person->team }}</dd></div>
            @endif
            @if (in_array('phone', $visible, true) && $person->phone)
                <div><dt class="text-sm text-sg-muted">Phone</dt><dd>{{ $person->phone }}</dd></div>
            @endif
            @if (in_array('mobile', $visible, true) && $person->mobile)
                <div><dt class="text-sm text-sg-muted">Mobile</dt><dd>{{ $person->mobile }}</dd></div>
            @endif
            @if (in_array('location', $visible, true) && $person->location)
                <div><dt class="text-sm text-sg-muted">Location</dt><dd>{{ $person->location }}</dd></div>
            @endif
            @if (in_array('skills', $visible, true) && $person->skills)
                <div class="sm:col-span-2"><dt class="text-sm text-sg-muted">Skills</dt><dd>{{ implode(', ', $person->skills) }}</dd></div>
            @endif
            @if (in_array('bio', $visible, true) && $person->bio)
                <div class="sm:col-span-2"><dt class="text-sm text-sg-muted">About</dt><dd>{{ $person->bio }}</dd></div>
            @endif
            @if (in_array('emergency_contact_name', $visible, true) && $person->emergency_contact_name)
                <div><dt class="text-sm text-sg-muted">Emergency contact</dt><dd>{{ $person->emergency_contact_name }}</dd></div>
            @endif
            @if (in_array('emergency_contact_phone', $visible, true) && $person->emergency_contact_phone)
                <div><dt class="text-sm text-sg-muted">Emergency phone</dt><dd>{{ $person->emergency_contact_phone }}</dd></div>
            @endif
            @if (in_array('employee_number', $visible, true) && $person->employee_number)
                <div><dt class="text-sm text-sg-muted">Employee number</dt><dd>{{ $person->employee_number }}</dd></div>
            @endif
            @if ($person->manager)
                <div><dt class="text-sm text-sg-muted">Manager</dt><dd><a href="{{ route('people.directory.show', $person->manager) }}" class="underline">{{ $person->manager->name }}</a></dd></div>
            @endif
        </dl>

        @if ($canEdit)
            <a href="{{ route('people.profile.edit') }}" class="sg-btn sg-btn-secondary sg-btn-sm">Edit profile</a>
        @endif
    </article>
</x-layouts.app>
