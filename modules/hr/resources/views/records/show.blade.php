<x-layouts.app :title="'HR: '.$person->name" subtitle="Fresh sign-in required. Views are audited.">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif

    <p class="mb-4">
        <a class="sg-btn sg-btn-secondary sg-btn-sm" href="{{ route('hr.records.index') }}">Back to HR records</a>
    </p>

    <article class="sg-card max-w-3xl space-y-6 p-6" data-hr-record="{{ $record->id }}">
        <header>
            <h2 class="font-serif text-2xl font-semibold">{{ $person->name }}</h2>
            <p class="text-sg-muted">{{ $person->email }}</p>
        </header>

        @if ($canEdit)
            <form method="post" action="{{ route('hr.records.update', $person) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="employment_status">Employment status</label>
                        <input id="employment_status" class="sg-input" name="employment_status" value="{{ old('employment_status', $record->employment_status) }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="contract_type">Contract type</label>
                        <input id="contract_type" class="sg-input" name="contract_type" value="{{ old('contract_type', $record->contract_type) }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="start_date">Start date</label>
                        <input id="start_date" class="sg-input" type="date" name="start_date" value="{{ old('start_date', optional($record->start_date)->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="end_date">End date</label>
                        <input id="end_date" class="sg-input" type="date" name="end_date" value="{{ old('end_date', optional($record->end_date)->format('Y-m-d')) }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium" for="work_location">Work location</label>
                        <input id="work_location" class="sg-input" name="work_location" value="{{ old('work_location', $record->work_location) }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="emergency_contact_name">Emergency contact</label>
                        <input id="emergency_contact_name" class="sg-input" name="emergency_contact_name" value="{{ old('emergency_contact_name', $record->emergency_contact_name) }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="emergency_contact_phone">Emergency phone</label>
                        <input id="emergency_contact_phone" class="sg-input" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $record->emergency_contact_phone) }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium" for="notes">HR notes</label>
                        <textarea id="notes" class="sg-input min-h-28" name="notes">{{ old('notes', $record->notes) }}</textarea>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="rounded-sg-field bg-sg-danger-mist p-3 text-sg-danger" role="alert">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <button class="sg-btn sg-btn-primary" type="submit">Save HR record</button>
            </form>
        @else
            <dl class="grid gap-3 sm:grid-cols-2">
                <div><dt class="text-sm text-sg-muted">Employment status</dt><dd>{{ $record->employment_status ?: '—' }}</dd></div>
                <div><dt class="text-sm text-sg-muted">Contract type</dt><dd>{{ $record->contract_type ?: '—' }}</dd></div>
                <div><dt class="text-sm text-sg-muted">Start date</dt><dd>{{ optional($record->start_date)->format('d M Y') ?: '—' }}</dd></div>
                <div><dt class="text-sm text-sg-muted">End date</dt><dd>{{ optional($record->end_date)->format('d M Y') ?: '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-sm text-sg-muted">Work location</dt><dd>{{ $record->work_location ?: '—' }}</dd></div>
                <div><dt class="text-sm text-sg-muted">Emergency contact</dt><dd>{{ $record->emergency_contact_name ?: '—' }}</dd></div>
                <div><dt class="text-sm text-sg-muted">Emergency phone</dt><dd>{{ $record->emergency_contact_phone ?: '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-sm text-sg-muted">HR notes</dt><dd class="whitespace-pre-wrap">{{ $record->notes ?: '—' }}</dd></div>
            </dl>
            <p class="text-sm text-sg-muted">Management can view this record. Only HR or admin can edit it.</p>
        @endif
    </article>
</x-layouts.app>
