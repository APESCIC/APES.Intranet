<x-layouts.app title="Forms" subtitle="Fill in an APES form. Each one goes straight to the right person.">
    @include('operations::partials.flash')

    @if ($inboxCount > 0)
        <p class="mb-4"><a class="sg-btn sg-btn-secondary sg-btn-sm" href="{{ route('operations.forms.inbox') }}">Submissions sent to you ({{ $inboxCount }})</a></p>
    @endif

    <section class="sg-card mb-6 p-6" aria-labelledby="forms-heading">
        <h2 id="forms-heading" class="mb-3 font-serif text-xl font-semibold">Available forms</h2>
        <ul class="grid gap-3 sm:grid-cols-2">
            @forelse ($forms as $form)
                <li class="rounded-sg-field border border-sg-info-mist p-4">
                    <a class="font-semibold" href="{{ route('operations.forms.show', $form) }}">{{ $form->title }}</a>
                    @if ($form->description)<p class="mt-1 text-sm text-sg-muted">{{ $form->description }}</p>@endif
                </li>
            @empty
                <li class="text-sg-muted">No forms are published yet.</li>
            @endforelse
        </ul>
    </section>

    @if ($mine->isNotEmpty())
        <section class="sg-card p-6" aria-labelledby="mine-heading">
            <h2 id="mine-heading" class="mb-3 font-serif text-xl font-semibold">What you have sent</h2>
            <ul class="grid gap-2">
                @foreach ($mine as $submission)
                    <li><a href="{{ route('operations.forms.submissions.show', $submission) }}">{{ $submission->form->title }}</a> · {{ $submission->created_at->format('D j M H:i') }}</li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.app>
