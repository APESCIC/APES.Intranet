<x-layouts.app :title="$submission->form->title" :subtitle="'Sent by '.($submission->submitter?->name ?? 'a former member').' · '.$submission->created_at->format('j F Y H:i')">
    @include('operations::partials.flash')

    @php($answers = $submission->answers->keyBy('field_id'))
    <article class="sg-card max-w-3xl p-6">
        <dl class="grid gap-4">
            @foreach ($submission->form->fields as $field)
                @php($answer = $answers->get($field->id))
                <div>
                    <dt class="text-sm font-semibold text-sg-muted">{{ $field->label }}</dt>
                    <dd class="mt-1">
                        @if ($answer?->file_path)
                            <img src="{{ route('operations.forms.files.show', $answer) }}" alt="{{ $field->type === 'signature' ? 'Signature' : 'Photo' }} for {{ $field->label }}" class="max-h-80 max-w-full rounded-sg-field border border-sg-info-mist bg-white">
                        @elseif (filled($answer?->value))
                            <span class="whitespace-pre-wrap">{{ $answer->value }}</span>
                        @else
                            <span class="text-sg-muted">No answer</span>
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    </article>
</x-layouts.app>
