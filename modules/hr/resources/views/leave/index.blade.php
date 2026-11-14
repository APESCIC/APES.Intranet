<x-layouts.app title="Leave" subtitle="Request leave, approve for your team, and track balances.">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-sg-field bg-sg-danger-mist p-3 text-sg-danger" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="sg-card mb-6 max-w-xl p-6" aria-labelledby="balance-heading">
        <h2 id="balance-heading" class="mb-2 font-serif text-xl font-semibold">Your balance ({{ $balance->year }})</h2>
        <p>{{ $balance->remainingDays() }} days left of {{ $balance->allowance_days }} ({{ $balance->used_days }} used).</p>
    </section>

    <section class="sg-card mb-6 max-w-xl space-y-3 p-6" aria-labelledby="request-heading">
        <h2 id="request-heading" class="font-serif text-xl font-semibold">Request leave</h2>
        <form method="post" action="{{ route('hr.leave.store') }}" class="grid gap-3 sm:grid-cols-2">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium" for="starts_on">From</label>
                <input id="starts_on" class="sg-input" type="date" name="starts_on" value="{{ old('starts_on') }}" required>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="ends_on">To</label>
                <input id="ends_on" class="sg-input" type="date" name="ends_on" value="{{ old('ends_on') }}" required>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium" for="reason">Reason (optional)</label>
                <input id="reason" class="sg-input" name="reason" value="{{ old('reason') }}">
            </div>
            <div class="sm:col-span-2">
                <button class="sg-btn sg-btn-primary" type="submit">Submit request</button>
            </div>
        </form>
    </section>

    @if ($toDecide->isNotEmpty())
        <section class="sg-card mb-6 p-6" aria-labelledby="decide-heading">
            <h2 id="decide-heading" class="mb-3 font-serif text-xl font-semibold">Waiting for your decision</h2>
            <ul class="space-y-3">
                @foreach ($toDecide as $row)
                    <li class="flex flex-wrap items-center justify-between gap-2 border-b border-sg-border/60 pb-3" data-leave="{{ $row->id }}">
                        <span>{{ $row->user->name }} — {{ $row->starts_on->format('d M Y') }} to {{ $row->ends_on->format('d M Y') }} ({{ $row->days }} days)</span>
                        <span class="flex gap-2">
                            <form method="post" action="{{ route('hr.leave.approve', $row) }}">@csrf<button class="sg-btn sg-btn-primary sg-btn-sm" type="submit">Approve</button></form>
                            <form method="post" action="{{ route('hr.leave.decline', $row) }}">@csrf<button class="sg-btn sg-btn-secondary sg-btn-sm" type="submit">Decline</button></form>
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="sg-card mb-6 p-6" aria-labelledby="mine-heading">
        <h2 id="mine-heading" class="mb-3 font-serif text-xl font-semibold">Your recent requests</h2>
        @if ($mine->isEmpty())
            <p class="text-sg-muted">None yet.</p>
        @else
            <ul class="space-y-2">
                @foreach ($mine as $row)
                    <li>{{ $row->starts_on->format('d M Y') }}–{{ $row->ends_on->format('d M Y') }} — {{ $row->days }} days — {{ $row->status }}</li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($canRecordAbsence)
        <section class="sg-card max-w-xl space-y-3 p-6" aria-labelledby="absence-heading">
            <h2 id="absence-heading" class="font-serif text-xl font-semibold">Record absence (HR)</h2>
            <form method="post" action="{{ route('hr.leave.absence') }}" class="grid gap-3">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium" for="user_id">Person</label>
                    <select id="user_id" class="sg-input" name="user_id" required>
                        @foreach ($people as $person)
                            <option value="{{ $person->id }}">{{ $person->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium" for="absent_on">Date</label>
                    <input id="absent_on" class="sg-input" type="date" name="absent_on" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium" for="absence_reason">Reason</label>
                    <input id="absence_reason" class="sg-input" name="reason">
                </div>
                <button class="sg-btn sg-btn-secondary" type="submit">Save absence</button>
            </form>
        </section>
    @endif
</x-layouts.app>
