<x-layouts.app title="Ask to swap a shift" :subtitle="$shift->label().' at '.$shift->site->name">
    @include('operations::rota.nav')
    @include('operations::partials.flash')

    <section class="sg-card max-w-3xl p-6" aria-labelledby="swap-heading">
        <h2 id="swap-heading" class="mb-3 font-serif text-xl font-semibold">Swap with a colleague's shift</h2>
        @if ($options->isEmpty())
            <p class="text-sg-muted">Nobody else has a published shift at this site within two weeks of yours.</p>
        @else
            <form method="post" action="{{ route('operations.rota.swap.store', $shift) }}" class="grid gap-3">
                @csrf
                <fieldset>
                    <legend class="sg-label">Their shift</legend>
                    <div class="grid gap-2">
                        @foreach ($options as $option)
                            <label class="flex items-center gap-2 rounded-sg-field border border-sg-info-mist p-2">
                                <input type="radio" name="target_shift_id" value="{{ $option->id }}" required>
                                {{ $option->label() }} · {{ $option->member->name }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div>
                    <label class="sg-label" for="note">Note for the manager (optional)</label>
                    <input id="note" name="note" maxlength="500" class="sg-input w-full">
                </div>
                <div>
                    <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Send swap request</button>
                </div>
            </form>
            <p class="mt-3 text-sm text-sg-muted">You, your colleague and the managers are emailed. Nothing changes until a manager approves.</p>
        @endif
    </section>
</x-layouts.app>
