<x-layouts.app :title="$run->template->name" :subtitle="$run->site->name.' · '.$run->shift->label().' · due by '.$run->due_at->format('H:i')">
    @include('operations::partials.flash')

    @if ($run->isComplete())
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" data-read-only>
            Completed by {{ $run->completer?->name ?? 'a former member' }} on {{ $run->completed_at->format('D j M Y H:i') }}. This record is read-only.
        </p>
    @elseif ($run->isMissed())
        <p class="mb-4 rounded-sg-field bg-sg-error-mist p-3 text-sg-error">This checklist is past its due time. Complete it as soon as you can.</p>
    @endif

    @if ($canUpdate)
        <form method="post" action="{{ route('operations.checklists.runs.update', $run) }}" enctype="multipart/form-data" class="sg-card max-w-3xl p-6">
            @csrf
            @method('put')
            <ul class="grid gap-4">
                @foreach ($run->items as $item)
                    <li class="rounded-sg-field border border-sg-info-mist p-3">
                        <label class="flex items-start gap-2 font-semibold">
                            <input type="checkbox" name="items[{{ $item->id }}][done]" value="1" class="mt-1" @checked(old('items.'.$item->id.'.done', $item->done))>
                            {{ $item->label }}
                        </label>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            <div>
                                <label class="sg-label" for="note-{{ $item->id }}">Note</label>
                                <input id="note-{{ $item->id }}" name="items[{{ $item->id }}][note]" maxlength="1000" class="sg-input w-full" value="{{ old('items.'.$item->id.'.note', $item->note) }}">
                            </div>
                            <div>
                                <label class="sg-label" for="photo-{{ $item->id }}">Photo (optional)</label>
                                <input id="photo-{{ $item->id }}" type="file" name="photos[{{ $item->id }}]" accept="image/*" capture="environment" class="sg-input w-full">
                                @if ($item->photo_path)
                                    <p class="mt-1 text-sm"><a href="{{ route('operations.checklists.photos.show', $item) }}" target="_blank" rel="noopener">See the photo already added<span class="sr-only"> (opens in a new tab)</span></a></p>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="submit" name="action" value="complete" class="sg-btn sg-btn-primary sg-btn-sm">Complete checklist</button>
                <button type="submit" name="action" value="save" class="sg-btn sg-btn-secondary sg-btn-sm">Save progress</button>
            </div>
        </form>
    @else
        <section class="sg-card max-w-3xl p-6" aria-labelledby="items-heading">
            <h2 id="items-heading" class="sr-only">Checks</h2>
            <ul class="grid gap-3">
                @foreach ($run->items as $item)
                    <li class="rounded-sg-field border border-sg-info-mist p-3">
                        <p class="font-semibold">
                            <span @class(['sg-badge', 'sg-badge--ok' => $item->done, 'sg-badge--warn' => ! $item->done])>{{ $item->done ? 'Done' : 'Not done' }}</span>
                            {{ $item->label }}
                        </p>
                        @if ($item->note)<p class="mt-1 text-sm">{{ $item->note }}</p>@endif
                        @if ($item->photo_path)
                            <img src="{{ route('operations.checklists.photos.show', $item) }}" alt="Photo for {{ $item->label }}" class="mt-2 max-h-64 max-w-full rounded-sg-field border border-sg-info-mist">
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.app>
