<x-layouts.app :title="'Appraisal — '.$appraisal->user->name" :subtitle="$appraisal->cycle->name.' ('.$appraisal->status.')'">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-sg-field bg-sg-danger-mist p-3 text-sg-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <p class="mb-4">
        <a class="text-sg-brand underline" href="{{ route('hr.appraisals.index') }}">All appraisals</a>
        @if ($appraisal->isLocked())
            <span class="text-sg-ok"> · Locked {{ $appraisal->locked_at?->format('d M Y H:i') }}</span>
        @endif
    </p>

    <section class="sg-card mb-6 p-6" aria-labelledby="self-heading">
        <h2 id="self-heading" class="mb-3 font-serif text-xl font-semibold">Self review</h2>
        @if ($canEdit && ($isMember || $isHr) && ! $appraisal->isLocked())
            <form method="post" action="{{ route('hr.appraisals.self', $appraisal) }}">
                @csrf
                <textarea class="sg-input min-h-32" name="self_review" required>{{ old('self_review', $appraisal->self_review) }}</textarea>
                <button class="sg-btn sg-btn-primary mt-3" type="submit">Save self review</button>
            </form>
        @else
            <p class="whitespace-pre-wrap">{{ $appraisal->self_review ?: 'Not written yet.' }}</p>
        @endif
    </section>

    <section class="sg-card mb-6 p-6" aria-labelledby="manager-heading">
        <h2 id="manager-heading" class="mb-3 font-serif text-xl font-semibold">Manager review</h2>
        @if ($canEdit && ($isManager || $isHr) && ! $appraisal->isLocked())
            <form method="post" action="{{ route('hr.appraisals.manager', $appraisal) }}">
                @csrf
                <textarea class="sg-input min-h-32" name="manager_review" required>{{ old('manager_review', $appraisal->manager_review) }}</textarea>
                <button class="sg-btn sg-btn-primary mt-3" type="submit">Save manager review</button>
            </form>
        @else
            <p class="whitespace-pre-wrap">{{ $appraisal->manager_review ?: 'Not written yet.' }}</p>
        @endif
    </section>

    @if ($canEdit && ($isHr || $isManager) && ! $appraisal->isLocked())
        <form method="post" action="{{ route('hr.appraisals.lock', $appraisal) }}">
            @csrf
            <button class="sg-btn sg-btn-secondary" type="submit">Complete and lock</button>
        </form>
    @endif
</x-layouts.app>
