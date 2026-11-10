<x-layouts.app title="Form builder" subtitle="Build forms without code and choose who receives each submission.">
    @include('operations::partials.flash')

    <section class="sg-card mb-6 max-w-3xl p-6" aria-labelledby="new-form">
        <h2 id="new-form" class="mb-3 font-serif text-xl font-semibold">New form</h2>
        <form method="post" action="{{ route('admin.operations.forms.store') }}" class="grid gap-3">
            @csrf
            <div>
                <label class="sg-label" for="title">Title</label>
                <input id="title" name="title" maxlength="200" class="sg-input w-full" value="{{ old('title') }}" required>
            </div>
            <div>
                <label class="sg-label" for="description">Description for members (optional)</label>
                <textarea id="description" name="description" rows="2" maxlength="2000" class="sg-input w-full">{{ old('description') }}</textarea>
            </div>
            <div><button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Create draft</button></div>
        </form>
    </section>

    <section class="sg-card p-6" aria-labelledby="forms-heading">
        <h2 id="forms-heading" class="mb-3 font-serif text-xl font-semibold">Forms</h2>
        <x-operations::table-scroll label="Forms">
            <table class="sg-table">
                <thead>
                    <tr><th scope="col">Title</th><th scope="col">Fields</th><th scope="col">Submissions</th><th scope="col">Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($forms as $form)
                        <tr>
                            <td><a href="{{ route('admin.operations.forms.show', $form) }}">{{ $form->title }}</a></td>
                            <td>{{ $form->fields_count }}</td>
                            <td>{{ $form->submissions_count }}</td>
                            <td><span @class(['sg-badge', 'sg-badge--ok' => $form->isPublished()])>{{ ucfirst($form->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-sg-muted">No forms yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-operations::table-scroll>
    </section>
</x-layouts.app>
